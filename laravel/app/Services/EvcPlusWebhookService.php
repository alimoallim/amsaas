<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\MonthlyInvoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvcPlusWebhookService
{
    public function __construct(
        protected PaymentService $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, payment_id?: string}
     */
    public function process(array $payload): array
    {
        $transactionId = trim((string) ($payload['transaction_id'] ?? ''));
        $invoiceNumber = trim((string) ($payload['invoice_number'] ?? ''));
        $amount = round((float) ($payload['amount'] ?? 0), 2);

        if ($transactionId === '' || $invoiceNumber === '' || $amount <= 0) {
            throw ValidationException::withMessages([
                'payload' => ['transaction_id, invoice_number, and amount are required.'],
            ]);
        }

        $existing = WebhookEvent::query()
            ->where('provider', 'evc_plus')
            ->where('external_id', $transactionId)
            ->first();

        if ($existing?->payment_id) {
            return [
                'status' => 'duplicate',
                'payment_id' => $existing->payment_id,
            ];
        }

        $invoice = MonthlyInvoice::query()
            ->withoutGlobalScopes()
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_number' => ['Invoice not found.'],
            ]);
        }

        if ($invoice->contract_type !== 'rental') {
            throw ValidationException::withMessages([
                'invoice_number' => ['EVC payments are only supported for rental invoices.'],
            ]);
        }

        $agreement = Agreement::query()
            ->withoutGlobalScopes()
            ->find($invoice->contract_id);

        if (! $agreement?->tenant_id) {
            throw ValidationException::withMessages([
                'invoice_number' => ['Invoice tenant could not be resolved.'],
            ]);
        }

        $systemUser = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->orderBy('created_at')
            ->first();

        if (! $systemUser) {
            throw ValidationException::withMessages([
                'company' => ['No user available to record webhook payment.'],
            ]);
        }

        return DB::transaction(function () use ($payload, $transactionId, $invoice, $agreement, $systemUser, $amount) {
            $duplicate = WebhookEvent::query()
                ->where('provider', 'evc_plus')
                ->where('external_id', $transactionId)
                ->lockForUpdate()
                ->first();

            if ($duplicate?->payment_id) {
                return [
                    'status' => 'duplicate',
                    'payment_id' => $duplicate->payment_id,
                ];
            }

            $paidAt = $payload['paid_at'] ?? $payload['payment_date'] ?? now()->toDateString();

            $payment = $this->payments->recordPayment($systemUser, [
                'tenant_id' => $agreement->tenant_id,
                'amount' => $amount,
                'payment_date' => is_string($paidAt) ? substr($paidAt, 0, 10) : now()->toDateString(),
                'payment_method' => 'mobile_money',
                'reference_number' => $transactionId,
                'notes' => 'EVC Plus webhook — invoice '.$invoice->invoice_number,
            ]);

            WebhookEvent::query()->updateOrCreate(
                [
                    'provider' => 'evc_plus',
                    'external_id' => $transactionId,
                ],
                [
                    'id' => $duplicate?->id ?? (string) Str::uuid(),
                    'company_id' => $invoice->company_id,
                    'payment_id' => $payment->id,
                    'status' => 'processed',
                    'payload' => $payload,
                ],
            );

            return [
                'status' => 'processed',
                'payment_id' => $payment->id,
            ];
        });
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('evc.webhook_secret');

        if (! filled($secret)) {
            return app()->environment(['local', 'testing']);
        }

        if (! filled($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
