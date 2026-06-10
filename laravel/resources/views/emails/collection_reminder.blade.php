<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment reminder</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #1e293b;">
    <p>Dear {{ $tenantName }},</p>

    <p>This is a friendly reminder regarding your rent invoice <strong>{{ $invoice->invoice_number }}</strong>
        for {{ $invoice->billing_month }}/{{ $invoice->billing_year }}.</p>

    <p><strong>{{ $reminderLabel }}</strong></p>

    <ul>
        <li>Due date: {{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</li>
        <li>Balance due: {{ number_format((float) $invoice->balance_due, 2) }}</li>
    </ul>

    <p>Please arrange payment at your earliest convenience. If you have already paid, please disregard this message.</p>

    <p>Thank you.</p>
</body>
</html>
