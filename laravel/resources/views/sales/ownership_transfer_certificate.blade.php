<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ownership Transfer — {{ $agreement->agreement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 36px; }
        h1 { font-size: 22px; margin: 0 0 6px; text-align: center; }
        .subtitle { text-align: center; color: #444; margin-bottom: 28px; }
        table.summary { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.summary th, table.summary td { border: 1px solid #ccc; padding: 8px 10px; }
        table.summary th { background: #f3f4f6; text-align: left; width: 28%; }
        .footer { margin-top: 48px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <h1>Certificate of Ownership Transfer</h1>
    <p class="subtitle">{{ $company?->name ?? 'Property Management' }}</p>

    <p>
        This certifies that legal ownership of the property described below has been transferred
        to the buyer named herein, following completion of the sale contract and required internal approvals.
    </p>

    <table class="summary">
        <tr>
            <th>Contract</th>
            <td>{{ $agreement->agreement_number }}</td>
            <th>Transfer date</th>
            <td>{{ $sale->ownership_transfer_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>New owner</th>
            <td>{{ $agreement->buyer?->full_name ?? '—' }}</td>
            <th>Unit</th>
            <td>{{ $agreement->apartment?->building?->name ?? '—' }} — Unit {{ $agreement->apartment?->unit_number ?? '—' }}</td>
        </tr>
        <tr>
            <th>Sale price</th>
            <td>{{ $agreement->currency ?? 'USD' }} {{ number_format((float) $sale->sale_price, 2) }}</td>
            <th>Title deed #</th>
            <td>{{ $sale->title_deed_number ?? 'Pending issuance' }}</td>
        </tr>
    </table>

    <p class="footer">Generated {{ now()->format('Y-m-d H:i') }} · Document ID {{ $sale->id }}</p>
</body>
</html>
