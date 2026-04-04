@php
    $forPdf = $forPdf ?? false;
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
    $schoolName = $siteSettings->school_name ?: config('app.name');
    $schoolSubtitle = $siteSettings->address ?: 'School Address';
    $contactLine = trim(collect([$siteSettings->contact_number, $siteSettings->contact_email])->filter()->implode(' | '));
@endphp

<div class="marksheet-shell {{ $forPdf ? 'pdf-mode' : '' }}">
    <div class="marksheet-header mb-3">
        <div class="marksheet-header-top">
            @if(!empty($siteSettings->logo_url))
                <img src="{{ $siteSettings->logo_url }}" alt="School Logo" class="marksheet-logo">
            @endif
            <div class="text-center">
                <h3 class="fw-bold mb-1">{{ $schoolName }}</h3>
                <div class="small text-muted fst-italic">{{ $schoolSubtitle }}</div>
                @if($contactLine !== '')
                    <div class="small text-muted fst-italic">{{ $contactLine }}</div>
                @endif
            </div>
        </div>
        <div class="text-uppercase small fw-semibold marksheet-title-bar">
            {{ $selectedExam->resolved_template === 'semester_2' ? 'Final / 2nd Semester Marksheet' : '1st Semester Marksheet' }}
        </div>
        <div class="small text-muted mt-1">{{ $selectedExam->name }}</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0 marksheet-meta">
                <tr><td>Name</td><td class="fw-semibold">{{ $student->full_name }}</td></tr>
                <tr><td>Admission No.</td><td>{{ $student->admission_no }}</td></tr>
                <tr><td>Class</td><td>{{ $student->schoolClass?->name }} - {{ $student->section?->name }}</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0 marksheet-meta">
                <tr><td>Academic Year</td><td>{{ $student->academicYear?->name }}</td></tr>
                <tr><td>Percentage</td><td>{{ number_format((float) $totals['percentage'], 2) }}%</td></tr>
                <tr><td>Rank in Class</td><td>{{ $marksheet['rank'] ? '#' . $marksheet['rank'] : '-' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered marksheet-table mb-0">
            <thead>
                @if($selectedExam->resolved_template === 'semester_2')
                    <tr>
                        <th>Subject</th>
                        <th class="text-center">1st Sem Total</th>
                        <th class="text-center">2nd Unit Test</th>
                        <th class="text-center">Final Exam</th>
                        <th class="text-center">2nd Sem Total</th>
                        <th class="text-center">Yearly Average</th>
                    </tr>
                @else
                    <tr>
                        <th>Subject</th>
                        <th class="text-center">Unit Test</th>
                        <th class="text-center">Half Yearly Exam</th>
                        <th class="text-center">Total</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach($subjectRows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['subject']->name }}</td>
                        @if($selectedExam->resolved_template === 'semester_2')
                            <td class="text-center">{{ number_format((float) $row['first_total'], 2) }}</td>
                        @endif
                        <td class="text-center">{{ number_format((float) ($selectedExam->resolved_template === 'semester_2' ? ($row['second']?->unit_test_marks ?? 0) : ($row['first']?->unit_test_marks ?? 0)), 2) }}</td>
                        <td class="text-center">{{ number_format((float) ($selectedExam->resolved_template === 'semester_2' ? ($row['second']?->main_exam_marks ?? 0) : ($row['first']?->main_exam_marks ?? 0)), 2) }}</td>
                        <td class="text-center">{{ number_format((float) ($selectedExam->resolved_template === 'semester_2' ? $row['second_total'] : $row['first_total']), 2) }}</td>
                        @if($selectedExam->resolved_template === 'semester_2')
                            <td class="text-center">{{ number_format((float) $row['yearly_average'], 2) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if($selectedExam->resolved_template === 'semester_2')
                    <tr class="fw-bold table-light">
                        <td>Grand Total</td>
                        <td class="text-center">{{ number_format((float) $totals['first_semester_total'], 2) }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-center">{{ number_format((float) $totals['second_semester_total'], 2) }}</td>
                        <td class="text-center">{{ number_format((float) $totals['yearly_grand_total'], 2) }}</td>
                    </tr>
                @else
                    <tr class="fw-bold table-light">
                        <td>Grand Total</td>
                        <td></td>
                        <td></td>
                        <td class="text-center">{{ number_format((float) $totals['first_semester_total'], 2) }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100 marksheet-card">
                <div class="card-header bg-white fw-semibold">Grading Subjects</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th class="text-center">{{ $selectedExam->resolved_template === 'semester_2' ? '1st Sem' : 'Grade' }}</th>
                                @if($selectedExam->resolved_template === 'semester_2')
                                    <th class="text-center">2nd Sem</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gradingRows as $row)
                                <tr>
                                    <td>{{ $row['subject']->name }}</td>
                                    <td class="text-center">{{ $row['first_grade'] ?: '-' }}</td>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <td class="text-center">{{ $row['second_grade'] ?: '-' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 marksheet-card">
                <div class="card-header bg-white fw-semibold">Personal Attributes</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Attribute</th>
                                <th class="text-center">{{ $selectedExam->resolved_template === 'semester_2' ? '1st Sem' : 'Grade' }}</th>
                                @if($selectedExam->resolved_template === 'semester_2')
                                    <th class="text-center">2nd Sem</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attributeFields as $key => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-center">{{ $personalAttributes['first'][$key] ?? '-' }}</td>
                                    @if($selectedExam->resolved_template === 'semester_2')
                                        <td class="text-center">{{ $personalAttributes['second'][$key] ?? '-' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card marksheet-card h-100">
                <div class="card-header bg-white fw-semibold">Remarks</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0 remarks-table">
                        <tbody>
                            <tr>
                                <td class="remarks-label">1st Unit Test</td>
                                <td>{{ $firstReport?->remarks_unit_test ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="remarks-label">Half Yearly</td>
                                <td>{{ $firstReport?->remarks_main_exam ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="remarks-label">2nd Unit Test</td>
                                <td>{{ $secondReport?->remarks_unit_test ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="remarks-label">Final Exams</td>
                                <td>{{ $secondReport?->remarks_main_exam ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card marksheet-card h-100">
                <div class="card-header bg-white fw-semibold">Result Summary</div>
                <div class="card-body">
                    @if($selectedExam->resolved_template === 'semester_2')
                        <div><strong>1st Semester Total:</strong> {{ number_format((float) $totals['first_semester_total'], 2) }}</div>
                        <div class="mt-2"><strong>2nd Semester Total:</strong> {{ number_format((float) $totals['second_semester_total'], 2) }}</div>
                        <div class="mt-2"><strong>Grand Total:</strong> {{ number_format((float) $totals['yearly_grand_total'], 2) }}</div>
                        <div class="mt-2"><strong>Yearly Average %:</strong> {{ number_format((float) $totals['percentage'], 2) }}%</div>
                        <div class="mt-2"><strong>Final Result:</strong> {{ $marksheet['result_label'] }}</div>
                        <div class="mt-2"><strong>{{ ($secondReport?->final_result === 'detained' ? 'Detained in Class' : 'Promoted to Class') }}:</strong> {{ $secondReport?->promotedToClass?->name ?? '-' }}</div>
                        <div class="mt-2"><strong>School Reopens On:</strong> {{ optional($secondReport?->school_reopens_on)->format('M d, Y') ?? '-' }}</div>
                        <div class="mt-2"><strong>Timings:</strong> {{ $secondReport?->school_timings ?: '-' }}</div>
                    @else
                        <div><strong>Grand Total:</strong> {{ number_format((float) $totals['first_semester_total'], 2) }}</div>
                        <div class="mt-2"><strong>Percentage:</strong> {{ number_format((float) $totals['percentage'], 2) }}%</div>
                        <div class="mt-2"><strong>Rank:</strong> {{ $marksheet['rank'] ? '#' . $marksheet['rank'] : '-' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row text-center pt-4 signature-row">
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="small fw-semibold">{{ $secondReport?->class_teacher_signature ?? $firstReport?->class_teacher_signature ?? 'Class Teacher' }}</div>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="small fw-semibold">{{ $secondReport?->principal_signature ?? $firstReport?->principal_signature ?? 'Principal' }}</div>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="small fw-semibold">{{ $secondReport?->parent_signature ?? $firstReport?->parent_signature ?? 'Parent' }}</div>
        </div>
    </div>
</div>
