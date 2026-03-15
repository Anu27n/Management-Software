@extends('layouts.app')
@section('title', 'Record Payment')
@section('page-title', 'Record Fee Payment')

@section('content')
@php
    $razorpayReady = $gatewaySettings->is_enabled
        && !blank($gatewaySettings->key_id)
        && !blank($gatewaySettings->key_secret);
@endphp

<div class="card table-card">
    <div class="card-body">
        <form id="feePaymentForm" method="POST" action="{{ route('fees.payments.store') }}">
            @csrf

            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="{{ old('razorpay_order_id') }}">
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="{{ old('razorpay_payment_id') }}">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="{{ old('razorpay_signature') }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}>{{ $s->admission_no }} - {{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fee Structure <span class="text-danger">*</span></label>
                    <select name="fee_structure_id" class="form-select" required>
                        <option value="">Select Fee</option>
                        @foreach($structures as $s)
                            <option value="{{ $s->id }}" {{ old('fee_structure_id') == $s->id ? 'selected' : '' }}>{{ $s->feeCategory->name }} - {{ $s->schoolClass->name }} (₹{{ number_format($s->amount) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Paid (₹) <span class="text-danger">*</span></label>
                    <input id="amount_paid" type="number" name="amount_paid" class="form-control" step="0.01" value="{{ old('amount_paid') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount (₹)</label>
                    <input type="number" name="discount" class="form-control" step="0.01" value="{{ old('discount', 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fine (₹)</label>
                    <input type="number" name="fine" class="form-control" step="0.01" value="{{ old('fine', 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select id="payment_method" name="payment_method" class="form-select" required>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="online" {{ old('payment_method') === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transaction ID</label>
                    <input id="transaction_id" type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ old('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <div id="onlineGatewayNotice" class="col-12 d-none">
                    <div class="alert alert-warning mb-0">
                        Razorpay is not enabled in payment settings. Enter the online transaction ID manually.
                    </div>
                </div>

                <div id="razorpaySection" class="col-12 d-none">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">Razorpay Checkout</h6>
                                <p class="text-muted small mb-0">Use the button below to collect fee online and auto-fill verified transaction details.</p>
                            </div>
                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Configured</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" id="payWithRazorpayBtn" class="btn btn-success">
                                <i class="bi bi-credit-card me-1"></i>Pay with Razorpay
                            </button>
                            <small id="razorpayStatus" class="text-muted">Awaiting payment.</small>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
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

@if($razorpayReady)
    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endpush
@endif

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('feePaymentForm');
        if (!form) {
            return;
        }

        const paymentMethodEl = document.getElementById('payment_method');
        const amountEl = document.getElementById('amount_paid');
        const transactionEl = document.getElementById('transaction_id');
        const statusEl = document.getElementById('status');
        const razorpaySectionEl = document.getElementById('razorpaySection');
        const onlineGatewayNoticeEl = document.getElementById('onlineGatewayNotice');
        const payWithRazorpayBtn = document.getElementById('payWithRazorpayBtn');
        const razorpayStatusEl = document.getElementById('razorpayStatus');

        const orderIdEl = document.getElementById('razorpay_order_id');
        const paymentIdEl = document.getElementById('razorpay_payment_id');
        const signatureEl = document.getElementById('razorpay_signature');

        const razorpayReady = {{ $razorpayReady ? 'true' : 'false' }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function resetRazorpayPayload() {
            orderIdEl.value = '';
            paymentIdEl.value = '';
            signatureEl.value = '';
        }

        function setStatus(message, className) {
            razorpayStatusEl.className = className;
            razorpayStatusEl.textContent = message;
        }

        function toggleOnlineMode() {
            const isOnline = paymentMethodEl.value === 'online';

            onlineGatewayNoticeEl.classList.toggle('d-none', !(isOnline && !razorpayReady));
            razorpaySectionEl.classList.toggle('d-none', !(isOnline && razorpayReady));

            if (isOnline && razorpayReady) {
                transactionEl.readOnly = true;
                transactionEl.placeholder = 'Auto-filled after successful Razorpay payment';
            } else {
                transactionEl.readOnly = false;
                transactionEl.placeholder = '';
                if (!isOnline) {
                    resetRazorpayPayload();
                    setStatus('Awaiting payment.', 'text-muted');
                }
            }
        }

        async function requestRazorpayOrder(amount) {
            const response = await fetch("{{ route('api.fees.razorpay.order') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ amount: amount }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Unable to create Razorpay order.');
            }

            return data;
        }

        async function verifyRazorpaySignature(payload) {
            const response = await fetch("{{ route('api.fees.razorpay.verify') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok || !data.verified) {
                throw new Error(data.message || 'Payment verification failed.');
            }

            return true;
        }

        if (payWithRazorpayBtn) {
            payWithRazorpayBtn.addEventListener('click', async function () {
                const amount = Number(amountEl.value || 0);
                if (!amount || amount <= 0) {
                    setStatus('Enter a valid amount before starting online payment.', 'text-danger');
                    amountEl.focus();
                    return;
                }

                if (typeof window.Razorpay === 'undefined') {
                    setStatus('Razorpay checkout script did not load. Refresh and try again.', 'text-danger');
                    return;
                }

                payWithRazorpayBtn.disabled = true;
                setStatus('Creating Razorpay order...', 'text-primary');

                try {
                    const order = await requestRazorpayOrder(amount);

                    const options = {
                        key: order.key_id,
                        amount: order.amount,
                        currency: order.currency,
                        name: order.name,
                        description: order.description,
                        order_id: order.order_id,
                        prefill: {
                            name: "{{ auth()->user()->name }}",
                            email: "{{ auth()->user()->email }}",
                        },
                        theme: {
                            color: '#2563eb',
                        },
                        handler: async function (response) {
                            const verifyPayload = {
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                            };

                            await verifyRazorpaySignature(verifyPayload);

                            orderIdEl.value = response.razorpay_order_id;
                            paymentIdEl.value = response.razorpay_payment_id;
                            signatureEl.value = response.razorpay_signature;
                            transactionEl.value = response.razorpay_payment_id;
                            statusEl.value = 'paid';

                            setStatus('Payment verified. You can record the payment now.', 'text-success');
                        },
                        modal: {
                            ondismiss: function () {
                                if (!transactionEl.value) {
                                    setStatus('Payment cancelled by user.', 'text-warning');
                                }
                            },
                        },
                    };

                    const checkout = new Razorpay(options);
                    checkout.on('payment.failed', function (response) {
                        const message = response?.error?.description || 'Payment failed. Please retry.';
                        setStatus(message, 'text-danger');
                    });
                    checkout.open();
                } catch (error) {
                    setStatus(error.message || 'Unable to process Razorpay payment.', 'text-danger');
                } finally {
                    payWithRazorpayBtn.disabled = false;
                }
            });
        }

        form.addEventListener('submit', function (event) {
            const isOnline = paymentMethodEl.value === 'online';
            if (!isOnline || !razorpayReady) {
                return;
            }

            if (!orderIdEl.value || !paymentIdEl.value || !signatureEl.value) {
                event.preventDefault();
                setStatus('Complete Razorpay checkout before recording an online payment.', 'text-danger');
            }
        });

        paymentMethodEl.addEventListener('change', toggleOnlineMode);
        toggleOnlineMode();
    })();
</script>
@endpush
