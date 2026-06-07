<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: sans-serif; color: #111; line-height: 1.5;">
    <p>Hello {{ $tenantName }},</p>

    <p>
        Your invoice <strong>{{ $invoice->invoice_number }}</strong> for
        {{ $invoice->billing_month }}/{{ $invoice->billing_year }} is attached.
    </p>

    <p>
        <strong>Amount due:</strong>
        {{ number_format((float) $invoice->balance_due, 2) }}
        (total {{ number_format((float) $invoice->total_amount, 2) }})
    </p>

    <p><strong>Due date:</strong> {{ $invoice->due_date?->format('Y-m-d') ?? 'See invoice' }}</p>

    <p style="margin-top: 24px; font-size: 13px; color: #555;">
        Payment methods: bank transfer, mobile money (EVC Plus), or cash at the management office.
        Include your invoice number as the payment reference.
    </p>
</body>
</html>
