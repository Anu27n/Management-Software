<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students List</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { margin: 0 0 5px; }
        .subtitle { color: #666; margin-bottom: 15px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
        .active { background: #d4edda; color: #155724; }
        .inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>{{ config('app.name') }}</h2>
    <div class="subtitle">Students List — Generated {{ now()->format('M d, Y h:i A') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Admission No</th><th>Name</th><th>Gender</th><th>DOB</th>
                <th>Class</th><th>Section</th><th>Father</th><th>Phone</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $s)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $s->admission_no }}</td>
                <td>{{ $s->full_name }}</td>
                <td>{{ ucfirst($s->gender) }}</td>
                <td>{{ $s->date_of_birth?->format('Y-m-d') }}</td>
                <td>{{ $s->schoolClass->name ?? '-' }}</td>
                <td>{{ $s->section->name ?? '-' }}</td>
                <td>{{ $s->father_name }}</td>
                <td>{{ $s->phone }}</td>
                <td><span class="badge {{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
