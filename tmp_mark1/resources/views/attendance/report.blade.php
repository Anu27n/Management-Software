@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance Report')

@section('content')
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
                <label class="form-label small">Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month', date('Y-m')) }}" required>
            </div>
            <div class="col-6 col-md-3">
                <button class="btn btn-primary btn-sm w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

@if($report->count())
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('export.attendance.csv', request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Student</th><th class="text-center text-success">Present</th><th class="text-center text-danger">Absent</th><th class="text-center text-warning">Late</th><th class="text-center">Half Day</th><th class="text-center">Total</th><th class="text-center">%</th></tr>
            </thead>
            <tbody>
                @foreach($report as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r['student']->full_name }}</td>
                    <td class="text-center text-success fw-semibold">{{ $r['present'] }}</td>
                    <td class="text-center text-danger fw-semibold">{{ $r['absent'] }}</td>
                    <td class="text-center text-warning fw-semibold">{{ $r['late'] }}</td>
                    <td class="text-center">{{ $r['half_day'] }}</td>
                    <td class="text-center">{{ $r['total'] }}</td>
                    <td class="text-center fw-semibold">
                        {{ $r['total'] > 0 ? round(($r['present'] / $r['total']) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
