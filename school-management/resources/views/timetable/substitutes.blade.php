@extends('layouts.app')
@section('title', 'Smart Substitute Management')
@section('page-title', 'Smart Substitute Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <form method="GET" action="{{ route('timetable.substitutes') }}" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        <button class="btn btn-sm btn-outline-primary">View</button>
    </form>

    <form method="POST" action="{{ route('timetable.substitutes.auto-run') }}" class="d-flex gap-2">
        @csrf
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        <button class="btn btn-sm btn-primary"><i class="bi bi-stars me-1"></i>Automatic Substitute/Cover Assignment</button>
    </form>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Faculty Cover Rules</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('timetable.cover-preferences.update') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Faculty</label>
                <select name="staff_id" class="form-select form-select-sm" required>
                    <option value="">Select Faculty</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Daily Covers</label>
                <input type="number" min="0" max="8" value="2" name="max_daily_covers" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="exclude_from_cover" id="exclude_from_cover" value="1">
                    <label class="form-check-label" for="exclude_from_cover">Exclude</label>
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-primary w-100">Save Rule</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Current Faculty Preferences</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Faculty</th><th>Max Covers/Day</th><th>Excluded</th></tr></thead>
            <tbody>
                @forelse($teachers as $teacher)
                    @php $pref = $coverPreferences[$teacher->id] ?? null; @endphp
                    <tr>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $pref?->max_daily_covers ?? 2 }}</td>
                        <td>{{ ($pref?->exclude_from_cover ?? false) ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">No faculty found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cover Date</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Absent Teacher</th>
                    <th>Cover Teacher</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $item)
                <tr>
                    <td>{{ $item->cover_date->format('M d, Y') }}</td>
                    <td>{{ $item->timetableEntry->schoolClass->name ?? '-' }} / {{ $item->timetableEntry->section->name ?? '-' }}</td>
                    <td>{{ $item->timetableEntry->subject->name ?? '-' }}</td>
                    <td>{{ $item->absentStaff->name ?? '-' }}</td>
                    <td>{{ $item->substituteStaff->name ?? 'No match found' }}</td>
                    <td>{{ $item->auto_assigned ? 'Auto' : 'Manual' }}</td>
                    <td><span class="badge bg-{{ $item->status === 'assigned' ? 'success' : 'warning' }}">{{ ucfirst($item->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No records for selected date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assignments->hasPages())<div class="card-footer bg-white">{{ $assignments->withQueryString()->links() }}</div>@endif
</div>
@endsection
