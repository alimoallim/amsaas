<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Instalment Schedule — {{ $agreement->agreement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Instalment Payment Schedule</h1>
    <p>{{ $agreement->agreement_number }} · {{ $agreement->buyer?->full_name ?? 'Buyer' }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Due date</th>
                <th class="right">Amount</th>
                <th class="right">Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($schedule as $row)
            <tr>
                <td>{{ $row->installment_number }}</td>
                <td>{{ $row->due_date?->format('Y-m-d') }}</td>
                <td class="right">{{ number_format((float) $row->amount, 2) }}</td>
                <td class="right">{{ number_format((float) $row->paid_amount, 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $row->effectiveStatus())) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
