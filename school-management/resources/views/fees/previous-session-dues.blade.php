@extends('layouts.app')
@section('title', 'Previous Session Dues')
@section('page-title', 'Previous Session Dues')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-1">Past Session Outstanding Dues</h5>
        <div class="text-muted small">
            Open Dues: <strong>{{ $summary['open_due_count'] }}</strong> |
            Open Amount: <strong>Rs {{ number_format($summary['open_due_total'], 2) }}</strong>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('fees.due') }}" class="btn btn-outline-warning btn-sm">Due Fees</a>
        <a href="{{ route('fees.payments.create') }}" class="btn btn-primary btn-sm">Quick Record</a>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Add Previous Session Due</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('fees.previous-dues.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-12 col-lg-4">
                <label class="form-label">Student <span class="text-danger">*</span></label>
                <select name="student_id" class="form-select" required>
                    <option value="">Select student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->admission_no }} - {{ $student->full_name }}
                            @if($student->schoolClass?->name)
                                ({{ $student->schoolClass->name }}{{ $student->section?->name ? ' - ' . $student->section->name : '' }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label">Previous Session <span class="text-danger">*</span></label>
                <input type="text" name="previous_session" class="form-control" value="{{ old('previous_session') }}" placeholder="e.g. 2024-25" required>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Due Amount (Rs) <span class="text-danger">*</span></label>
                <input type="number" name="due_amount" class="form-control" step="0.01" min="0.01" value="{{ old('due_amount') }}" required>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Remarks</label>
                <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Optional notes">
            </div>
            <div class="col-12">
                <button class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Previous Due</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small">Student Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name or admission no">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="settled" {{ request('status') === 'settled' ? 'selected' : '' }}>Settled</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Session</th>
                    <th>Due Amount</th>
                    <th>Status</th>
                    <th>Added By</th>
                    <th>Remarks</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($previousDues as $due)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $due->student?->full_name ?? '-' }}</div>
                            <div class="small text-muted">{{ $due->student?->admission_no ?? '-' }}</div>
                        </td>
                        <td>{{ $due->student?->schoolClass?->name }}{{ $due->student?->section?->name ? ' - ' . $due->student->section->name : '' }}</td>
                        <td>{{ $due->previous_session }}</td>
                        <td class="fw-semibold">Rs {{ number_format($due->due_amount, 2) }}</td>
                        <td>
                            @if($due->status === 'open')
                                <span class="badge bg-warning text-dark">Open</span>
                            @else
                                <span class="badge bg-success">Settled</span>
                                @if($due->settled_at)
                                    <div class="small text-muted">{{ $due->settled_at->format('d M Y') }}</div>
                                @endif
                            @endif
                        </td>
                        <td>{{ $due->createdBy?->name ?? '-' }}</td>
                        <td>{{ $due->remarks ?: '-' }}</td>
                        <td class="text-end">
                            @if($due->status === 'open')
                                <form method="POST" action="{{ route('fees.previous-dues.settle', $due) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm">Mark Settled</button>
                                </form>
                            @else
                                <span class="text-muted small">Done</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No previous session due records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($previousDues->hasPages())
        <div class="card-footer bg-white">{{ $previousDues->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
