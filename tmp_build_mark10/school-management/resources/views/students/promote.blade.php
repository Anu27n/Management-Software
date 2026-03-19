@extends('layouts.app')
@section('title', 'Promote Students')
@section('page-title', 'Promote Students')

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Source Class</label>
                <select name="source_class_id" id="source_class_id" class="form-select" required>
                    <option value="">Select Class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" data-sections='@json($class->sections)' {{ (int) request('source_class_id') === (int) $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Source Section</label>
                <select name="source_section_id" id="source_section_id" class="form-select" required>
                    <option value="">Select Section</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Source Session</label>
                <select name="source_academic_year_id" class="form-select" required>
                    <option value="">Select Session</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (int) request('source_academic_year_id') === (int) $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Load Students</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Promotion Setup</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('students.promote.store') }}">
            @csrf
            <input type="hidden" name="source_class_id" value="{{ request('source_class_id') }}">
            <input type="hidden" name="source_section_id" value="{{ request('source_section_id') }}">
            <input type="hidden" name="source_academic_year_id" value="{{ request('source_academic_year_id') }}">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Target Session</label>
                    <select name="target_academic_year_id" class="form-select" required>
                        <option value="">Select Session</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Promoted Students: Target Class</label>
                    <select name="target_class_id" id="target_class_id" class="form-select">
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)'>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Promoted Students: Target Section</label>
                    <select name="target_section_id" id="target_section_id" class="form-select">
                        <option value="">Select Section</option>
                    </select>
                </div>
            </div>

            <div class="alert alert-info">
                Use <strong>Promote</strong> to move selected students into the target class and session. Use <strong>Repeat</strong> to keep them in the same class/section but move them into the selected target session.
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Student</th>
                            <th>Current Class</th>
                            <th>Session</th>
                            <th style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="form-check-input">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $student->full_name }}</div>
                                    <small class="text-muted">{{ $student->admission_no }}</small>
                                </td>
                                <td>{{ $student->schoolClass?->name }} - {{ $student->section?->name }}</td>
                                <td>{{ $student->academicYear?->name }}</td>
                                <td>
                                    <select name="student_actions[{{ $student->id }}]" class="form-select form-select-sm">
                                        <option value="promote">Promote</option>
                                        <option value="repeat">Repeat Same Class</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Load a class, section, and session to see students for promotion.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->isNotEmpty())
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-up-circle me-1"></i>Process Promotion
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-secondary">Back</a>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function populateSections(classSelectId, sectionSelectId, selectedSectionId = '') {
        const classSelect = document.getElementById(classSelectId);
        const sectionSelect = document.getElementById(sectionSelectId);

        if (!classSelect || !sectionSelect) {
            return;
        }

        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        const option = classSelect.options[classSelect.selectedIndex];

        if (!option || !option.dataset.sections) {
            return;
        }

        JSON.parse(option.dataset.sections).forEach(function (section) {
            const item = document.createElement('option');
            item.value = section.id;
            item.textContent = section.name;
            if (String(section.id) === String(selectedSectionId)) {
                item.selected = true;
            }
            sectionSelect.appendChild(item);
        });
    }

    document.getElementById('source_class_id')?.addEventListener('change', function () {
        populateSections('source_class_id', 'source_section_id');
    });

    document.getElementById('target_class_id')?.addEventListener('change', function () {
        populateSections('target_class_id', 'target_section_id');
    });

    populateSections('source_class_id', 'source_section_id', "{{ request('source_section_id') }}");
</script>
@endpush
