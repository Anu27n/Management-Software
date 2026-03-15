<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Payments</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { margin: 0 0 5px; }
        .subtitle { color: #666; margin-bottom: 15px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ config('app.name') }}</h2>
    <div class="subtitle">Fee Payments Report — Generated {{ now()->format('M d, Y h:i A') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Receipt</th><th>Student</th><th>Category</th>
                <th>Amount</th><th>Discount</th><th>Fine</th><th>Date</th><th>Method</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($payments as $i => $p)
            @php $total += $p->amount_paid; @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $p->receipt_no }}</td>
                <td>{{ $p->student->full_name ?? '-' }}</td>
                <td>{{ $p->feeStructure->feeCategory->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($p->amount_paid, 2) }}</td>
                <td class="text-right">{{ number_format($p->discount, 2) }}</td>
                <td class="text-right">{{ number_format($p->fine, 2) }}</td>
                <td>{{ $p->payment_date?->format('Y-m-d') }}</td>
                <td>{{ ucfirst($p->payment_method) }}</td>
                <td>{{ ucfirst($p->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="4"><strong>Total</strong></td><td class="text-right"><strong>{{ number_format($total, 2) }}</strong></td><td colspan="5"></td></tr>
        </tfoot>
    </table>
</body>
</html>
