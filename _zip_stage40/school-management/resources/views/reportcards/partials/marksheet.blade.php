@php
    $forPdf = $forPdf ?? false;
    $student = $marksheet['student'];
    $profile = $student->profile;
    $selectedExam = $marksheet['selected_exam'];
    $subjectRows = $marksheet['subject_rows'];
    $gradingRows = $marksheet['grading_rows'];
    $totals = $marksheet['totals'];
    $firstReport = $marksheet['first_report'];
    $secondReport = $marksheet['second_report'];
    $personalAttributes = $marksheet['personal_attributes'];
    $templateMeta = \App\Support\ReportTemplateRegistry::meta($selectedExam->resolved_template);
    $attributeFields = [
        'discipline_conduct' => 'Discipline & Conduct',
        'punctuality' => 'Punctuality',
        'self_confidence' => 'Self Confidence',
        'creativity' => 'Creativity',
        'spoken_english' => 'Spoken English',
        'personal_hygiene' => 'Personal Hygiene',
    ];
    $gradeScale = [
        'A+' => '90% & Above',
        'A' => '80% - 89%',
        'B+' => '70% - 79%',
        'B' => '60% - 69%',
        'C' => '50% - 59%',
        'D' => '40% - 49%',
        'F' => 'Below 40%',
    ];
    $schoolName = $siteSettings->school_name ?: config('app.name');
    $schoolSubtitle = $siteSettings->address ?: 'School Address';
    $contactLine = trim(collect([$siteSettings->contact_number, $siteSettings->contact_email])->filter()->implode(' | '));
    $activeReport = $selectedExam->resolved_template === 'semester_2' ? ($secondReport ?: $firstReport) : $firstReport;
    $activeGrades = $gradingRows->map(function ($row) use ($selectedExam) {
        return [
            'subject' => $row['subject']->name,
            'grade' => $selectedExam->resolved_template === 'semester_2'
                ? ($row['second_grade'] ?: '-')
                : ($row['first_grade'] ?: '-'),
        ];
    })->filter(fn ($row) => $row['subject'] !== '');
    $activeAttributes = collect($attributeFields)->map(function ($label, $key) use ($personalAttributes, $selectedExam) {
        return [
            'label' => $label,
            'grade' => $selectedExam->resolved_template === 'semester_2'
                ? ($personalAttributes['second'][$key] ?? '-')
                : ($personalAttributes['first'][$key] ?? '-'),
        ];
    });
    $remarksLines = collect([
        $activeReport?->remarks_unit_test ? 'Unit Test: ' . $activeReport->remarks_unit_test : null,
        $activeReport?->remarks_main_exam ? 'Main Exam: ' . $activeReport->remarks_main_exam : null,
        $selectedExam->resolved_template === 'semester_2' && ($secondReport?->final_result || $marksheet['result_label'])
            ? 'Result: ' . ($marksheet['result_label'] ?: ucfirst((string) $secondReport?->final_result))
            : null,
        $selectedExam->resolved_template === 'semester_2' && $secondReport?->promotedToClass?->name
            ? (($secondReport?->final_result === 'detained' ? 'Detained in' : 'Promoted to') . ': ' . $secondReport->promotedToClass->name)
            : null,
        $selectedExam->resolved_template === 'semester_2' && filled($secondReport?->school_timings)
            ? 'Timings: ' . $secondReport->school_timings
            : null,
        $selectedExam->resolved_template === 'semester_2' && filled($secondReport?->school_reopens_on)
            ? 'Reopens On: ' . \App\Support\DateFormatter::display($secondReport?->school_reopens_on)
            : null,
    ])->filter()->values();
    $hasEnteredMarks = $subjectRows->contains(function (array $row) use ($selectedExam) {
        return $selectedExam->resolved_template === 'semester_2'
            ? ($row['first'] !== null || $row['second'] !== null)
            : $row['first'] !== null;
    });
    $displayMarks = function ($value, $record) {
        return $record ? number_format((float) $value, 2) : '-';
    };
    $displayTotal = function ($value) use ($hasEnteredMarks) {
        return $hasEnteredMarks ? number_format((float) $value, 2) : '-';
    };
    $displayPercentage = $hasEnteredMarks ? number_format((float) $totals['percentage'], 2) . '%' : '-';
    $displayRank = $hasEnteredMarks && $marksheet['rank'] ? '#' . $marksheet['rank'] : '-';
