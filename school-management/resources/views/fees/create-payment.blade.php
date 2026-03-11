@extends('layouts.app')
@section('title', 'Record Payment')
@section('page-title', 'Record Fee Payment')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <form method="POST" action="{{ route('fees.payments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->admission_no }} - {{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fee Structure <span class="text-danger">*</span></label>
                    <select name="fee_structure_id" class="form-select" required>
                        <option value="">Select Fee</option>
                        @foreach($structures as $s)
                            <option value="{{ $s->id }}">{{ $s->feeCategory->name }} - {{ $s->schoolClass->name }} (₹{{ number_format($s->amount) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="amount_paid" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount (₹)</label>
                    <input type="number" name="discount" class="form-control" step="0.01" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fine (₹)</label>
                    <input type="number" name="fine" class="form-control" step="0.01" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Record Payment</button>
                <a href="{{ route('fees.payments') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
