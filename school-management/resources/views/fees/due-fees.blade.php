@extends('layouts.app')
@section('title', 'Due Fees')
@section('page-title', 'Due Fees')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-1">Outstanding Student Fees</h5>
        <div class="text-muted small">
            Total Due: <strong>Rs {{ number_format($totalDueAmount, 2) }}</strong> |
            Due Fee Heads: <strong>{{ $totalDueHeads }}</strong>
        </div>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('fees.previous-dues') }}" class="btn btn-outline-secondary">Previous Session Dues</a>
        <a href="{{ route('fees.payments') }}" class="btn btn-outline-primary">Fee Payments</a>
        <a href="{{ route('fees.payments.create') }}" class="btn btn-primary">Quick Record</a>
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
                <label class="form-label small">Section ID</label>
                <input type="number" name="section_id" class="form-control form-control-sm" value="{{ request('section_id') }}" placeholder="Optional">
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
                    <th>Assigned</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Heads</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dueStudents as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['student']->full_name }}</div>
                            <div class="small text-muted">{{ $row['student']->admission_no }}</div>
                        </td>
                        <td>{{ $row['student']->schoolClass?->name }} {{ $row['student']->section?->name ? '- ' . $row['student']->section->name : '' }}</td>
                        <td>Rs {{ number_format($row['total_assigned'], 2) }}</td>
                        <td>Rs {{ number_format($row['total_paid'], 2) }}</td>
                        <td class="fw-semibold text-danger">Rs {{ number_format($row['total_due'], 2) }}</td>
                        <td>
                            @if($row['breakdown']->isNotEmpty())
                                <div class="small">
                                    @foreach($row['breakdown']->take(2) as $item)
                                        <div>{{ $item['fee_head'] }}: Rs {{ number_format($item['due_amount'], 2) }}</div>
                                    @endforeach
                                    @if($row['breakdown']->count() > 2)
                                        <div class="text-muted">+{{ $row['breakdown']->count() - 2 }} more</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No outstanding dues found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($dueStudents->hasPages())
        <div class="card-footer bg-white">{{ $dueStudents->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
