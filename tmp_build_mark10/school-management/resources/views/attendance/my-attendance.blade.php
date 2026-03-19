@extends('layouts.app')
@section('title', 'My Attendance')
@section('page-title', 'My Attendance')

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            @if($students->count() > 1)
                <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ (int) request('student_id', $selectedStudent?->id) === (int) $student->id ? 'selected' : '' }}>
                                {{ $student->full_name }} - {{ $student->schoolClass?->name }} {{ $student->section?->name ? '(' . $student->section->name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-4">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">View</button>
            </div>
        </form>
    </div>
</div>

@if($selectedStudent)
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Present</div>
                    <div class="fs-4 fw-bold text-success">{{ $summary['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Absent</div>
                    <div class="fs-4 fw-bold text-danger">{{ $summary['absent'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Late / Half Day</div>
                    <div class="fs-4 fw-bold text-warning">{{ $summary['late'] + $summary['half_day'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Marked Days</div>
                    <div class="fs-4 fw-bold">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-semibold">{{ $selectedStudent->full_name }}</h6>
                <small class="text-muted">{{ $selectedStudent->schoolClass?->name }} {{ $selectedStudent->section?->name ? '- ' . $selectedStudent->section->name : '' }}</small>
            </div>
            <span class="badge bg-light text-dark">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->date?->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'absent' ? 'danger' : 'warning') }}">
                                    {{ ucwords(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">No attendance records found for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card table-card">
        <div class="card-body text-center py-5 text-muted">
            No student is linked to this login yet.
        </div>
    </div>
@endif
@endsection
