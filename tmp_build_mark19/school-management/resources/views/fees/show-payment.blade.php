@extends('layouts.app')
@section('title', 'Payment Receipt')
@section('page-title', 'Payment Receipt')

@section('content')
<div class="card table-card" id="receipt">
    <div class="card-body">
        <div class="text-center mb-4">
            <h4 class="fw-bold">School Management System</h4>
            <h6 class="text-muted">Fee Payment Receipt</h6>
            <hr>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Receipt No:</strong> {{ $payment->receipt_no }}</p>
                <p><strong>Date:</strong> {{ $payment->payment_date->format('M d, Y') }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                @if($payment->transaction_id)
                <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
                @endif
            </div>
            <div class="col-md-6">
                <p><strong>Student:</strong> {{ $payment->student->full_name }}</p>
                <p><strong>Admission No:</strong> {{ $payment->student->admission_no }}</p>
                <p><strong>Fee Category:</strong> {{ $payment->feeStructure->feeCategory->name ?? '-' }}</p>
            </div>
        </div>
        <table class="table table-bordered">
            <tr><td>Fee Amount</td><td class="text-end">₹{{ number_format($payment->feeStructure->amount ?? 0, 2) }}</td></tr>
            <tr><td>Discount</td><td class="text-end text-success">- ₹{{ number_format($payment->discount, 2) }}</td></tr>
            <tr><td>Fine</td><td class="text-end text-danger">+ ₹{{ number_format($payment->fine, 2) }}</td></tr>
            <tr class="table-primary"><td class="fw-bold">Amount Paid</td><td class="text-end fw-bold">₹{{ number_format($payment->amount_paid, 2) }}</td></tr>
        </table>
        <p><strong>Status:</strong> <span class="badge bg-{{ $payment->status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payment->status) }}</span></p>
        @if($payment->remarks)
        <p><strong>Remarks:</strong> {{ $payment->remarks }}</p>
        @endif
        <p class="text-muted small">Collected by: {{ $payment->collector->name ?? '-' }}</p>
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
    <a href="{{ route('fees.payments') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