@endphp

<div class="progress-sheet {{ $forPdf ? 'pdf-mode' : '' }}">
    <div class="progress-sheet__frame">
        <div class="progress-sheet__header">
            <div class="progress-sheet__brand">
                @if(!empty($siteSettings->logo_url))
                    <img src="{{ $siteSettings->logo_url }}" alt="School Logo" class="progress-sheet__logo">
                @endif
                <div class="progress-sheet__heading">
                    <div class="progress-sheet__school">{{ $schoolName }}</div>
                    <div class="progress-sheet__subhead">{{ $schoolSubtitle }}</div>
                    @if($contactLine !== '')
                        <div class="progress-sheet__subhead">{{ $contactLine }}</div>
                    @endif
                </div>
            </div>
            <div class="progress-sheet__title">
                {{ strtoupper($templateMeta['title']) }} - SESSION {{ $student->academicYear?->name ?? '-' }}
            </div>
        </div>

        <table class="progress-sheet__meta">
            <tr>
                <td><strong>Name</strong> : {{ $student->full_name ?: '-' }}</td>
                <td><strong>Admission No.</strong> : {{ $student->admission_no ?: '-' }}</td>
                <td><strong>Father Name</strong> : {{ $student->father_name ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>House</strong> : {{ $profile?->house ?: '-' }}</td>
                <td><strong>D.O.B.</strong> : {{ \App\Support\DateFormatter::display($student->date_of_birth) }}</td>
                <td><strong>Class-Sec</strong> : {{ trim(($student->schoolClass?->name ?? '-') . ' - ' . ($student->section?->name ?? '-')) }}</td>
            </tr>
        </table>

        @if($forPdf)
            <table class="progress-sheet__layout-table progress-sheet__layout-table--pdf">
                <tr>
                    <td class="progress-sheet__layout-main">
                        <div class="progress-sheet__panel">
                            <div class="progress-sheet__section-title">Scholastic Areas</div>
                            <table class="progress-sheet__table {{ $selectedExam->resolved_template === 'semester_2' ? 'progress-sheet__table--semester-two' : 'progress-sheet__table--single-term' }}">
                                @if($selectedExam->resolved_template === 'semester_2')
                                    <colgroup>
                                        <col style="width: 34%;">
                                        <col style="width: 9%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 17%;">
                                    </colgroup>
                                @else
                                    <colgroup>
                                        <col style="width: 40%;">
                                        <col style="width: 10%;">
                                        <col style="width: 12%;">
                                        <col style="width: 12%;">
                                        <col style="width: 12%;">
                                        <col style="width: 14%;">
                                    </colgroup>
                                @endif
                                <thead>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <tr>
                                            <th class="subject-col">Subjects</th>
                                            <th>Max Marks</th>
                                            <th>1st Sem</th>
                                            <th>Unit II</th>
                                            <th>Final</th>
                                            <th>Total</th>
                                            <th>Average</th>
                                        </tr>
                                    @else
                                        <tr>
                                            <th class="subject-col">Subjects</th>
                                            <th>Max Marks</th>
                                            <th>Unit Test</th>
                                            <th>Main Exam</th>
                                            <th>Total</th>
                                            <th>Grade</th>
                                        </tr>
                                    @endif
                                </thead>
                                <tbody>
                                    @forelse($subjectRows as $row)
                                        @php
                                            $currentRecord = $selectedExam->resolved_template === 'semester_2' ? $row['second'] : $row['first'];
                                            $currentTotal = $selectedExam->resolved_template === 'semester_2' ? $row['second_total'] : $row['first_total'];
                                            $currentGrade = $currentRecord
                                                ? match (true) {
                                                    $currentTotal >= 90 => 'A+',
                                                    $currentTotal >= 80 => 'A',
                                                    $currentTotal >= 70 => 'B+',
                                                    $currentTotal >= 60 => 'B',
                                                    $currentTotal >= 50 => 'C',
                                                    $currentTotal >= 40 => 'D',
                                                    default => 'F',
                                                }
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td class="subject-col">{{ $row['subject']->name }}</td>
                                            <td>100</td>
                                            @if($selectedExam->resolved_template === 'semester_2')
                                                <td>{{ $displayMarks($row['first_total'], $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['second']?->unit_test_marks, $row['second']) }}</td>
                                                <td>{{ $displayMarks($row['second']?->main_exam_marks, $row['second']) }}</td>
                                                <td>{{ $displayMarks($row['second_total'], $row['second']) }}</td>
                                                <td>{{ ($row['first'] || $row['second']) ? number_format((float) $row['yearly_average'], 2) : '-' }}</td>
                                            @else
                                                <td>{{ $displayMarks($row['first']?->unit_test_marks, $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['first']?->main_exam_marks, $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['first_total'], $row['first']) }}</td>
                                                <td>{{ $currentGrade }}</td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $selectedExam->resolved_template === 'semester_2' ? 7 : 6 }}">No subjects available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <tr>
                                            <td class="subject-col">Grand Total</td>
                                            <td>{{ $totals['max_marks'] }}</td>
                                            <td>{{ $displayTotal($totals['first_semester_total']) }}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $displayTotal($totals['second_semester_total']) }}</td>
                                            <td>{{ $displayTotal($totals['yearly_grand_total']) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Percentage</td>
                                            <td colspan="6">{{ $displayPercentage }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Rank in Class</td>
                                            <td colspan="6">{{ $displayRank }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="subject-col">Grand Total</td>
                                            <td>{{ $totals['max_marks'] }}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $displayTotal($totals['first_semester_total']) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Percentage</td>
                                            <td colspan="5">{{ $displayPercentage }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Rank in Class</td>
                                            <td colspan="5">{{ $displayRank }}</td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>

                        <div class="progress-sheet__panel progress-sheet__panel--spaced">
                            <div class="progress-sheet__section-title">Personal Attributes</div>
                            <table class="progress-sheet__side-table progress-sheet__side-table--attributes">
                                <colgroup>
                                    <col style="width: 76%;">
                                    <col style="width: 24%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeAttributes as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ $row['grade'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td class="progress-sheet__layout-side">
                        <table class="progress-sheet__right-stack">
                            <tr>
                                <td class="progress-sheet__right-stack-cell progress-sheet__right-stack-cell--grades">
                                    <div class="progress-sheet__panel progress-sheet__panel--stretch">
                                        <div class="progress-sheet__section-title">Grade Subjects</div>
                                        <table class="progress-sheet__side-table progress-sheet__side-table--grades">
                                            <colgroup>
                                                <col style="width: 72%;">
                                                <col style="width: 28%;">
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th>Subject</th>
                                                    <th>{{ $templateMeta['label'] }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($activeGrades as $row)
                                                    <tr>
                                                        <td>{{ $row['subject'] }}</td>
                                                        <td>{{ $row['grade'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>-</td>
                                                        <td>-</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="progress-sheet__right-stack-cell progress-sheet__right-stack-cell--remarks">
                                    <div class="progress-sheet__panel progress-sheet__panel--stretch">
                                        <div class="progress-sheet__section-title">Remarks</div>
                                        <div class="progress-sheet__remarks-body progress-sheet__remarks-body--expanded">
                                            @forelse($remarksLines as $line)
                                                <div>{{ $line }}</div>
                                            @empty
                                                <div>-</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="progress-sheet__right-stack-cell progress-sheet__right-stack-cell--scale">
                                    <div class="progress-sheet__panel progress-sheet__panel--stretch">
                                        <div class="progress-sheet__section-title">Grading Scale</div>
                                        <table class="progress-sheet__scale-table progress-sheet__scale-table--gradescale">
                                            <colgroup>
                                                <col style="width: 28%;">
                                                <col style="width: 72%;">
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th>Grade</th>
                                                    <th>Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($gradeScale as $grade => $label)
                                                    <tr>
                                                        <td>{{ $grade }}</td>
                                                        <td>{{ $label }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        @else
            <table class="progress-sheet__layout-table">
                <tr>
                    <td class="progress-sheet__layout-main">
                        <div class="progress-sheet__panel progress-sheet__panel--full">
                            <div class="progress-sheet__section-title">Scholastic Areas</div>
                            <table class="progress-sheet__table {{ $selectedExam->resolved_template === 'semester_2' ? 'progress-sheet__table--semester-two' : 'progress-sheet__table--single-term' }}">
                                @if($selectedExam->resolved_template === 'semester_2')
                                    <colgroup>
                                        <col style="width: 34%;">
                                        <col style="width: 9%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 10%;">
                                        <col style="width: 17%;">
                                    </colgroup>
                                @else
                                    <colgroup>
                                        <col style="width: 40%;">
                                        <col style="width: 10%;">
                                        <col style="width: 12%;">
                                        <col style="width: 12%;">
                                        <col style="width: 12%;">
                                        <col style="width: 14%;">
                                    </colgroup>
                                @endif
                                <thead>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <tr>
                                            <th class="subject-col">Subjects</th>
                                            <th>Max Marks</th>
                                            <th>1st Sem</th>
                                            <th>Unit II</th>
                                            <th>Final</th>
                                            <th>Total</th>
                                            <th>Average</th>
                                        </tr>
                                    @else
                                        <tr>
                                            <th class="subject-col">Subjects</th>
                                            <th>Max Marks</th>
                                            <th>Unit Test</th>
                                            <th>Main Exam</th>
                                            <th>Total</th>
                                            <th>Grade</th>
                                        </tr>
                                    @endif
                                </thead>
                                <tbody>
                                    @forelse($subjectRows as $row)
                                        @php
                                            $currentRecord = $selectedExam->resolved_template === 'semester_2' ? $row['second'] : $row['first'];
                                            $currentTotal = $selectedExam->resolved_template === 'semester_2' ? $row['second_total'] : $row['first_total'];
                                            $currentGrade = $currentRecord
                                                ? match (true) {
                                                    $currentTotal >= 90 => 'A+',
                                                    $currentTotal >= 80 => 'A',
                                                    $currentTotal >= 70 => 'B+',
                                                    $currentTotal >= 60 => 'B',
                                                    $currentTotal >= 50 => 'C',
                                                    $currentTotal >= 40 => 'D',
                                                    default => 'F',
                                                }
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td class="subject-col">{{ $row['subject']->name }}</td>
                                            <td>100</td>
                                            @if($selectedExam->resolved_template === 'semester_2')
                                                <td>{{ $displayMarks($row['first_total'], $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['second']?->unit_test_marks, $row['second']) }}</td>
                                                <td>{{ $displayMarks($row['second']?->main_exam_marks, $row['second']) }}</td>
                                                <td>{{ $displayMarks($row['second_total'], $row['second']) }}</td>
                                                <td>{{ ($row['first'] || $row['second']) ? number_format((float) $row['yearly_average'], 2) : '-' }}</td>
                                            @else
                                                <td>{{ $displayMarks($row['first']?->unit_test_marks, $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['first']?->main_exam_marks, $row['first']) }}</td>
                                                <td>{{ $displayMarks($row['first_total'], $row['first']) }}</td>
                                                <td>{{ $currentGrade }}</td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $selectedExam->resolved_template === 'semester_2' ? 7 : 6 }}">No subjects available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <tr>
                                            <td class="subject-col">Grand Total</td>
                                            <td>{{ $totals['max_marks'] }}</td>
                                            <td>{{ $displayTotal($totals['first_semester_total']) }}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $displayTotal($totals['second_semester_total']) }}</td>
                                            <td>{{ $displayTotal($totals['yearly_grand_total']) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Percentage</td>
                                            <td colspan="6">{{ $displayPercentage }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Rank in Class</td>
                                            <td colspan="6">{{ $displayRank }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="subject-col">Grand Total</td>
                                            <td>{{ $totals['max_marks'] }}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $displayTotal($totals['first_semester_total']) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Percentage</td>
                                            <td colspan="5">{{ $displayPercentage }}</td>
                                        </tr>
                                        <tr>
                                            <td class="subject-col">Rank in Class</td>
                                            <td colspan="5">{{ $displayRank }}</td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </td>
                    <td class="progress-sheet__layout-side">
                        <div class="progress-sheet__panel progress-sheet__panel--full">
                            <div class="progress-sheet__section-title">Grade Subjects</div>
                            <table class="progress-sheet__side-table progress-sheet__side-table--grades">
                                <colgroup>
                                    <col style="width: 72%;">
                                    <col style="width: 28%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>{{ $templateMeta['label'] }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activeGrades as $row)
                                        <tr>
                                            <td>{{ $row['subject'] }}</td>
                                            <td>{{ $row['grade'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="progress-sheet__layout-main">
                        <div class="progress-sheet__panel progress-sheet__panel--full">
                            <div class="progress-sheet__section-title">Personal Attributes</div>
                            <table class="progress-sheet__side-table progress-sheet__side-table--attributes">
                                <colgroup>
                                    <col style="width: 76%;">
                                    <col style="width: 24%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeAttributes as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ $row['grade'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td class="progress-sheet__layout-side">
                        <div class="progress-sheet__panel">
                            <div class="progress-sheet__section-title">Remarks</div>
                            <div class="progress-sheet__remarks-body">
                                @forelse($remarksLines as $line)
                                    <div>{{ $line }}</div>
                                @empty
                                    <div>-</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="progress-sheet__panel">
                            <div class="progress-sheet__section-title">Grading Scale</div>
                            <table class="progress-sheet__scale-table progress-sheet__scale-table--gradescale">
                                <colgroup>
                                    <col style="width: 28%;">
                                    <col style="width: 72%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeScale as $grade => $label)
                                        <tr>
                                            <td>{{ $grade }}</td>
                                            <td>{{ $label }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        <div class="progress-sheet__footer-wrap">
            @if($forPdf)
                <table class="progress-sheet__footer-table">
                    <tr>
                        <td>
                            <div class="progress-sheet__sign-line"></div>
                            <div class="progress-sheet__sign-label">{{ $activeReport?->class_teacher_signature ?: 'Class Teacher' }}</div>
                        </td>
                        <td>
                            <div class="progress-sheet__sign-line"></div>
                            <div class="progress-sheet__sign-label">{{ $activeReport?->principal_signature ?: 'Principal' }}</div>
                        </td>
                        <td>
                            <div class="progress-sheet__sign-line"></div>
                            <div class="progress-sheet__sign-label">{{ $activeReport?->parent_signature ?: 'Parent' }}</div>
                        </td>
                    </tr>
                </table>
            @else
                <div class="progress-sheet__footer">
                    <div class="progress-sheet__sign">
                        <div class="progress-sheet__sign-line"></div>
                        <div class="progress-sheet__sign-label">{{ $activeReport?->class_teacher_signature ?: 'Class Teacher' }}</div>
                    </div>
                    <div class="progress-sheet__sign">
                        <div class="progress-sheet__sign-line"></div>
                        <div class="progress-sheet__sign-label">{{ $activeReport?->principal_signature ?: 'Principal' }}</div>
                    </div>
                    <div class="progress-sheet__sign">
                        <div class="progress-sheet__sign-line"></div>
                        <div class="progress-sheet__sign-label">{{ $activeReport?->parent_signature ?: 'Parent' }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
