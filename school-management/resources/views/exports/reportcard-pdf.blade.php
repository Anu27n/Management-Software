<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        @page {
            margin: 11mm 10mm 11mm 10mm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Serif, serif;
            font-size: 8.6px;
            color: {{ $siteSettings->page_text_color ?? '#1a0a00' }};
            margin: 0;
            line-height: 1.2;
        }
        .page {
            width: 100%;
            border: 2.5px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 4px;
        }
        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .header {
            text-align: center;
            margin-bottom: 0;
            border: 2px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 6px 8px 0;
            background: #ffffff;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .header::after {
            display: none;
        }
        .school-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: {{ $siteSettings->school_name_color ?? '#8b0000' }};
            font-family: DejaVu Serif, serif;
            line-height: 1.1;
            margin-top: 2px;
        }
        .report-title {
            margin-top: 3px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: {{ $siteSettings->title_text_color ?? '#ffffff' }};
            background: {{ $siteSettings->title_bar_color ?? '#b8860b' }};
            padding: 3px 0;
            letter-spacing: 0.4px;
        }
        .report-subtitle {
            margin-top: 1px;
            font-size: 8px;
            color: {{ $siteSettings->border_color ?? '#4a2800' }};
            font-style: italic;
            line-height: 1.3;
        }
        .session-line {
            display: none;
        }
        .meta-table,
        .section-table,
        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table {
            margin-bottom: 0;
            page-break-inside: avoid;
            break-inside: avoid;
            border: 2px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            border-top: none;
        }
        .meta-table td {
            border: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 3px 5px;
            vertical-align: top;
            font-size: 8px;
        }
        .meta-label {
            font-weight: 700;
            background: #ffffff;
            color: {{ $siteSettings->page_text_color ?? '#1a0a00' }};
            white-space: nowrap;
        }
        .meta-value {
            font-weight: 400;
        }
        .marks-table {
            margin-bottom: 0;
            page-break-inside: avoid;
            break-inside: avoid;
            border: 2px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            border-top: none;
        }
        .marks-table th,
        .marks-table td {
            border: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 2.5px 3px;
            text-align: center;
            vertical-align: middle;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .marks-table th {
            background: {{ $siteSettings->header_fill_color ?? '#e8d5a3' }};
            font-weight: 700;
            font-size: 8px;
            text-transform: uppercase;
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
            page-break-after: avoid;
            break-after: avoid;
        }
        .marks-table tbody tr:nth-child(even) td {
            background: #fffdf5;
        }
        .marks-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .marks-table .subject-col {
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.8px;
        }
        .marks-table .group-head {
            font-size: 8px;
            font-weight: 700;
            background: {{ $siteSettings->title_bar_color ?? '#c8a45a' }};
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
            text-transform: uppercase;
        }
        .marks-table .sub-head {
            font-size: 7.5px;
            font-weight: 700;
            background: {{ $siteSettings->header_fill_color ?? '#e8d5a3' }};
        }
        .marks-table .summary-row td {
            font-weight: 700;
            background: #f5ecd0;
            font-size: 8.5px;
            border-top: 1.5px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
        }
        .section-row {
            margin-bottom: 0;
        }
        .section-row:last-of-type {
            margin-bottom: 0;
        }
        .split {
            width: 100%;
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
            width: 49%;
        }
        .col-48.right {
            float: right;
        }
        .section-card {
            border: none;
            margin-bottom: 0;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
            background: #ffffff;
            border-right: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
        }
        .col-48.right .section-card { border-right: none; }
        .section-title {
            background: {{ $siteSettings->header_fill_color ?? '#e8d5a3' }};
            padding: 3px 6px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
            border-bottom: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            border-top: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            page-break-after: avoid;
            break-after: avoid;
        }
        .section-body {
            padding: 0;
        }
        .section-table th,
        .section-table td {
            border: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 2px 4px;
            vertical-align: middle;
            page-break-inside: avoid;
            break-inside: avoid;
            font-size: 8px;
        }
        .section-table th {
            background: {{ $siteSettings->header_fill_color ?? '#e8d5a3' }};
            font-weight: 700;
            text-align: center;
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
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
            text-transform: uppercase;
            font-size: 7.6px;
        }
        .remarks-block {
            padding: 5px 6px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .remarks-line {
            margin-bottom: 4px;
            font-size: 8px;
        }
        .remarks-line:last-child {
            margin-bottom: 0;
        }
        .remarks-line strong {
            display: inline-block;
            min-width: 76px;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            border: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            padding: 3px 5px;
            font-size: 8px;
        }
        .summary-grid .label {
            font-weight: 700;
            background: #fffdf5;
            width: 45%;
            text-transform: uppercase;
            font-size: 7.8px;
        }
        .summary-grid .highlight {
            font-weight: 700;
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
        }
        .result-block {
            border: 2px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            border-top: none;
            padding: 0;
            margin-top: 0;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
            background: #ffffff;
        }
        .result-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .result-grid td {
            vertical-align: top;
            padding: 6px 8px;
            font-size: 8px;
        }
        .result-grid td + td {
            border-left: 1px solid {{ $siteSettings->border_color ?? '#7a4a00' }};
            width: 42%;
        }
        .result-line {
            margin-bottom: 3px;
        }
        .result-line:last-child {
            margin-bottom: 0;
        }
        .result-emphasis {
            font-size: 9px;
            font-weight: 700;
            color: {{ $siteSettings->border_color ?? '#3b1f00' }};
        }
        .signatures {
            width: 100%;
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
            padding: 16px 4px 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid {{ $siteSettings->page_text_color ?? '#1a0a00' }};
            margin: 0 10px 4px;
        }
        .signature-label {
            font-weight: 700;
            font-size: 8px;
        }
        .value-normal {
            font-weight: 400;
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
    $title = \App\Support\ReportTemplateRegistry::title($selectedExam->resolved_template);
    $grandTotal = $selectedExam->resolved_template === 'semester_2'
        ? (float) $totals['yearly_grand_total']
        : (float) $totals['first_semester_total'];
    $schoolName = $siteSettings->school_name ?: config('app.name');
    $schoolSubtitle = $siteSettings->address ?: 'School Address';
    $contactLine = trim(collect([$siteSettings->contact_number, $siteSettings->contact_email])->filter()->implode(' | '));
    $sessionLabel = $student->academicYear?->name ?: 'Academic Session';
    $logoPath = $siteSettings->logo_path ? storage_path('app/public/' . $siteSettings->logo_path) : null;
    $logoDataUri = null;

    if ($logoPath && file_exists($logoPath)) {
        $mimeType = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
        $logoContents = @file_get_contents($logoPath);

        if ($logoContents !== false) {
            $logoDataUri = 'data:' . ($mimeType ?: 'image/png') . ';base64,' . base64_encode($logoContents);
        }
    }
@endphp

<div class="page">
    <div class="header keep-together">
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; padding-bottom: 4px;">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="School Logo" style="max-height: 48px; width: auto; display: block; mix-blend-mode: multiply;">
            @endif

            <div style="text-align: center;">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="report-subtitle">{{ $schoolSubtitle }}</div>
                @if($contactLine !== '')
                    <div class="report-subtitle">{{ $contactLine }}</div>
                @endif
            </div>
        </div>
        <div class="report-title">{{ $title }} &mdash; Session ( {{ $sessionLabel }} )</div>
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

    <div class="section-row keep-together">
        <div class="split">
            <div class="col-48">
                <div class="section-card keep-together" style="margin-bottom: 6px;">
                    <div class="section-title">Summary</div>
                    <table class="summary-grid">
                        <tr>
                            <td class="label">Grand Total</td>
                            <td class="highlight">{{ number_format((float) $grandTotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Percentage</td>
                            <td class="highlight">{{ number_format((float) $totals['percentage'], 2) }}%</td>
                        </tr>
                        <tr>
                            <td class="label">Rank in Class</td>
                            <td class="highlight">{{ $marksheet['rank'] ? '#' . $marksheet['rank'] : '-' }}</td>
                        </tr>
                    </table>
                </div>
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
                <div class="section-card keep-together" style="margin-bottom: 6px;">
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
    </div>

    <div class="section-row keep-together">
        <div class="result-block">
            <div class="section-title">Final Result</div>
            <table class="result-grid">
                <tr>
                    <td>
                        <div class="result-line result-emphasis"><strong>Final Status:</strong> {{ $marksheet['result_label'] }}</div>
                        <div class="result-line"><strong>Promoted to Class:</strong> {{ $secondReport?->final_result === 'promoted' ? ($secondReport?->promotedToClass?->name ?? '-') : '-' }}</div>
                        <div class="result-line"><strong>Detained in Class:</strong> {{ $secondReport?->final_result === 'detained' ? ($secondReport?->promotedToClass?->name ?? '-') : '-' }}</div>
                        <div class="result-line"><strong>School Reopens On:</strong> {{ \App\Support\DateFormatter::display($secondReport?->school_reopens_on) }}</div>
                        <div class="result-line"><strong>Timings:</strong> {{ $secondReport?->school_timings ?: '-' }}</div>
                    </td>
                    <td>
                        <table class="signatures">
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
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
