@extends('layouts.app')
@section('title', 'Smart Timetable')
@section('page-title', 'Smart Timetable')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card table-card h-100"><div class="card-body"><div class="text-muted small">Time Slots</div><div class="fs-4 fw-bold">{{ $stats['total_slots'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card table-card h-100"><div class="card-body"><div class="text-muted small">Scheduled Periods</div><div class="fs-4 fw-bold">{{ $stats['total_entries'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card table-card h-100"><div class="card-body"><div class="text-muted small">Pending Staff Leaves</div><div class="fs-4 fw-bold">{{ $stats['pending_staff_leaves'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card table-card h-100"><div class="card-body"><div class="text-muted small">Today Covers</div><div class="fs-4 fw-bold">{{ $stats['today_substitute_count'] }}</div></div></div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Automatic Substitute Assignments (Today)</h6>
        @if(auth()->user()->hasPermission('substitutes.manage'))
        <form method="POST" action="{{ route('timetable.substitutes.auto-run') }}" class="d-flex gap-2">
            @csrf
            <input type="hidden" name="date" value="{{ now()->toDateString() }}">
            <button class="btn btn-sm btn-primary"><i class="bi bi-magic me-1"></i>Run Auto Assignment</button>
        </form>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Slot</th>
                    <th>Absent</th>
                    <th>Substitute</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todaySubstitutes as $item)
                <tr>
                    <td>{{ $item->timetableEntry->schoolClass->name ?? '-' }} / {{ $item->timetableEntry->section->name ?? '-' }}</td>
                    <td>{{ $item->timetableEntry->subject->name ?? '-' }}</td>
                    <td>{{ $item->timetableEntry->slot->name ?? '-' }}</td>
                    <td>{{ $item->absentStaff->name ?? '-' }}</td>
                    <td>{{ $item->substituteStaff->name ?? 'Not Found' }}</td>
                    <td><span class="badge bg-{{ $item->status === 'assigned' ? 'success' : 'warning' }}">{{ ucfirst($item->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
