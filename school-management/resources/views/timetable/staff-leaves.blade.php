@extends('layouts.app')
@section('title', 'Staff Absence Tracking')
@section('page-title', 'Staff Absence Tracking')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Log Staff Leave</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.staff-leaves.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><label class="form-label">Teacher</label><select name="staff_id" class="form-select form-select-sm" required><option value="">Select</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label">From</label><input type="date" name="from_date" class="form-control form-control-sm" required></div>
                    <div class="col-6"><label class="form-label">To</label><input type="date" name="to_date" class="form-control form-control-sm" required></div>
                    <div class="col-12"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control form-control-sm" required></div>
                    <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" rows="2" class="form-control form-control-sm"></textarea></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary w-100">Submit Leave</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Leave Management</h6>
                <form method="GET" action="{{ route('timetable.staff-leaves') }}" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Filter</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Staff</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($leaves as $leave)
                        <tr>
                            <td>{{ $leave->staff->name ?? '-' }}</td>
                            <td>{{ $leave->from_date->format('M d, Y') }}</td>
                            <td>{{ $leave->to_date->format('M d, Y') }}</td>
                            <td>{{ $leave->reason }}</td>
                            <td><span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($leave->status) }}</span></td>
                            <td>
                                @if($leave->status === 'pending')
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('timetable.staff-leaves.approve', $leave) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                    <form method="POST" action="{{ route('timetable.staff-leaves.reject', $leave) }}">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No staff leaves found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leaves->hasPages())<div class="card-footer bg-white">{{ $leaves->withQueryString()->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
