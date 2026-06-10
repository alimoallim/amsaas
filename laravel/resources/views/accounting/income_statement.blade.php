<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Income Statement</title>
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
        .net-income { font-size: 14px; font-weight: bold; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Income Statement</h1>
    <div class="meta">
        {{ $report['company']['name'] ?? 'Company' }}<br>
        Period: {{ $report['period']['from'] }} to {{ $report['period']['to'] }}<br>
        Currency: {{ $report['company']['currency_code'] ?? 'USD' }}
    </div>

    <div class="section-title">Revenue</div>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['sections']['revenue']['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No revenue in this period.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Total revenue</td>
                <td class="amount">{{ number_format($report['sections']['revenue']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Expenses</div>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['sections']['expenses']['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="amount">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No expenses in this period.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Total expenses</td>
                <td class="amount">{{ number_format($report['sections']['expenses']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-income">
        Net income: {{ number_format($report['totals']['net_income'], 2) }}
    </div>
</body>
</html>
