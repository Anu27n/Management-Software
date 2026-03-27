<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { margin: 0 0 3px; }
        h4 { margin: 5px 0 15px; color: #555; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 8px; }
        .label { color: #888; width: 120px; }
        table.marks { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.marks th, table.marks td { border: 1px solid #ddd; padding: 6px 8px; text-align: center; }
        table.marks th { background: #f5f5f5; }
        table.marks td:nth-child(2) { text-align: left; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .grade-a { color: #28a745; font-weight: bold; }
        .grade-f { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h2 style="text-align:center">{{ config('app.name') }}</h2>
    <h4 style="text-align:center">{{ $exam->name }} — Report Card</h4>

    <table class="info-table">
        <tr>
            <td class="label">Name</td><td><strong>{{ $student->full_name }}</strong></td>
            <td class="label">Class</td><td>{{ $student->schoolClass->name ?? '-' }} — {{ $student->section->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Admission No.</td><td>{{ $student->admission_no }}</td>
            <td class="label">Academic Year</td><td>{{ $student->academicYear->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="marks">
        <thead>
            <tr><th>#</th><th>Subject</th><th>Marks Obtained</th><th>Max Marks</th><th>Percentage</th><th>Grade</th></tr>
        </thead>
        <tbody>
            @php $totalObtained = 0; $totalMax = 0; @endphp
            @foreach($results as $i => $r)
            @php $totalObtained += $r->marks_obtained; $totalMax += $r->max_marks; @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $r->subject->name ?? 'N/A' }}</td>
                <td>{{ $r->marks_obtained }}</td>
                <td>{{ $r->max_marks }}</td>
                <td>{{ number_format($r->percentage, 1) }}%</td>
                <td class="{{ in_array($r->grade, ['A+','A']) ? 'grade-a' : ($r->grade == 'F' ? 'grade-f' : '') }}">{{ $r->grade }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php $overallPct = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0; @endphp
            <tr class="total-row">
                <td colspan="2">Total</td>
                <td>{{ $totalObtained }}</td>
                <td>{{ $totalMax }}</td>
                <td>{{ number_format($overallPct, 1) }}%</td>
                <td>{{ $overallPct >= 90 ? 'A+' : ($overallPct >= 80 ? 'A' : ($overallPct >= 70 ? 'B' : ($overallPct >= 60 ? 'C' : ($overallPct >= 50 ? 'D' : 'F')))) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>
    <p style="color:#888; font-size:10px; text-align:center">Generated on {{ now()->format('M d, Y') }}</p>
</body>
</html>
