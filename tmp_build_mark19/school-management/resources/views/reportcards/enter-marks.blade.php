@extends('layouts.app')
@section('title', 'Enter Marks')
@section('page-title', 'Enter Marks')

@section('content')
<div class="card table-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportcards.enter-marks') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Exam <span class="text-danger">*</span></label>
                <select name="exam_id" class="form-select" required>
                    <option value="">Select Exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
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
                    @if(isset($sections))
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subject <span class="text-danger">*</span></label>
                <select name="subject_id" id="subjectSelect" class="form-select" required>
                    <option value="">Select</option>
                    @if(isset($subjects))
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Load Students</button>
            </div>
        </form>
    </div>
</div>

@if(isset($students) && $students->count())
<div class="card table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Enter Marks — {{ $selectedExam->name }} | {{ $selectedSubject->name }}</h6>
        <span class="badge bg-secondary">{{ $students->count() }} students</span>
    </div>
    <form method="POST" action="{{ route('reportcards.store-marks') }}">
        @csrf
        <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
        <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Student</th><th>Admission No</th><th style="width:120px">Marks</th><th style="width:120px">Max Marks</th><th>Grade</th></tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                    @php $existing = $existingResults[$student->id] ?? null; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $student->full_name }}</td>
                        <td>{{ $student->admission_no }}</td>
                        <td>
                            <input type="hidden" name="results[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                            <input type="number" name="results[{{ $student->id }}][marks_obtained]" class="form-control form-control-sm" min="0" value="{{ $existing->marks_obtained ?? '' }}" required>
                        </td>
                        <td>
                            <input type="number" name="results[{{ $student->id }}][max_marks]" class="form-control form-control-sm" min="1" value="{{ $existing->total_marks ?? 100 }}" required>
                        </td>
                        <td><span class="grade-display">{{ $existing->grade ?? '-' }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Marks</button>
        </div>
    </form>
</div>
@elseif(request('exam_id'))
<div class="alert alert-info">No students found for the selected criteria.</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const classSelect = document.getElementById('classSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const lookupBaseUrl = @json(url('/api/reportcards/classes'));

    if (!classSelect || !sectionSelect || !subjectSelect) {
        return;
    }

    const selectedSection = @json((string) request('section_id'));
    const selectedSubject = @json((string) request('subject_id'));

    const renderOptions = (selectEl, items, selectedValue = '') => {
        let html = '<option value="">Select</option>';
        items.forEach((item) => {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.name}</option>`;
        });
        selectEl.innerHTML = html;
    };

    const loadClassLookups = (classId, preserveSelected = false) => {
        if (!classId) {
            sectionSelect.innerHTML = '<option value="">Select</option>';
            subjectSelect.innerHTML = '<option value="">Select</option>';
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        subjectSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`${lookupBaseUrl}/${classId}/lookups`)
            .then((response) => response.json())
            .then((data) => {
                renderOptions(sectionSelect, data.sections || [], preserveSelected ? selectedSection : '');
                renderOptions(subjectSelect, data.subjects || [], preserveSelected ? selectedSubject : '');
            })
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">Select</option>';
                subjectSelect.innerHTML = '<option value="">Select</option>';
            });
    };

    classSelect.addEventListener('change', function () {
        loadClassLookups(this.value, false);
    });

    if (classSelect.value) {
        loadClassLookups(classSelect.value, true);
    }
})();
</script>
@endpush
