<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { padding: 6px 8px; text-align: left; }
        th { border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #64748b; }
        td.amount { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        tr.total td { border-top: 2px solid #cbd5e1; font-weight: bold; }
        .section-title { font-size: 13px; font-weight: bold; margin: 12px 0 6px; }
        .equation { margin-top: 12px; font-size: 13px; }
    </style>
</head>
<body>
    <h1>Balance Sheet</h1>
    <div class="meta">
        {{ $report['company']['name'] ?? 'Company' }}<br>
        As of: {{ $report['as_of'] }}<br>
        Currency: {{ $report['company']['currency_code'] ?? 'USD' }}
    </div>

    <div class="section-title">Assets</div>
    <table>
        <thead><tr><th>Code</th><th>Account</th><th class="amount">Balance</th></tr></thead>
        <tbody>
            @forelse ($report['sections']['assets']['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No asset balances.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Total assets</td>
                <td class="amount">{{ number_format($report['sections']['assets']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Liabilities</div>
    <table>
        <thead><tr><th>Code</th><th>Account</th><th class="amount">Balance</th></tr></thead>
        <tbody>
            @forelse ($report['sections']['liabilities']['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No liability balances.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Total liabilities</td>
                <td class="amount">{{ number_format($report['sections']['liabilities']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Equity</div>
    <table>
        <thead><tr><th>Code</th><th>Account</th><th class="amount">Balance</th></tr></thead>
        <tbody>
            @forelse ($report['sections']['equity']['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No equity balances.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Total equity</td>
                <td class="amount">{{ number_format($report['sections']['equity']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="equation">
        Liabilities + Equity: {{ number_format($report['totals']['liabilities_and_equity'], 2) }}<br>
        Status: {{ $report['totals']['balanced'] ? 'Balanced' : 'Out of balance' }}
        @if (! $report['totals']['balanced'])
            (variance {{ number_format($report['totals']['variance'], 2) }})
        @endif
    </div>
</body>
</html>
