@extends('layouts.app')
@section('title', 'School Calendar & Events')
@section('page-title', 'School Calendar & Events')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Event</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.calendar.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><label class="form-label">Title</label><input type="text" name="title" class="form-control form-control-sm" required></div>
                    <div class="col-6"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                    <div class="col-6"><label class="form-label">End</label><input type="date" name="end_date" class="form-control form-control-sm" required></div>
                    <div class="col-12"><label class="form-label">Type</label><select name="event_type" class="form-select form-select-sm"><option value="general">General</option><option value="exam">Exam</option><option value="holiday">Holiday</option><option value="meeting">Meeting</option></select></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control form-control-sm"></textarea></div>
                    <div class="col-12 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="is_public" id="is_public" checked><label class="form-check-label" for="is_public">Visible to all roles</label></div></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary w-100">Add Event</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Centralized Event Management</h6>
                <form method="GET" action="{{ route('timetable.calendar') }}" class="d-flex gap-2">
                    <input type="month" name="month" value="{{ $selectedMonth }}" class="form-control form-control-sm">
                    <button class="btn btn-sm btn-outline-primary">Go</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Title</th><th>Dates</th><th>Type</th><th>Visibility</th><th></th></tr></thead>
                    <tbody>
                        @forelse($events as $event)
                        <tr>
                            <td><div class="fw-semibold">{{ $event->title }}</div><div class="small text-muted">{{ $event->description }}</div></td>
                            <td>{{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($event->event_type) }}</span></td>
                            <td>{{ $event->is_public ? 'Public' : 'Internal' }}</td>
                            <td>
                                <form method="POST" action="{{ route('timetable.calendar.destroy', $event) }}" onsubmit="return confirm('Delete event?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No events in this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
