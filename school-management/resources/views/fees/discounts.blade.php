@extends('layouts.app')
@section('title', 'Discount Records')
@section('page-title', 'Discount Records')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="btn-group btn-group-sm">
        <a href="{{ route('fees.payments') }}" class="btn btn-outline-primary">Payments</a>
        <a href="{{ route('fees.due') }}" class="btn btn-outline-warning">Due Fees</a>
    </div>
    <a href="{{ route('fees.payments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-lightning-charge me-1"></i>Quick Record</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">Discount Entries</div>
                <div class="fs-4 fw-bold">{{ $summary['total_discount_records'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">Total Discount Given</div>
                <div class="fs-4 fw-bold">Rs {{ number_format((float) $summary['total_discount_amount'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search Student</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, admission no, father name">
            </div>
            <div class="col-md-3">
                <label class="form-label small">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
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
                    <th>Date</th>
                    <th>Student</th>
                    <th>Fee Head</th>
                    <th>Receipt</th>
                    <th>Discount Amount</th>
                    <th>Discount %</th>
                    <th>Recorded By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discounts as $discount)
                    <tr>
                        <td>{{ $discount->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $discount->student?->full_name ?? '-' }}</div>
                            <small class="text-muted">{{ $discount->student?->admission_no ?? '-' }}</small>
                        </td>
                        <td>{{ $discount->feeStructure?->feeCategory?->name ?? '-' }}</td>
                        <td>{{ $discount->payment?->receipt_no ?? '-' }}</td>
                        <td class="text-success fw-semibold">Rs {{ number_format((float) $discount->discount_amount, 2) }}</td>
                        <td>{{ number_format((float) $discount->discount_percentage, 2) }}%</td>
                        <td>{{ $discount->createdBy?->name ?? '-' }}</td>
                        <td>{{ $discount->remarks ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No discount records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($discounts->hasPages())
        <div class="card-footer bg-white">{{ $discounts->links() }}</div>
    @endif
</div>
@endsection
