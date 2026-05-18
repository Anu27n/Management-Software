@extends('layouts.app')
@section('title', 'Enter Marks')
@section('page-title', 'Enter Marks')

@section('content')
@php
    $attributeFields = [
        'discipline_conduct' => 'Discipline & Conduct',
        'punctuality' => 'Punctuality',
        'self_confidence' => 'Self Confidence',
        'creativity' => 'Creativity',
        'spoken_english' => 'Spoken English',
        'personal_hygiene' => 'Personal Hygiene',
    ];
    $attributeGrades = ['A', 'B', 'C', 'D', 'Excellent', 'Good', 'Average'];
    $gradingGrades = ['A+', 'A', 'B+', 'B', 'C', 'D', 'Excellent', 'Good', 'Average'];
    $currentTemplate = $selectedExam?->resolved_template ?? $selectedTemplate;
    $templateOptions = \App\Support\ReportTemplateRegistry::all();
    $currentTemplateMeta = \App\Support\ReportTemplateRegistry::meta($currentTemplate);
    $previewRows = collect($marksheetPreview['subject_rows'] ?? []);
@endphp

<div class="card table-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportcards.enter-marks') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">Select Academic Year</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (string) request('academic_year_id', $selectedAcademicYear) === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Class <span class="text-danger">*</span></label>
                <select name="class_id" id="classSelect" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <select name="section_id" id="sectionSelect" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Marksheet Template <span class="text-danger">*</span></label>
                <select name="report_template" class="form-select" required>
                    @foreach($templateOptions as $templateKey => $templateMeta)
                        <option value="{{ $templateKey }}" {{ $selectedTemplate === $templateKey ? 'selected' : '' }}>{{ $templateMeta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Student <span class="text-danger">*</span></label>
                <select name="student_id" id="studentSelect" class="form-select" required>
                    <option value="">Select Student</option>
                    @foreach($students as $studentOption)
                        <option value="{{ $studentOption->id }}" {{ request('student_id') == $studentOption->id ? 'selected' : '' }}>
                            {{ $studentOption->full_name }} ({{ $studentOption->admission_no }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 col-lg-2">
                <button class="btn btn-primary w-100">Open Marksheet</button>
            </div>
        </form>
    </div>
</div>

@if($selectedExam && $selectedStudent)
<div class="card table-card mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-1 fw-semibold">{{ $selectedStudent->full_name }}</h6>
            <div class="text-muted small">
                {{ $selectedStudent->schoolClass?->name }} - {{ $selectedStudent->section?->name }}
                | {{ $selectedStudent->academicYear?->name }}
                | {{ $currentTemplateMeta['title'] }}
            </div>
        </div>
        <a href="{{ route('reportcards.view', ['academic_year_id' => $selectedAcademicYear, 'report_template' => $selectedTemplate, 'student_id' => $selectedStudent->id]) }}" class="btn btn-outline-primary btn-sm">Preview Marksheet</a>
    </div>
    <form method="POST" action="{{ route('reportcards.store-marks') }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear }}">
        <input type="hidden" name="report_template" value="{{ $selectedTemplate }}">
        <input type="hidden" name="class_id" value="{{ $selectedStudent->class_id }}">
        <input type="hidden" name="section_id" value="{{ $selectedStudent->section_id }}">
        <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">

        <div class="card-body">
            <div class="alert alert-info py-2 small">
                @if($currentTemplate === 'semester_2')
                    1st semester marks are fetched automatically and shown as read-only. Enter only 2nd semester marks below.
                @else
                    Enter marks directly for this template. Totals are calculated automatically.
                @endif
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0" id="scholasticMarksTable">
                    <thead class="table-light">
                        @if($currentTemplate === 'semester_2')
                            <tr>
                                <th>Subject</th>
                                <th class="text-center">1st Semester Total</th>
                                <th class="text-center">2nd Unit Test (20)</th>
                                <th class="text-center">Final Exam (80)</th>
                                <th class="text-center">2nd Semester Total</th>
                                <th class="text-center">Yearly Average</th>
                            </tr>
                        @else
                            <tr>
                                <th>Subject</th>
                                <th class="text-center">Unit Test (20)</th>
                                <th class="text-center">Half Yearly (80)</th>
                                <th class="text-center">Total</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach($subjects as $subject)
                            @php
                                $existing = $existingResults[$subject->id] ?? null;
                                $preview = $previewRows->firstWhere('subject.id', $subject->id);
                                $firstTotal = $preview['first_total'] ?? 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $subject->name }}</td>
                                @if($currentTemplate === 'semester_2')
                                    <td class="text-center bg-light fw-semibold js-first-total">{{ number_format((float) $firstTotal, 2) }}</td>
                                @endif
                                <td>
                                    <input type="number" step="0.01" min="0" max="20" name="scholastic[{{ $subject->id }}][unit_test_marks]" class="form-control text-center js-unit" value="{{ old("scholastic.{$subject->id}.unit_test_marks", $existing?->unit_test_marks) }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="80" name="scholastic[{{ $subject->id }}][main_exam_marks]" class="form-control text-center js-main" value="{{ old("scholastic.{$subject->id}.main_exam_marks", $existing?->main_exam_marks) }}">
                                </td>
                                <td class="text-center fw-semibold bg-light js-total">{{ number_format((float) old("scholastic.{$subject->id}.total", $existing?->calculated_total ?? $existing?->marks_obtained ?? 0), 2) }}</td>
                                @if($currentTemplate === 'semester_2')
                                    <td class="text-center fw-semibold bg-light js-yearly">{{ number_format(($firstTotal + (float) ($existing?->calculated_total ?? $existing?->marks_obtained ?? 0)) / 2, 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        @if($currentTemplate === 'semester_2')
                            <tr>
                                <td>Grand Total</td>
                                <td class="text-center" id="firstSemesterGrandTotal">{{ number_format((float) ($marksheetPreview['totals']['first_semester_total'] ?? 0), 2) }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-center" id="secondSemesterGrandTotal">{{ number_format((float) ($marksheetPreview['totals']['second_semester_total'] ?? 0), 2) }}</td>
                                <td class="text-center" id="yearlyGrandTotal">{{ number_format((float) ($marksheetPreview['totals']['yearly_grand_total'] ?? 0), 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>Grand Total</td>
                                <td></td>
                                <td></td>
                                <td class="text-center" id="firstSemesterGrandTotal">{{ number_format((float) ($marksheetPreview['totals']['first_semester_total'] ?? 0), 2) }}</td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 bg-light-subtle h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Grading Subjects</h6>
                            <div class="row g-3">
                                @foreach($gradingSubjects as $subject)
                                    @php($existingGrade = $existingResults[$subject->id]->grade ?? null)
                                    <div class="col-md-6">
                                        <label class="form-label">{{ $subject->name }}</label>
                                        <select name="grading[{{ $subject->id }}][grade]" class="form-select">
                                            <option value="">Select Grade</option>
                                            @foreach($gradingGrades as $grade)
                                                <option value="{{ $grade }}" {{ old("grading.{$subject->id}.grade", $existingGrade) === $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 bg-light-subtle h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Personal Attributes</h6>
                            <div class="row g-3">
                                @foreach($attributeFields as $field => $label)
                                    <div class="col-md-6">
                                        <label class="form-label">{{ $label }}</label>
                                        <select name="personal_attributes[{{ $field }}]" class="form-select">
                                            <option value="">Select</option>
                                            @foreach($attributeGrades as $grade)
                                                <option value="{{ $grade }}" {{ old("personal_attributes.{$field}", $existingReport?->personal_attributes[$field] ?? null) === $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">{{ $currentTemplate === 'semester_2' ? '2nd Unit Test Remarks' : 'Unit Test Remarks' }}</label>
                    <textarea name="remarks_unit_test" class="form-control" rows="3">{{ old('remarks_unit_test', $existingReport?->remarks_unit_test) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $currentTemplate === 'semester_2' ? 'Final Exam Remarks' : 'Main Exam Remarks' }}</label>
                    <textarea name="remarks_main_exam" class="form-control" rows="3">{{ old('remarks_main_exam', $existingReport?->remarks_main_exam) }}</textarea>
                </div>
            </div>

            @if($currentTemplate === 'semester_2')
                <div class="card border-0 bg-light-subtle mb-4">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Final Result Section</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Final Result</label>
                                <select name="final_result" class="form-select">
                                    <option value="">Select</option>
                                    <option value="promoted" {{ old('final_result', $existingReport?->final_result) === 'promoted' ? 'selected' : '' }}>Promoted</option>
                                    <option value="detained" {{ old('final_result', $existingReport?->final_result) === 'detained' ? 'selected' : '' }}>Detained</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Promoted / Detained Class</label>
                                <select name="promoted_to_class_id" class="form-select">
                                    <option value="">Select Class</option>
                                    @foreach($promoteClasses as $promoteClass)
                                        <option value="{{ $promoteClass->id }}" {{ (string) old('promoted_to_class_id', $existingReport?->promoted_to_class_id) === (string) $promoteClass->id ? 'selected' : '' }}>{{ $promoteClass->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">School Reopens On</label>
                                <input type="date" name="school_reopens_on" class="form-control" value="{{ old('school_reopens_on', optional($existingReport?->school_reopens_on)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Timings</label>
                                <input type="text" name="school_timings" class="form-control" value="{{ old('school_timings', $existingReport?->school_timings) }}" placeholder="e.g. 8:00 AM to 2:00 PM">
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Class Teacher Signature Label</label>
                    <input type="text" name="class_teacher_signature" class="form-control" value="{{ old('class_teacher_signature', $existingReport?->class_teacher_signature ?? 'Class Teacher') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Principal Signature Label</label>
                    <input type="text" name="principal_signature" class="form-control" value="{{ old('principal_signature', $existingReport?->principal_signature ?? 'Principal') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Parent Signature Label</label>
                    <input type="text" name="parent_signature" class="form-control" value="{{ old('parent_signature', $existingReport?->parent_signature ?? 'Parent') }}">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">Save marks directly for this student. No exam creation is required.</div>
            <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Marksheet</button>
        </div>
    </form>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const classSelect = document.getElementById('classSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    const studentSelect = document.getElementById('studentSelect');
    const lookupBaseUrl = @json(url('/api/reportcards/classes'));
    const selectedSection = @json((string) request('section_id'));
    const selectedStudent = @json((string) request('student_id'));
    let classStudents = [];

    const renderOptions = (selectEl, items, selectedValue, labelBuilder) => {
        const defaultLabel = selectEl === studentSelect ? 'Select Student' : 'Select';
        let html = `<option value="">${defaultLabel}</option>`;
        items.forEach((item) => {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${labelBuilder(item)}</option>`;
        });
        selectEl.innerHTML = html;
    };

    const filterStudentsBySection = (sectionId, preserve) => {
        const filtered = classStudents.filter((student) => String(student.section_id) === String(sectionId));
        renderOptions(studentSelect, filtered, preserve ? selectedStudent : '', (student) => `${student.name} (${student.admission_no})`);
    };

    const loadClassLookups = (classId, preserve = false) => {
        if (!classId) {
            sectionSelect.innerHTML = '<option value="">Select</option>';
            studentSelect.innerHTML = '<option value="">Select Student</option>';
            return;
        }

        fetch(`${lookupBaseUrl}/${classId}/lookups`)
            .then((response) => response.json())
            .then((data) => {
                classStudents = data.students || [];
                renderOptions(sectionSelect, data.sections || [], preserve ? selectedSection : '', (section) => section.name);
                filterStudentsBySection(preserve ? selectedSection : '', preserve);
            })
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">Select</option>';
                studentSelect.innerHTML = '<option value="">Select Student</option>';
            });
    };

    if (classSelect && sectionSelect && studentSelect) {
        classSelect.addEventListener('change', function () {
            loadClassLookups(this.value, false);
        });

        sectionSelect.addEventListener('change', function () {
            filterStudentsBySection(this.value, false);
        });

        if (classSelect.value) {
            loadClassLookups(classSelect.value, true);
        }
    }

    const table = document.getElementById('scholasticMarksTable');
    if (!table) {
        return;
    }

    const updateTotals = () => {
        let firstGrandTotal = 0;
        let secondGrandTotal = 0;
        let yearlyGrandTotal = 0;

        table.querySelectorAll('tbody tr').forEach((row) => {
            const unitInput = row.querySelector('.js-unit');
            const mainInput = row.querySelector('.js-main');
            const totalCell = row.querySelector('.js-total');
            const yearlyCell = row.querySelector('.js-yearly');
            const firstTotalCell = row.querySelector('.js-first-total');

            const unit = parseFloat(unitInput?.value || 0);
            const main = parseFloat(mainInput?.value || 0);
            const total = unit + main;
            const firstTotal = parseFloat(firstTotalCell?.textContent || 0);

            if (totalCell) {
                totalCell.textContent = total.toFixed(2);
            }

            firstGrandTotal += firstTotal;
            secondGrandTotal += total;

            if (yearlyCell) {
                const yearly = (firstTotal + total) / 2;
                yearlyCell.textContent = yearly.toFixed(2);
                yearlyGrandTotal += yearly;
            } else {
                yearlyGrandTotal += total;
            }
        });

        const firstGrandEl = document.getElementById('firstSemesterGrandTotal');
        const secondGrandEl = document.getElementById('secondSemesterGrandTotal');
        const yearlyGrandEl = document.getElementById('yearlyGrandTotal');

        if (firstGrandEl) firstGrandEl.textContent = firstGrandTotal.toFixed(2);
        if (secondGrandEl) secondGrandEl.textContent = secondGrandTotal.toFixed(2);
        if (yearlyGrandEl) yearlyGrandEl.textContent = yearlyGrandTotal.toFixed(2);
    };

    table.querySelectorAll('.js-unit, .js-main').forEach((input) => {
        input.addEventListener('input', updateTotals);
    });

    updateTotals();
})();
</script>
@endpush
