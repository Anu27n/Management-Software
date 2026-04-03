<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        @page {
            margin: 18mm 12mm 16mm 12mm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
            line-height: 1.35;
        }
        .page {
            width: 100%;
        }
        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .school-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .report-title {
            margin-top: 3px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .report-subtitle {
            margin-top: 2px;
            font-size: 9px;
            color: #4b5563;
        }
        .meta-table,
        .section-table,
        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table {
            margin-bottom: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .meta-table td {
            border: 1px solid #1f2937;
            padding: 5px 7px;
            vertical-align: top;
        }
        .meta-label {
            width: 18%;
            font-weight: 700;
            background: #f3f4f6;
        }
        .meta-value {
            width: 32%;
        }
        .marks-table {
            margin-bottom: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .marks-table th,
        .marks-table td {
            border: 1px solid #111827;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .marks-table th {
            background: #eef2f7;
            font-weight: 700;
            page-break-after: avoid;
            break-after: avoid;
        }
        .marks-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .marks-table .subject-col {
            text-align: left;
            width: 25%;
            font-weight: 700;
        }
        .marks-table .group-head {
            font-size: 10px;
            letter-spacing: 0.2px;
        }
        .marks-table .sub-head {
            font-size: 9px;
            font-weight: 700;
        }
        .marks-table .summary-row td {
            font-weight: 700;
            background: #f9fafb;
        }
        .split {
            width: 100%;
            margin-bottom: 10px;
            page-break-before: auto;
            break-before: auto;
        }
        .split::after {
            content: '';
            display: table;
            clear: both;
        }
        .col-48 {
            float: left;
            width: 48.5%;
        }
        .col-48.right {
            float: right;
        }
        .section-card {
            border: 1px solid #111827;
            margin-bottom: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
        }
        .section-title {
            background: #eef2f7;
            padding: 6px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            page-break-after: avoid;
            break-after: avoid;
        }
        .section-body {
            padding: 0;
        }
        .section-table th,
        .section-table td {
            border: 1px solid #111827;
            padding: 5px 6px;
            vertical-align: middle;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .section-table th {
            background: #f9fafb;
            font-weight: 700;
            text-align: center;
            page-break-after: avoid;
            break-after: avoid;
        }
        .section-table {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .section-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .section-table td:first-child {
            font-weight: 600;
        }
        .remarks-block {
            padding: 8px 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .remarks-line {
            margin-bottom: 6px;
        }
        .remarks-line strong {
            display: inline-block;
            min-width: 110px;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            border: 1px solid #111827;
            padding: 6px 8px;
        }
        .summary-grid .label {
            font-weight: 700;
            background: #f9fafb;
            width: 45%;
        }
        .result-block {
            border: 1px solid #111827;
            padding: 8px 10px;
            margin-top: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
        }
        .result-line {
            margin-bottom: 5px;
        }
        .result-line:last-child {
            margin-bottom: 0;
        }
        .signatures {
            width: 100%;
            margin-top: 20px;
            table-layout: fixed;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 26px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid #111827;
            margin: 0 18px 6px;
        }
        .signature-label {
            font-weight: 700;
            font-size: 10px;
        }
        .muted {
            color: #4b5563;
        }
    </style>
</head>
<body>
@php
    $student = $marksheet['student'];
    $selectedExam = $marksheet['selected_exam'];
    $subjectRows = $marksheet['subject_rows'];
    $gradingRows = $marksheet['grading_rows'];
    $totals = $marksheet['totals'];
    $firstReport = $marksheet['first_report'];
    $secondReport = $marksheet['second_report'];
    $personalAttributes = $marksheet['personal_attributes'];
    $attributeFields = [
        'discipline_conduct' => 'Discipline & Conduct',
        'punctuality' => 'Punctuality',
        'self_confidence' => 'Self Confidence',
        'creativity' => 'Creativity',
        'spoken_english' => 'Spoken English',
        'personal_hygiene' => 'Personal Hygiene',
    ];
    $title = $selectedExam->resolved_template === 'semester_2' ? 'Final / 2nd Semester Marksheet' : '1st Semester Marksheet';
    $grandTotal = $selectedExam->resolved_template === 'semester_2'
        ? (float) $totals['yearly_grand_total']
        : (float) $totals['first_semester_total'];
@endphp

<div class="page">
    <div class="header keep-together">
        <div class="school-name">{{ config('app.name') }}</div>
        <div class="report-title">{{ $title }}</div>
        <div class="report-subtitle">Academic Progress Report</div>
    </div>

    <table class="meta-table keep-together">
        <tr>
            <td class="meta-label">Student Name</td>
            <td class="meta-value">{{ $student->full_name }}</td>
            <td class="meta-label">Admission No.</td>
            <td class="meta-value">{{ $student->admission_no }}</td>
        </tr>
        <tr>
            <td class="meta-label">Class & Section</td>
            <td class="meta-value">{{ $student->schoolClass?->name }} - {{ $student->section?->name }}</td>
            <td class="meta-label">Academic Year</td>
            <td class="meta-value">{{ $student->academicYear?->name }}</td>
        </tr>
    </table>

    <table class="marks-table keep-together">
        <thead>
            <tr>
                <th rowspan="2" class="subject-col">Subject</th>
                <th colspan="3" class="group-head">1st Semester</th>
                <th colspan="3" class="group-head">2nd Semester</th>
                <th rowspan="2" class="group-head">Yearly Average %</th>
            </tr>
            <tr>
                <th class="sub-head">Unit Test (20)</th>
                <th class="sub-head">Half Yearly (80)</th>
                <th class="sub-head">Total (100)</th>
                <th class="sub-head">Unit Test (20)</th>
                <th class="sub-head">Final Exam (80)</th>
                <th class="sub-head">Total (100)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjectRows as $row)
                <tr>
                    <td class="subject-col">{{ $row['subject']->name }}</td>
                    <td>{{ number_format((float) ($row['first']?->unit_test_marks ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($row['first']?->main_exam_marks ?? 0), 2) }}</td>
                    <td>{{ number_format((float) $row['first_total'], 2) }}</td>
                    <td>{{ number_format((float) ($row['second']?->unit_test_marks ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($row['second']?->main_exam_marks ?? 0), 2) }}</td>
                    <td>{{ number_format((float) $row['second_total'], 2) }}</td>
                    <td>{{ number_format((float) ($row['yearly_average_percentage'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <td class="subject-col">Grand Total</td>
                <td></td>
                <td></td>
                <td>{{ number_format((float) $totals['first_semester_total'], 2) }}</td>
                <td></td>
                <td></td>
                <td>{{ number_format((float) $totals['second_semester_total'], 2) }}</td>
                <td>{{ number_format((float) $grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="split keep-together">
        <div class="col-48">
            <div class="section-card keep-together">
                <div class="section-title">Summary</div>
                <table class="summary-grid">
                    <tr>
                        <td class="label">Grand Total</td>
                        <td>{{ number_format((float) $grandTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Percentage</td>
                        <td>{{ number_format((float) $totals['percentage'], 2) }}%</td>
                    </tr>
                    <tr>
                        <td class="label">Rank in Class</td>
                        <td>{{ $marksheet['rank'] ? '#' . $marksheet['rank'] : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="col-48 right">
            <div class="section-card keep-together">
                <div class="section-title">Grading Subjects</div>
                <table class="section-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>1st Sem</th>
                            <th>2nd Sem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradingRows as $row)
                            <tr>
                                <td>{{ $row['subject']->name }}</td>
                                <td style="text-align:center;">{{ $row['first_grade'] ?: '-' }}</td>
                                <td style="text-align:center;">{{ $row['second_grade'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="split keep-together">
        <div class="col-48">
            <div class="section-card keep-together">
                <div class="section-title">Personal Attributes</div>
                <table class="section-table">
                    <thead>
                        <tr>
                            <th>Attribute</th>
                            <th>1st Sem</th>
                            <th>2nd Sem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attributeFields as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td style="text-align:center;">{{ $personalAttributes['first'][$key] ?? '-' }}</td>
                                <td style="text-align:center;">{{ $personalAttributes['second'][$key] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-48 right">
            <div class="section-card keep-together">
                <div class="section-title">Remarks</div>
                <div class="remarks-block">
                    <div class="remarks-line"><strong>1st Unit Test:</strong> {{ $firstReport?->remarks_unit_test ?: '-' }}</div>
                    <div class="remarks-line"><strong>Half Yearly:</strong> {{ $firstReport?->remarks_main_exam ?: '-' }}</div>
                    <div class="remarks-line"><strong>2nd Unit Test:</strong> {{ $secondReport?->remarks_unit_test ?: '-' }}</div>
                    <div class="remarks-line"><strong>Final Exams:</strong> {{ $secondReport?->remarks_main_exam ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="result-block keep-together">
        <div class="section-title" style="margin: -8px -10px 8px -10px; border-bottom: 1px solid #111827;">Final Result</div>
        <div class="result-line"><strong>Promoted to Class:</strong> {{ $secondReport?->final_result === 'promoted' ? ($secondReport?->promotedToClass?->name ?? '-') : '-' }}</div>
        <div class="result-line"><strong>Detained in Class:</strong> {{ $secondReport?->final_result === 'detained' ? ($secondReport?->promotedToClass?->name ?? '-') : '-' }}</div>
        <div class="result-line"><strong>School Reopens On:</strong> {{ optional($secondReport?->school_reopens_on)->format('M d, Y') ?? '-' }}</div>
        <div class="result-line"><strong>Timings:</strong> {{ $secondReport?->school_timings ?: '-' }}</div>
    </div>

    <table class="signatures keep-together">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $secondReport?->class_teacher_signature ?? $firstReport?->class_teacher_signature ?? 'Class Teacher' }}</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $secondReport?->principal_signature ?? $firstReport?->principal_signature ?? 'Principal' }}</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $secondReport?->parent_signature ?? $firstReport?->parent_signature ?? 'Parent' }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
