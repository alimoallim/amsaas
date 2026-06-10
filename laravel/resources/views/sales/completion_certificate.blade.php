<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Completion Certificate — {{ $agreement->agreement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 36px; }
        h1 { font-size: 22px; margin: 0 0 6px; text-align: center; }
        .subtitle { text-align: center; color: #444; margin-bottom: 28px; }
        .section { margin-top: 20px; }
        .label { font-size: 10px; text-transform: uppercase; color: #666; letter-spacing: 0.04em; }
        .value { font-size: 13px; font-weight: bold; margin-top: 4px; }
        table.summary { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.summary th, table.summary td { border: 1px solid #ccc; padding: 8px 10px; }
        table.summary th { background: #f3f4f6; text-align: left; }
        .footer { margin-top: 48px; font-size: 10px; color: #555; }
        .signatures { margin-top: 56px; width: 100%; }
        .signatures td { width: 50%; padding-top: 40px; vertical-align: top; }
        .line { border-top: 1px solid #333; width: 80%; margin-top: 48px; }
    </style>
</head>
<body>
    <h1>Certificate of Sale Completion</h1>
    <p class="subtitle">{{ $company?->name ?? 'Property Management' }}</p>

    <p>
        This certifies that the cash sale contract referenced below has been fully settled
        and ownership transfer has been recorded in the property management system.
    </p>

    <table class="summary">
        <tr>
            <th>Contract number</th>
            <td>{{ $agreement->agreement_number }}</td>
            <th>Completion date</th>
            <td>{{ $sale->closing_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>Buyer</th>
            <td>{{ $agreement->buyer?->full_name ?? '—' }}</td>
            <th>Unit</th>
            <td>
                {{ $agreement->apartment?->building?->name ?? '—' }}
                — Unit {{ $agreement->apartment?->unit_number ?? '—' }}
            </td>
        </tr>
        <tr>
            <th>Sale price</th>
            <td>{{ $agreement->currency ?? 'USD' }} {{ number_format((float) $sale->sale_price, 2) }}</td>
            <th>Amount received</th>
            <td>{{ $agreement->currency ?? 'USD' }} {{ number_format($sale->paidAmount(), 2) }}</td>
        </tr>
    </table>

    @if($sale->special_terms)
        <div class="section">
            <div class="label">Special terms</div>
            <p>{{ $sale->special_terms }}</p>
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <div>Authorized signatory</div>
            </td>
            <td>
                <div class="line"></div>
                <div>Buyer acknowledgement</div>
            </td>
        </tr>
    </table>

    <p class="footer">
        Generated {{ now()->format('Y-m-d H:i') }} · Document ID {{ $sale->id }}
    </p>
</body>
</html>
