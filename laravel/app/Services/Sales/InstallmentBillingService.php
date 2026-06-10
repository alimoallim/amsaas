<?php

namespace App\Services\Sales;

use App\Models\Agreement;
use App\Models\InstallmentSchedule;
use App\Models\InvoiceLineItem;
use App\Models\MonthlyInvoice;
use App\Models\User;
use App\Services\Billing\InvoiceNumberService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentBillingService
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Post issued invoices for instalment lines due on or before $asOf.
     *
     * @return array{posted: int, skipped: int, overdue: int}
     */
    public function postDueInvoices(string $companyId, ?Carbon $asOf = null, ?User $actor = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $stats = ['posted' => 0, 'skipped' => 0, 'overdue' => 0];

        return DB::transaction(function () use ($companyId, $asOf, $actor, &$stats) {
            $stats['overdue'] = $this->markOverdue($companyId, $asOf);

            $dueRows = InstallmentSchedule::query()
                ->with(['saleAgreement.agreement.apartment'])
                ->where('company_id', $companyId)
                ->whereNull('monthly_invoice_id')
                ->whereDate('due_date', '<=', $asOf->toDateString())
                ->whereRaw('amount > paid_amount')
                ->whereHas(
                    'saleAgreement.agreement',
                    fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', Agreement::STATUS_ACTIVE),
                )
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            foreach ($dueRows as $row) {
                if ($row->monthly_invoice_id) {
                    $stats['skipped']++;

                    continue;
                }

                $this->postInvoiceForInstallment($row, $actor);
                $stats['posted']++;
            }

            return $stats;
        });
    }

    public function postInvoiceForInstallment(InstallmentSchedule $row, ?User $actor = null): MonthlyInvoice
    {
        return DB::transaction(function () use ($row, $actor) {
            $row = InstallmentSchedule::query()
                ->with(['saleAgreement.agreement.apartment'])
                ->where('id', $row->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($row->monthly_invoice_id) {
                return MonthlyInvoice::query()->findOrFail($row->monthly_invoice_id);
            }

            $sale = $row->saleAgreement;
            $agreement = $sale?->agreement;
            $apartment = $agreement?->apartment;

            if (! $sale || ! $agreement || ! $apartment) {
                throw new \RuntimeException('Instalment schedule is missing sale context.');
            }

            $dueDate = Carbon::parse($row->due_date);
            $amount = round((float) $row->amount, 2);
            $prepaid = round((float) $row->paid_amount, 2);

            $invoice = MonthlyInvoice::create([
                'company_id' => $row->company_id,
                'apartment_id' => $apartment->id,
                'invoice_number' => app(InvoiceNumberService::class)->next($row->company_id, $dueDate->year),
                'contract_type' => 'sale',
                'contract_id' => $sale->id,
                'billing_year' => $dueDate->year,
                'billing_month' => $dueDate->month,
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal_installment' => $amount,
                'paid_amount' => $prepaid,
                'status' => 'draft',
                'notes' => sprintf(
                    'Sale instalment #%d for agreement %s',
                    $row->installment_number,
                    $agreement->agreement_number,
                ),
                'generated_by' => $actor?->id,
            ]);

            InvoiceLineItem::create([
                'monthly_invoice_id' => $invoice->id,
                'line_type' => 'installment',
                'description' => sprintf(
                    'Instalment %d of %d — %s',
                    $row->installment_number,
                    $sale->installment_months,
                    $agreement->agreement_number,
                ),
                'quantity' => 1,
                'unit_price' => $amount,
                'amount' => $amount,
                'sort_order' => 0,
            ]);

            $invoice->refresh();

            if ((float) $invoice->balance_due <= 0.009) {
                $invoice->update([
                    'status' => 'paid',
                    'finalized_at' => now(),
                    'finalized_by' => $actor?->id,
                ]);
            } else {
                $this->invoices->issue($invoice->fresh());
            }

            $row->update(['monthly_invoice_id' => $invoice->id]);
            $this->syncInstallmentStatus($row->fresh());

            return $invoice->fresh(['lineItems']);
        });
    }

    public function markOverdue(string $companyId, Carbon $asOf): int
    {
        return InstallmentSchedule::query()
            ->where('company_id', $companyId)
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->whereIn('status', [
                InstallmentSchedule::STATUS_PENDING,
                InstallmentSchedule::STATUS_PARTIALLY_PAID,
            ])
            ->whereRaw('amount > paid_amount')
            ->update(['status' => InstallmentSchedule::STATUS_OVERDUE]);
    }

    public function syncInstallmentStatus(InstallmentSchedule $row): InstallmentSchedule
    {
        $balance = $row->balanceDue();

        if ($balance <= 0.009) {
            $row->update([
                'status' => InstallmentSchedule::STATUS_PAID,
                'paid_at' => $row->paid_at ?? now()->toDateString(),
            ]);
        } elseif ((float) $row->paid_amount > 0.009) {
            $row->update([
                'status' => $row->due_date?->isPast()
                    ? InstallmentSchedule::STATUS_OVERDUE
                    : InstallmentSchedule::STATUS_PARTIALLY_PAID,
            ]);
        } elseif ($row->due_date?->isPast()) {
            $row->update(['status' => InstallmentSchedule::STATUS_OVERDUE]);
        } else {
            $row->update(['status' => InstallmentSchedule::STATUS_PENDING]);
        }

        return $row->fresh();
    }
}
