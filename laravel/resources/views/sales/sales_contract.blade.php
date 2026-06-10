<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Contract — {{ $agreement->agreement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #555; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #ccc; padding: 7px 9px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Sale Contract Summary</h1>
    <p class="muted">{{ $company?->name ?? 'Property Management' }}</p>

    <table>
        <tr><th>Contract number</th><td>{{ $agreement->agreement_number }}</td></tr>
        <tr><th>Status</th><td>{{ $agreement->status_label ?? $agreement->status }}</td></tr>
        <tr><th>Buyer</th><td>{{ $agreement->buyer?->full_name ?? '—' }}</td></tr>
        <tr><th>Unit</th><td>{{ $agreement->apartment?->building?->name ?? '—' }} — Unit {{ $agreement->apartment?->unit_number ?? '—' }}</td></tr>
        <tr><th>Sale price</th><td>{{ $agreement->currency ?? 'USD' }} {{ number_format((float) $sale->sale_price, 2) }}</td></tr>
        <tr><th>Down payment</th><td>{{ number_format((float) ($sale->down_payment ?? 0), 2) }}</td></tr>
        <tr><th>Payment type</th><td>{{ $sale->is_installment_sale ? 'Payment plan' : 'Cash' }}</td></tr>
        @if($sale->is_installment_sale)
        <tr><th>Financed amount</th><td>{{ number_format((float) $sale->financedAmountValue(), 2) }}</td></tr>
        <tr><th>Plan term</th><td>{{ $agreement->start_date?->format('Y-m-d') ?? '—' }} → {{ $agreement->end_date?->format('Y-m-d') ?? '—' }}</td></tr>
        @endif
        <tr><th>Contract date</th><td>{{ $agreement->start_date?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    @if($sale->special_terms)
        <p style="margin-top:20px;"><strong>Special terms</strong><br>{{ $sale->special_terms }}</p>
    @endif
</body>
</html>
