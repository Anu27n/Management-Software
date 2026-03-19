@extends('layouts.app')
@section('title', 'Leave Applications')
@section('page-title', 'Leave Applications')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <form method="GET" action="{{ route('leaves.index') }}" class="d-flex flex-wrap gap-2">
        <select name="status" class="form-select form-select-sm" style="width: 160px;">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        @if(auth()->user()->hasPermission('leaves.approve'))
            <select name="class_id" class="form-select form-select-sm" style="width: 200px;">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        @endif

        <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
    </form>

    <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Application</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Applied By</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $leave->student->full_name ?? 'N/A' }}</div>
                        <small class="text-muted">{{ $leave->reason }}</small>
                    </td>
                    <td>{{ $leave->schoolClass->name ?? '-' }} / {{ $leave->section->name ?? '-' }}</td>
                    <td>{{ $leave->from_date->format('M d, Y') }}</td>
                    <td>{{ $leave->to_date->format('M d, Y') }}</td>
                    <td>{{ $leave->from_date->diffInDays($leave->to_date) + 1 }}</td>
                    <td>{{ $leave->appliedBy->name ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($leave->status) }}
                        </span>
                    </td>
                    <td><a href="{{ route('leaves.show', $leave) }}" class="btn btn-outline-primary btn-sm">View</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No leave applications found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
    <div class="card-footer bg-white">{{ $leaves->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
