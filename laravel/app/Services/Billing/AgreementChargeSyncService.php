<?php

namespace App\Services\Billing;

use App\Models\Agreement;
use App\Models\AgreementCharge;
use App\Models\ChargeModel;
use App\Models\RentalAgreement;
use App\Models\User;
use Illuminate\Support\Str;

class AgreementChargeSyncService
{
    /** @var list<string> */
    protected array $deletedChargeIds = [];

    /**
     * @param  array<int, array<string, mixed>>  $recurringCharges
     */
    public function sync(
        User $actor,
        Agreement $agreement,
        RentalAgreement $rental,
        array $recurringCharges = [],
        ?string $rentChargeModelId = null,
    ): void {
        $this->deletedChargeIds = [];
        $status = $this->resolveChargeStatus($agreement);
        $billingStart = $agreement->start_date
            ? \Illuminate\Support\Carbon::parse($agreement->start_date)->toDateString()
            : now()->toDateString();
        $keptIds = [];

        if ((float) $rental->monthly_rent > 0) {
            $rentModel = $this->resolveRentChargeModel(
                $actor->company_id,
                $rentChargeModelId
            );

            if ($rentModel) {
                $keptIds[] = $this->upsertRentCharge(
                    $actor,
                    $agreement,
                    $rentModel,
                    $billingStart,
                    $status
                )->id;
            }
        }

        $seenModelIds = [];

        foreach ($recurringCharges as $row) {
            $modelId = $row['charge_model_id'] ?? null;
            if (! $modelId || isset($seenModelIds[$modelId])) {
                continue;
            }

            $model = ChargeModel::query()
                ->where('company_id', $actor->company_id)
                ->where('id', $modelId)
                ->first();

            if (! $model || $model->usesAgreementRent()) {
                continue;
            }

            $seenModelIds[$modelId] = true;

            $keptIds[] = $this->upsertRecurringCharge(
                $actor,
                $agreement,
                $model,
                $row,
                $billingStart,
                $status
            )->id;
        }

        AgreementCharge::query()
            ->where('company_id', $actor->company_id)
            ->where('agreement_id', $agreement->id)
            ->when(
                $keptIds !== [],
                fn ($query) => $query->whereNotIn('id', $keptIds)
            )
            ->delete();
    }

    protected function resolveRentChargeModel(
        string $companyId,
        ?string $rentChargeModelId
    ): ?ChargeModel {
        if ($rentChargeModelId) {
            return ChargeModel::query()
                ->where('company_id', $companyId)
                ->where('id', $rentChargeModelId)
                ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT)
                ->first();
        }

        return ChargeModel::query()
            ->where('company_id', $companyId)
            ->where('status', ChargeModel::STATUS_ACTIVE)
            ->where('pricing_strategy', ChargeModel::STRATEGY_AGREEMENT_RENT)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
    }

    protected function upsertRentCharge(
        User $actor,
        Agreement $agreement,
        ChargeModel $model,
        string $billingStart,
        string $status,
    ): AgreementCharge {
        $attributes = [
            'charge_model_id' => $model->id,
            'charge_type_id' => $model->charge_type_id,
            'custom_name' => $model->name,
            'override_amount' => null,
            'override_unit_rate' => null,
            'billing_start_date' => $billingStart,
            'next_billing_date' => $billingStart,
            'status' => $status,
            'priority' => 0,
            'is_required' => true,
            'updated_by' => $actor->id,
        ];

        $existing = $this->findExistingCharge(
            $agreement->id,
            $model->id,
            $billingStart,
        );

        if ($existing) {
            return $this->updateExistingCharge($existing, $attributes);
        }

        return AgreementCharge::create([
            ...$attributes,
            'id' => (string) Str::uuid(),
            'company_id' => $actor->company_id,
            'agreement_id' => $agreement->id,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function upsertRecurringCharge(
        User $actor,
        Agreement $agreement,
        ChargeModel $model,
        array $row,
        string $billingStart,
        string $status,
    ): AgreementCharge {
        $priority = $model->isMetered() ? 5 : 10;

        $attributes = [
            'charge_model_id' => $model->id,
            'charge_type_id' => $model->charge_type_id,
            'custom_name' => $row['custom_name'] ?? $model->name,
            'override_amount' => $model->usesFlatFee() || $model->isFixed()
                ? ($row['override_amount'] ?? null)
                : null,
            'override_unit_rate' => $model->isMetered()
                ? ($row['override_unit_rate'] ?? null)
                : null,
            'billing_start_date' => $billingStart,
            'next_billing_date' => $billingStart,
            'status' => $status,
            'priority' => $priority,
            'is_required' => true,
            'updated_by' => $actor->id,
        ];

        $existingActive = $this->findActiveCharge(
            $agreement->id,
            $model->id,
            $billingStart,
        );

        $existingById = ! empty($row['id'])
            ? AgreementCharge::withTrashed()
                ->where('agreement_id', $agreement->id)
                ->where('id', $row['id'])
                ->first()
            : null;

        if ($existingActive) {
            $charge = $this->updateExistingCharge($existingActive, $attributes);

            if ($existingById && $existingById->id !== $existingActive->id) {
                $this->deleteCharge($existingById, force: true);
            }

            return $charge;
        }

        if ($existingById) {
            return $this->updateExistingCharge($existingById, $attributes);
        }

        $existingTrashed = AgreementCharge::onlyTrashed()
            ->where('agreement_id', $agreement->id)
            ->where('charge_model_id', $model->id)
            ->where('billing_start_date', $billingStart)
            ->first();

        if (
            $existingTrashed
            && ! in_array($existingTrashed->id, $this->deletedChargeIds, true)
        ) {
            return $this->updateExistingCharge($existingTrashed, $attributes);
        }

        return AgreementCharge::create([
            ...$attributes,
            'id' => (string) Str::uuid(),
            'company_id' => $actor->company_id,
            'agreement_id' => $agreement->id,
            'created_by' => $actor->id,
        ]);
    }

    protected function findExistingCharge(
        string $agreementId,
        string $chargeModelId,
        string $billingStart,
    ): ?AgreementCharge {
        return $this->findActiveCharge($agreementId, $chargeModelId, $billingStart)
            ?? AgreementCharge::onlyTrashed()
                ->where('agreement_id', $agreementId)
                ->where('charge_model_id', $chargeModelId)
                ->where('billing_start_date', $billingStart)
                ->first();
    }

    protected function findActiveCharge(
        string $agreementId,
        string $chargeModelId,
        string $billingStart,
    ): ?AgreementCharge {
        return AgreementCharge::query()
            ->where('agreement_id', $agreementId)
            ->where('charge_model_id', $chargeModelId)
            ->where('billing_start_date', $billingStart)
            ->first();
    }

    protected function deleteCharge(AgreementCharge $charge, bool $force = false): void
    {
        $this->deletedChargeIds[] = $charge->id;

        if ($force) {
            $charge->forceDelete();
        } else {
            $charge->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function updateExistingCharge(
        AgreementCharge $charge,
        array $attributes,
    ): AgreementCharge {
        if ($charge->trashed()) {
            $charge->restore();
        }

        $charge->update($attributes);

        return $charge->fresh();
    }

    protected function resolveChargeStatus(Agreement $agreement): string
    {
        return $agreement->status === Agreement::STATUS_ACTIVE
            ? AgreementCharge::STATUS_ACTIVE
            : AgreementCharge::STATUS_DRAFT;
    }
}
