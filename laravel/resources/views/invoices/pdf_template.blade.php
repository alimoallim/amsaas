<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #555; font-size: 11px; }
        .grid { width: 100%; margin-top: 20px; }
        .grid td { vertical-align: top; width: 50%; padding: 0 8px 0 0; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.lines th, table.lines td { border: 1px solid #ccc; padding: 6px 8px; }
        table.lines th { background: #f3f4f6; font-weight: bold; }
        .right { text-align: right; }
        .totals { margin-top: 12px; width: 45%; float: right; }
        .totals td { padding: 4px 0; }
        .totals .grand { font-weight: bold; font-size: 13px; border-top: 1px solid #333; padding-top: 6px; }
        .footer { clear: both; margin-top: 40px; font-size: 10px; color: #444; }
    </style>
</head>
<body>
    <table class="grid">
        <tr>
            <td>
                <h1>{{ $company?->name ?? 'Property Management' }}</h1>
                <p class="muted">
                    @if($company?->address){{ $company->address }}<br>@endif
                    @if($company?->city){{ $company->city }}@endif
                    @if($company?->country), {{ $company->country }}@endif
                    @if($company?->email)<br>{{ $company->email }}@endif
                </p>
            </td>
            <td class="right">
                <strong style="font-size: 16px;">INVOICE</strong><br>
                <span class="muted">{{ $invoice->invoice_number }}</span><br>
                Period: {{ sprintf('%02d', $invoice->billing_month) }}/{{ $invoice->billing_year }}<br>
                Issue: {{ $invoice->issue_date?->format('Y-m-d') }}<br>
                Due: {{ $invoice->due_date?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <strong>Bill to</strong><br>
                {{ $tenantName ?? 'Tenant' }}<br>
                Unit {{ $invoice->apartment?->unit_number ?? '—' }}<br>
                {{ $invoice->apartment?->building?->name ?? '' }}
            </td>
            <td class="right">
                <strong>Balance due</strong><br>
                <span style="font-size: 18px;">{{ number_format((float) $invoice->balance_due, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lineItems as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ number_format((float) $line->quantity, 3) }}</td>
                    <td class="right">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $line->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>Rent</td>
                    <td class="right">1</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_rent, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_rent, 2) }}</td>
                </tr>
                @if((float) $invoice->subtotal_utilities > 0)
                <tr>
                    <td>Utilities</td>
                    <td class="right">1</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_utilities, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_utilities, 2) }}</td>
                </tr>
                @endif
                @if((float) $invoice->subtotal_services > 0)
                <tr>
                    <td>Services</td>
                    <td class="right">1</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_services, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->subtotal_services, 2) }}</td>
                </tr>
                @endif
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal rent</td>
            <td class="right">{{ number_format((float) $invoice->subtotal_rent, 2) }}</td>
        </tr>
        <tr>
            <td>Utilities</td>
            <td class="right">{{ number_format((float) $invoice->subtotal_utilities, 2) }}</td>
        </tr>
        <tr>
            <td>Services</td>
            <td class="right">{{ number_format((float) $invoice->subtotal_services, 2) }}</td>
        </tr>
        @if((float) $invoice->discount_amount > 0)
        <tr>
            <td>Discount</td>
            <td class="right">−{{ number_format((float) $invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td>Paid</td>
            <td class="right">{{ number_format((float) $invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Balance due</td>
            <td class="right">{{ number_format((float) $invoice->balance_due, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <strong>Payment instructions</strong><br>
        Pay by bank transfer or mobile money (EVC Plus). Quote invoice number {{ $invoice->invoice_number }} as reference.
        Contact the management office for payment confirmation.
    </div>
</body>
</html>
