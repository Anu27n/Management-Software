@extends('layouts.app')
@section('title', 'Mark Attendance')
@section('page-title', 'Mark Attendance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">Daily Attendance</h5>
    <a href="{{ route('attendance.report') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-bar-chart me-1"></i>View Report</a>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small">Class</label>
                <select name="class_id" id="class_id" class="form-select form-select-sm" required>
                    <option value="">Select Class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" data-sections='@json($class->sections)' {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Section</label>
                <select name="section_id" id="section_id" class="form-select form-select-sm" required>
                    <option value="">Select Section</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" required>
            </div>
            <div class="col-6 col-md-3">
                <button class="btn btn-primary btn-sm w-100">Load Students</button>
            </div>
        </form>
    </div>
</div>

@if($students->count())
<div class="card table-card">
    <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ request('class_id') }}">
        <input type="hidden" name="section_id" value="{{ request('section_id') }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Adm. No</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Half Day</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                    @php $current = $attendances[$student->id] ?? 'present'; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>{{ $student->admission_no }}</td>
                        <td class="text-center">
                            <input type="radio" name="attendance[{{ $student->id }}]" value="present" class="form-check-input" {{ $current == 'present' ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="attendance[{{ $student->id }}]" value="absent" class="form-check-input" {{ $current == 'absent' ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="attendance[{{ $student->id }}]" value="late" class="form-check-input" {{ $current == 'late' ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="attendance[{{ $student->id }}]" value="half_day" class="form-check-input" {{ $current == 'half_day' ? 'checked' : '' }}>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Attendance</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('input[value=present]').forEach(r=>r.checked=true)">Mark All Present</button>
        </div>
    </form>
</div>
@endif
@endsection

@push('scripts')
<script>
const currentSection = '{{ request('section_id') }}';
function loadSections() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    const option = classSelect.options[classSelect.selectedIndex];
    if (option.dataset.sections) {
        JSON.parse(option.dataset.sections).forEach(s => {
            sectionSelect.innerHTML += `<option value="${s.id}" ${s.id == currentSection ? 'selected' : ''}>${s.name}</option>`;
        });
    }
}
document.getElementById('class_id').addEventListener('change', loadSections);
if (document.getElementById('class_id').value) loadSections();
</script>
@endpush
