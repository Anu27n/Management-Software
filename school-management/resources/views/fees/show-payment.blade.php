@extends('layouts.app')
@section('title', 'Payment Receipt')
@section('page-title', 'Payment Receipt')

@section('content')
@php
    $canEditPayments = auth()->user()->hasPermission('fees.payments.edit');
    $canDeletePayments = auth()->user()->hasPermission('fees.payments.delete');
@endphp
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
                @if($payment->bb_number)
                <p><strong>B.B Number:</strong> {{ $payment->bb_number }}</p>
                @endif
                <p><strong>Date:</strong> {{ \App\Support\DateFormatter::display($payment->payment_date) }}</p>
                <p><strong>Payment Location:</strong> {{ ucfirst($payment->payment_location ?: 'school') }}</p>
                <p><strong>Payment Mode:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_channel ?: $payment->payment_method)) }}</p>
                @if($payment->transaction_id)
                <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
                @endif
                @if($payment->utr_number)
                <p><strong>UTR Number:</strong> {{ $payment->utr_number }}</p>
                @endif
                @if($payment->cheque_number)
                <p><strong>Cheque Number:</strong> {{ $payment->cheque_number }}</p>
                @endif
            </div>
            <div class="col-md-6">
                <p><strong>Student:</strong> {{ $payment->student->full_name }}</p>
                <p><strong>Admission No:</strong> {{ $payment->student->admission_no }}</p>
                <p><strong>Fee Category:</strong> {{ $payment->feeStructure->display_name ?? '-' }}</p>
            </div>
        </div>
        <table class="table table-bordered">
            <tr><td>Fee Amount</td><td class="text-end">Rs {{ number_format($payment->feeStructure->amount ?? 0, 2) }}</td></tr>
            <tr><td>Concession</td><td class="text-end text-success">- Rs {{ number_format($payment->discount, 2) }}</td></tr>
            <tr><td>Fine</td><td class="text-end text-danger">+ Rs {{ number_format($payment->fine, 2) }}</td></tr>
            <tr class="table-primary"><td class="fw-bold">Amount Paid</td><td class="text-end fw-bold">Rs {{ number_format($payment->amount_paid, 2) }}</td></tr>
        </table>
        <p><strong>Status:</strong> <span class="badge bg-{{ $payment->status == 'paid' ? 'success' : 'warning' }}">{{ $payment->status == 'paid' ? 'Fully Paid' : ($payment->status == 'partial' ? 'Partially Paid' : ucfirst($payment->status)) }}</span></p>
        @if($payment->remarks)
        <p><strong>Remarks:</strong> {{ $payment->remarks }}</p>
        @endif
        <p class="text-muted small">Collected by: {{ $payment->collector->name ?? '-' }}</p>
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
    @if($canEditPayments)
        <a href="{{ route('fees.payments.edit', $payment) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
    @endif
    @if($canDeletePayments)
        <form action="{{ route('fees.payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Delete this fee payment record? This will also remove its concession record.')" class="m-0">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
    @endif
    <a href="{{ route('fees.payments') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
