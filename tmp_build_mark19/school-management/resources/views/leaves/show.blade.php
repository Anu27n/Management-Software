@extends('layouts.app')
@section('title', 'Leave Application Details')
@section('page-title', 'Leave Application Details')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Leave Application</h6>
                <span class="badge bg-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }} fs-6">
                    {{ ucfirst($leave->status) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:160px">Student</td><td class="fw-semibold">{{ $leave->student->full_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Class / Section</td><td>{{ $leave->schoolClass->name ?? '-' }} / {{ $leave->section->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">From</td><td>{{ $leave->from_date->format('M d, Y') }}</td></tr>
                    <tr><td class="text-muted">To</td><td>{{ $leave->to_date->format('M d, Y') }}</td></tr>
                    <tr><td class="text-muted">Duration</td><td>{{ $leave->from_date->diffInDays($leave->to_date) + 1 }} day(s)</td></tr>
                    <tr><td class="text-muted">Reason</td><td>{{ $leave->reason }}</td></tr>
                    @if($leave->description)
                        <tr><td class="text-muted">Description</td><td>{{ $leave->description }}</td></tr>
                    @endif
                    @if($leave->attachment)
                        <tr>
                            <td class="text-muted">Attachment</td>
                            <td><a href="{{ asset('storage/' . $leave->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Attachment</a></td>
                        </tr>
                    @endif
                    <tr><td class="text-muted">Applied By</td><td>{{ $leave->appliedBy->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Applied On</td><td>{{ $leave->created_at->format('M d, Y h:i A') }}</td></tr>
                    @if($leave->responded_at)
                        <tr><td class="text-muted">Responded On</td><td>{{ $leave->responded_at->format('M d, Y h:i A') }}</td></tr>
                    @endif
                    @if($leave->admin_remarks)
                    <tr><td class="text-muted">Remarks</td><td>{{ $leave->admin_remarks }}</td></tr>
                    @endif
                </table>
            </div>
            @if(auth()->user()->hasPermission('leaves.approve') && $leave->status == 'pending')
            <div class="card-footer bg-white d-flex gap-2">
                <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="flex-fill">
                    @csrf
                    <input type="text" name="admin_remarks" class="form-control form-control-sm mb-2" placeholder="Remarks (optional)">
                    <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Approve</button>
                </form>
                <form method="POST" action="{{ route('leaves.reject', $leave) }}" class="flex-fill">
                    @csrf
                    <input type="text" name="admin_remarks" class="form-control form-control-sm mb-2" placeholder="Reason for rejection">
                    <button class="btn btn-danger btn-sm w-100"><i class="bi bi-x-lg me-1"></i>Reject</button>
                </form>
            </div>
            @endif
        </div>
        <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>
@endsection
