<?php

namespace App\Services\Billing;

use App\Exceptions\BusinessRuleException;
use App\Models\Charge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChargeWorkflowService
{
    public function __construct(
        protected ApprovedChargeInvoiceSyncService $invoiceSync,
    ) {}

    public function approve(Charge $charge, User $user): Charge
    {
        if ($charge->status !== Charge::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending charges can be approved.'],
            ]);
        }

        if ($charge->invoice_id) {
            throw new BusinessRuleException(
                'Charge is already linked to an invoice.',
                'CHARGE_ALREADY_INVOICED',
            );
        }

        return DB::transaction(function () use ($charge, $user) {
            $charge->update([
                'status' => Charge::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $charge = $charge->fresh();

            $this->invoiceSync->syncAfterApproval($charge, $user);

            return $charge->fresh();
        });
    }

    public function reject(Charge $charge, User $user, ?string $reason = null): Charge
    {
        if ($charge->status !== Charge::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending charges can be rejected.'],
            ]);
        }

        if ($charge->invoice_id) {
            throw new BusinessRuleException(
                'Cannot reject a charge that is linked to an invoice.',
                'CHARGE_ALREADY_INVOICED',
            );
        }

        $notes = trim((string) ($reason ?? ''));
        $mergedNotes = $notes !== ''
            ? trim(($charge->notes ? $charge->notes."\n" : '').'Rejected: '.$notes)
            : $charge->notes;

        $charge->update([
            'status' => Charge::STATUS_CANCELLED,
            'notes' => $mergedNotes,
        ]);

        return $charge->fresh();
    }

    /**
     * @param  array<int, string>  $chargeIds
     * @return array{approved: int, skipped: int, synced_to_invoice: int, invoice_numbers: list<string>}
     */
    public function bulkApprove(array $chargeIds, User $user): array
    {
        $approved = 0;
        $skipped = 0;
        $syncedToInvoice = 0;
        $invoiceNumbers = [];

        DB::transaction(function () use ($chargeIds, $user, &$approved, &$skipped, &$syncedToInvoice, &$invoiceNumbers) {
            $charges = Charge::query()
                ->whereIn('id', $chargeIds)
                ->where('company_id', $user->company_id)
                ->get();

            foreach ($charges as $charge) {
                try {
                    if ($charge->status === Charge::STATUS_PENDING && ! $charge->invoice_id) {
                        $this->approve($charge, $user);
                        $approved++;

                        $fresh = $charge->fresh()->loadMissing('invoice');
                        if ($fresh->invoice_id && $fresh->invoice) {
                            $syncedToInvoice++;
                            $invoiceNumbers[] = $fresh->invoice->invoice_number;
                        }
                    } else {
                        $skipped++;
                    }
                } catch (ValidationException|BusinessRuleException) {
                    $skipped++;
                }
            }
        });

        return [
            'approved' => $approved,
            'skipped' => $skipped,
            'synced_to_invoice' => $syncedToInvoice,
            'invoice_numbers' => array_values(array_unique(array_filter($invoiceNumbers))),
        ];
    }
}
