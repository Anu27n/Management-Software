@extends('layouts.app')
@section('title', 'My Fees')
@section('page-title', 'My Fees')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Students Linked</div>
                <div class="fs-4 fw-bold">{{ $students->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Outstanding Fees</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($feeOverview['due_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Payments Recorded</div>
                <div class="fs-4 fw-bold">{{ $recentPayments->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Paid Amount</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($feeOverview['paid_amount'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Due Fees</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Fee Head</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feeOverview['items'] as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item['student']->full_name }}</td>
                        <td>{{ $item['student']->schoolClass?->name ?? '-' }}</td>
                        <td>{{ $item['structure']->feeCategory?->name ?? '-' }}</td>
                        <td>Rs {{ number_format($item['structure']->amount, 2) }}</td>
                        <td>Rs {{ number_format($item['paid_amount'], 2) }}</td>
                        <td class="{{ $item['due_amount'] > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                            Rs {{ number_format($item['due_amount'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No fee records available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card table-card">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Recent Payments</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Receipt</th>
                    <th>Student</th>
                    <th>Fee Head</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $payment)
                    <tr>
                        <td class="fw-semibold">{{ $payment->receipt_no }}</td>
                        <td>{{ $payment->student?->full_name ?? '-' }}</td>
                        <td>{{ $payment->feeStructure?->feeCategory?->name ?? '-' }}</td>
                        <td>Rs {{ number_format($payment->amount_paid, 2) }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'partial' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No payments recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
