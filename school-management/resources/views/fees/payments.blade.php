@extends('layouts.app')
@section('title', 'Fee Payments')
@section('page-title', 'Fee Payments')

@section('content')
@php
    $canEditPayments = auth()->user()->hasPermission('fees.payments.edit');
    $canDeletePayments = auth()->user()->hasPermission('fees.payments.delete');
@endphp
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
        <a href="{{ route('fees.discounts') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-percent me-1"></i>Concession Records</a>
        <a href="{{ route('fees.discount-presets') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-tags me-1"></i>Concession Options</a>
        <a href="{{ route('fees.due') }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-exclamation-circle me-1"></i>Due Fees</a>
        <a href="{{ route('fees.previous-dues') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history me-1"></i>Previous Session Dues</a>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('export.payments.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
            <a href="{{ route('export.payments.pdf', request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
        </div>
        <a href="{{ route('fees.payments.create') }}" class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Quick Record</a>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small">Student Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, admission no, parent">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Fully Paid</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Payment Location</label>
                <select name="payment_location" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="school" {{ request('payment_location') == 'school' ? 'selected' : '' }}>School</option>
                    <option value="bank" {{ request('payment_location') == 'bank' ? 'selected' : '' }}>Bank</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Fees Register</label>
                <select name="register_view" class="form-select form-select-sm">
                    <option value="day_wise" {{ ($registerView ?? 'day_wise') === 'day_wise' ? 'selected' : '' }}>Day Wise</option>
                    <option value="month_wise" {{ ($registerView ?? '') === 'month_wise' ? 'selected' : '' }}>Month Wise</option>
                    <option value="quarter_wise" {{ ($registerView ?? '') === 'quarter_wise' ? 'selected' : '' }}>Quarter Wise</option>
                    <option value="quarter_fee_wise" {{ ($registerView ?? '') === 'quarter_fee_wise' ? 'selected' : '' }}>Quarter Fees</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Fees Register Summary</h6></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Register</th><th>Receipts</th><th>Amount Paid</th><th>Concession</th><th>Fine</th><th>Settled</th></tr>
            </thead>
            <tbody>
                @forelse($registerSummary as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['label'] }}</td>
                        <td>{{ $row['records'] }}</td>
                        <td>Rs {{ number_format((float) $row['amount_paid'], 2) }}</td>
                        <td class="text-success">Rs {{ number_format((float) $row['concession_amount'], 2) }}</td>
                        <td>Rs {{ number_format((float) $row['fine_amount'], 2) }}</td>
                        <td class="fw-semibold">Rs {{ number_format((float) $row['settled_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No fee register data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Receipt</th><th>B.B No</th><th>Student</th><th>Category</th><th>Amount</th><th>Location</th><th>Mode</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->receipt_no }}</td>
                    <td>{{ $p->bb_number ?: '-' }}</td>
                    <td>{{ $p->student->full_name }}</td>
                    <td>{{ $p->feeStructure->display_name ?? '-' }}</td>
                    <td>Rs {{ number_format((float) $p->amount_paid, 2) }}</td>
                    <td>{{ ucfirst($p->payment_location ?: 'school') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $p->payment_channel ?: $p->payment_method)) }}</td>
                    <td>{{ \App\Support\DateFormatter::display($p->payment_date) }}</td>
                    <td><span class="badge bg-{{ $p->status == 'paid' ? 'success' : ($p->status == 'partial' ? 'warning' : 'danger') }}">{{ $p->status == 'paid' ? 'Fully Paid' : ($p->status == 'partial' ? 'Partially Paid' : 'Pending') }}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('fees.payments.show', $p) }}" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            @if($canEditPayments)
                                <a href="{{ route('fees.payments.edit', $p) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endif
                            @if($canDeletePayments)
                                <form action="{{ route('fees.payments.destroy', $p) }}" method="POST" onsubmit="return confirm('Delete this fee payment record? This will also remove its concession record.')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-3">No payments</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
