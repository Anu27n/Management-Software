@extends('layouts.app')
@section('title', 'Fee Payments')
@section('page-title', 'Fee Payments')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        @if(auth()->user()->hasPermission('fees.manage'))
            <div class="btn-group btn-group-sm">
                <a href="{{ route('fees.categories') }}" class="btn btn-outline-primary">Categories</a>
                <a href="{{ route('fees.structures') }}" class="btn btn-outline-primary">Structures</a>
                <a href="{{ route('settings.payment-gateway') }}" class="btn btn-outline-primary">Payment Gateway</a>
            </div>
        @endif
    </div>
    <div class="d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('export.payments.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
            <a href="{{ route('export.payments.pdf', request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
        </div>
        <a href="{{ route('fees.payments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Record Payment</a>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-3">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Receipt</th><th>Student</th><th>Category</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->receipt_no }}</td>
                    <td>{{ $p->student->full_name }}</td>
                    <td>{{ $p->feeStructure->feeCategory->name ?? '-' }}</td>
                    <td>₹{{ number_format($p->amount_paid) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                    <td>{{ $p->payment_date->format('M d, Y') }}</td>
                    <td><span class="badge bg-{{ $p->status == 'paid' ? 'success' : ($p->status == 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($p->status) }}</span></td>
                    <td><a href="{{ route('fees.payments.show', $p) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No payments</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
