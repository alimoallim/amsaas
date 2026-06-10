<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Plan — {{ $agreement->agreement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #ccc; padding: 7px 9px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Agreement Payment Plan Statement</h1>
    <p>{{ $agreement->agreement_number }} · {{ $agreement->buyer?->full_name ?? 'Buyer' }}</p>

    <table>
        <tr><th>Total price</th><td class="right">{{ number_format((float) $sale->sale_price, 2) }}</td></tr>
        <tr><th>Down payment</th><td class="right">{{ number_format((float) ($sale->down_payment ?? 0), 2) }}</td></tr>
        <tr><th>Financed amount</th><td class="right">{{ number_format((float) $summary['financed_amount'], 2) }}</td></tr>
        <tr><th>Total paid</th><td class="right">{{ number_format((float) $summary['total_paid'], 2) }}</td></tr>
        <tr><th>Running balance</th><td class="right">{{ number_format((float) $summary['running_balance'], 2) }}</td></tr>
        <tr><th>Progress</th><td class="right">{{ number_format((float) $summary['progress_percent'], 1) }}%</td></tr>
        <tr><th>Plan term</th><td>{{ $agreement->start_date?->format('Y-m-d') ?? '—' }} → {{ $agreement->end_date?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    @if($payments->isNotEmpty())
    <h2 style="margin-top:24px;font-size:14px;">Payments received</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $allocation)
            <tr>
                <td>{{ $allocation->payment?->payment_date?->format('Y-m-d') ?? '—' }}</td>
                <td>{{ $allocation->payment?->receipt_number ?? '—' }}</td>
                <td class="right">{{ number_format((float) $allocation->amount_allocated, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
