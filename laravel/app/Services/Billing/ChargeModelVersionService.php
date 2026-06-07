<?php

namespace App\Services\Billing;

use App\Models\AgreementCharge;
use App\Models\Charge;
use App\Models\ChargeModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChargeModelVersionService
{
    public function isInUse(ChargeModel $chargeModel): bool
    {
        return AgreementCharge::query()
            ->where('charge_model_id', $chargeModel->id)
            ->exists()
            || Charge::query()
                ->where('charge_model_id', $chargeModel->id)
                ->exists();
    }

    public function shouldVersion(ChargeModel $chargeModel): bool
    {
        return $chargeModel->status === ChargeModel::STATUS_ACTIVE
            && $this->isInUse($chargeModel);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createVersion(
        ChargeModel $existing,
        array $validated,
        User $user,
    ): ChargeModel {
        $effectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        if ($effectiveFrom->lte(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_from' => [
                    'Active models in use must be versioned with a future effective start date.',
                ],
            ]);
        }

        return DB::transaction(function () use ($existing, $validated, $user, $effectiveFrom) {
            $existing->update([
                'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
                'status' => ChargeModel::STATUS_INACTIVE,
                'updated_by' => $user->id,
            ]);

            $versionNumber = (int) (($existing->metadata['version_number'] ?? 1)) + 1;
            $newCode = $this->nextVersionCode($existing, $versionNumber);

            $metadata = array_merge($existing->metadata ?? [], [
                'version_number' => $versionNumber,
                'replaces_id' => $existing->id,
                'replaces_code' => $existing->code,
            ]);

            return ChargeModel::create([
                ...$validated,
                'id' => (string) Str::uuid(),
                'company_id' => $existing->company_id,
                'code' => $newCode,
                'metadata' => $metadata,
                'created_by' => $user->id,
                'updated_by' => null,
            ]);
        });
    }

    public function cloneAsDraft(ChargeModel $source, User $user): ChargeModel
    {
        $attributes = $source->only($source->getFillable());
        unset($attributes['id']);

        $attributes['code'] = $this->nextCloneCode($source);
        $attributes['status'] = ChargeModel::STATUS_DRAFT;
        $attributes['effective_from'] = now()->toDateString();
        $attributes['effective_to'] = null;
        $attributes['company_id'] = $user->company_id;
        $attributes['created_by'] = $user->id;
        $attributes['updated_by'] = null;
        $attributes['metadata'] = array_merge($source->metadata ?? [], [
            'cloned_from_id' => $source->id,
        ]);

        return ChargeModel::create([
            ...$attributes,
            'id' => (string) Str::uuid(),
        ]);
    }

    protected function nextVersionCode(ChargeModel $existing, int $versionNumber): string
    {
        $base = preg_replace('/-V\d+$/', '', $existing->code) ?: $existing->code;
        $candidate = sprintf('%s-V%d', $base, $versionNumber);

        while (ChargeModel::query()
            ->where('company_id', $existing->company_id)
            ->where('code', $candidate)
            ->exists()) {
            $versionNumber++;
            $candidate = sprintf('%s-V%d', $base, $versionNumber);
        }

        return $candidate;
    }

    protected function nextCloneCode(ChargeModel $source): string
    {
        $base = $source->code;
        $candidate = $base.'-COPY';
        $suffix = 2;

        while (ChargeModel::query()
            ->where('company_id', $source->company_id)
            ->where('code', $candidate)
            ->exists()) {
            $candidate = $base.'-COPY'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
