<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\MonthlyInvoice;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function recordPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create($data);

            // AUTO-ALLOCATION LOGIC:
            // Find all unpaid or partially paid invoices for this tenant/buyer
            $unpaidInvoices = MonthlyInvoice::where('company_id', $data['company_id'])
                ->where(function ($query) use ($data) {
                    if (!empty($data['tenant_id'])) $query->where('contract_type', 'RentalAgreement')->where('contract_id', $data['tenant_id']);
                    // Add buyer logic similarly
                })
                ->where('balance_due', '>', 0)
                ->orderBy('issue_date', 'asc') // Pay oldest invoices first
                ->get();

            $remainingAmount = $payment->amount;

            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) break;

                $allocationAmount = min($remainingAmount, $invoice->balance_due);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'monthly_invoice_id' => $invoice->id,
                    'amount_allocated' => $allocationAmount,
                ]);

                $remainingAmount -= $allocationAmount;
            }

            return $payment;
        });
    }
}