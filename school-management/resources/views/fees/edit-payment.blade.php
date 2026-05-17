@extends('layouts.app')
@section('title', 'Edit Payment')
@section('page-title', 'Edit Fee Payment')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="border rounded p-3 bg-light h-100">
                    <div class="text-muted small">Student</div>
                    <div class="fw-semibold">{{ $payment->student?->full_name ?? '-' }}</div>
                    <div class="small text-muted">
                        {{ collect([$payment->student?->admission_no, $payment->student?->schoolClass?->name, $payment->student?->section?->name])->filter()->join(' | ') }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 bg-light h-100">
                    <div class="text-muted small">Fee Head</div>
                    <div class="fw-semibold">{{ $payment->feeStructure?->display_name ?? '-' }}</div>
                    <div class="small text-muted">Assigned: Rs {{ number_format((float) ($payment->feeStructure?->amount ?? 0), 2) }}</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('fees.payments.update', $payment) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Receipt No <span class="text-danger">*</span></label>
                    <input type="text" name="receipt_no" class="form-control" value="{{ old('receipt_no', $payment->receipt_no) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">B.B Number</label>
                    <input type="text" name="bb_number" class="form-control" value="{{ old('bb_number', $payment->bb_number) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                    <input type="number" name="amount_paid" class="form-control" step="0.01" min="0" value="{{ old('amount_paid', $payment->amount_paid) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount</label>
                    <input type="number" name="discount" class="form-control" step="0.01" min="0" value="{{ old('discount', $payment->discount) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fine</label>
                    <input type="number" name="fine" class="form-control" step="0.01" min="0" value="{{ old('fine', $payment->fine) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Location <span class="text-danger">*</span></label>
                    <select name="payment_location" id="payment_location" class="form-select" required>
                        <option value="school" {{ old('payment_location', $payment->payment_location ?: 'school') === 'school' ? 'selected' : '' }}>School</option>
                        <option value="bank" {{ old('payment_location', $payment->payment_location) === 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_channel" id="payment_channel" class="form-select" required data-selected="{{ old('payment_channel', $payment->payment_channel ?: $payment->payment_method) }}"></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id', $payment->transaction_id) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">UTR Number</label>
                    <input type="text" name="utr_number" class="form-control" value="{{ old('utr_number', $payment->utr_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cheque Number</label>
                    <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number', $payment->cheque_number) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $payment->remarks) }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Payment</button>
                <a href="{{ route('fees.payments.show', $payment) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const paymentLocationEl = document.getElementById('payment_location');
    const paymentChannelEl = document.getElementById('payment_channel');
    const locationModeMap = {
        school: [
            { value: 'cash', label: 'Cash' },
            { value: 'upi', label: 'UPI' },
            { value: 'bank_transfer', label: 'Bank Transfer' },
            { value: 'cheque', label: 'Cheque' },
        ],
        bank: [
            { value: 'cheque', label: 'Cheque' },
            { value: 'cash', label: 'Cash' },
            { value: 'bank_transfer', label: 'Bank Transfer' },
        ],
    };

    function renderPaymentModes() {
        const selectedValue = paymentChannelEl.dataset.selected || 'cash';
        const options = locationModeMap[paymentLocationEl.value] || [];

        paymentChannelEl.innerHTML = '<option value="">Select</option>';
        options.forEach(function (option) {
            const selected = option.value === selectedValue ? 'selected' : '';
            paymentChannelEl.innerHTML += `<option value="${option.value}" ${selected}>${option.label}</option>`;
        });
    }

    if (paymentLocationEl && paymentChannelEl) {
        renderPaymentModes();
        paymentLocationEl.addEventListener('change', function () {
            paymentChannelEl.dataset.selected = '';
            renderPaymentModes();
        });
    }
})();
</script>
@endpush
