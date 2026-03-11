@extends('layouts.app')
@section('title', 'Leave Applications')
@section('page-title', 'Leave Applications')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="btn-group btn-group-sm">
        <a href="{{ route('leaves.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }}">All</a>
        <a href="{{ route('leaves.index', ['status' => 'pending']) }}" class="btn btn-{{ request('status') == 'pending' ? 'warning' : 'outline-warning' }}">Pending</a>
        <a href="{{ route('leaves.index', ['status' => 'approved']) }}" class="btn btn-{{ request('status') == 'approved' ? 'success' : 'outline-success' }}">Approved</a>
        <a href="{{ route('leaves.index', ['status' => 'rejected']) }}" class="btn btn-{{ request('status') == 'rejected' ? 'danger' : 'outline-danger' }}">Rejected</a>
    </div>
    <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Application</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Applicant</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $leave->user->name }}</div>
                        <small class="text-muted">{{ ucfirst($leave->user->role) }}</small>
                    </td>
                    <td>{{ $leave->leave_type }}</td>
                    <td>{{ $leave->start_date->format('M d, Y') }}</td>
                    <td>{{ $leave->end_date->format('M d, Y') }}</td>
                    <td>{{ $leave->start_date->diffInDays($leave->end_date) + 1 }}</td>
                    <td>
                        <span class="badge bg-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($leave->status) }}
                        </span>
                    </td>
                    <td><a href="{{ route('leaves.show', $leave) }}" class="btn btn-outline-primary btn-sm">View</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No leave applications found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
    <div class="card-footer bg-white">{{ $leaves->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
