@extends('layouts.app')
@section('title', 'My Fees')
@section('page-title', 'My Fees')

@section('content')
@php
    $razorpayReady = $gatewaySettings?->is_enabled
        && !blank($gatewaySettings?->key_id)
        && !blank($gatewaySettings?->key_secret);
@endphp

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
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feeOverview['items'] as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item['student']->full_name }}</td>
                        <td>{{ $item['student']->schoolClass?->name ?? '-' }}</td>
                        <td>{{ $item['fee_head'] ?? ($item['structure']->display_name ?? '-') }}</td>
                        <td>Rs {{ number_format($item['total_amount'] ?? ($item['structure']->amount ?? 0), 2) }}</td>
                        <td>Rs {{ number_format($item['paid_amount'], 2) }}</td>
                        <td class="{{ $item['due_amount'] > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                            Rs {{ number_format($item['due_amount'], 2) }}
                        </td>
                        <td class="text-end">
                            @if($item['due_amount'] > 0 && ($item['can_pay_online'] ?? true) && $item['structure'])
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary pay-online-btn"
                                    data-student-id="{{ $item['student']->id }}"
                                    data-student-name="{{ $item['student']->full_name }}"
                                    data-fee-structure-id="{{ $item['structure']->id }}"
                                    data-fee-name="{{ $item['structure']->display_name ?? 'Fee' }}"
                                    data-due-amount="{{ $item['due_amount'] }}"
                                    {{ $razorpayReady ? '' : 'disabled' }}
                                >
                                    <i class="bi bi-credit-card me-1"></i>Pay Online
                                </button>
                            @elseif($item['due_amount'] > 0)
                                <span class="badge bg-warning text-dark">Pay at school office</span>
                            @else
                                <span class="badge bg-success">Cleared</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No fee records available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!$razorpayReady)
    <div class="alert alert-warning">
        Online payment is currently unavailable because Razorpay is not configured in payment settings.
    </div>
@endif

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
                        <td>{{ $payment->feeStructure?->display_name ?? '-' }}</td>
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

<div class="modal fade" id="payOnlineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="myFeePaymentForm" method="POST" action="{{ route('fees.my-fees.pay-online') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Pay Fees Online</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="pay_student_id">
                    <input type="hidden" name="fee_structure_id" id="pay_fee_structure_id">
                    <input type="hidden" name="razorpay_order_id" id="pay_razorpay_order_id">
                    <input type="hidden" name="razorpay_payment_id" id="pay_razorpay_payment_id">
                    <input type="hidden" name="razorpay_signature" id="pay_razorpay_signature">

                    <div class="mb-3">
                        <div class="fw-semibold" id="pay_student_name">Student</div>
                        <small class="text-muted" id="pay_fee_name">Fee Head</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Amount</label>
                        <input type="text" id="pay_due_amount_display" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount to Pay</label>
                        <input type="number" name="amount_paid" id="pay_amount_paid" class="form-control" min="1" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="small text-muted" id="pay_online_status">Proceed to Razorpay checkout to complete payment.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="startRazorpayPaymentBtn">
                        <i class="bi bi-credit-card me-1"></i>Pay with Razorpay
                    </button>
                </div>
            </form>
        </div>
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
        const modalElement = document.getElementById('payOnlineModal');
        if (!modalElement) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const razorpayReady = {{ $razorpayReady ? 'true' : 'false' }};
        const payButtons = document.querySelectorAll('.pay-online-btn');
        const payStudentId = document.getElementById('pay_student_id');
        const payFeeStructureId = document.getElementById('pay_fee_structure_id');
        const payStudentName = document.getElementById('pay_student_name');
        const payFeeName = document.getElementById('pay_fee_name');
        const payDueAmountDisplay = document.getElementById('pay_due_amount_display');
        const payAmountInput = document.getElementById('pay_amount_paid');
        const payOrderId = document.getElementById('pay_razorpay_order_id');
        const payPaymentId = document.getElementById('pay_razorpay_payment_id');
        const paySignature = document.getElementById('pay_razorpay_signature');
        const payStatus = document.getElementById('pay_online_status');
        const payButton = document.getElementById('startRazorpayPaymentBtn');
        const form = document.getElementById('myFeePaymentForm');

        function resetGatewayFields() {
            payOrderId.value = '';
            payPaymentId.value = '';
            paySignature.value = '';
        }

        function setStatus(message, className) {
            payStatus.className = className;
            payStatus.textContent = message;
        }

        payButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                payStudentId.value = button.dataset.studentId;
                payFeeStructureId.value = button.dataset.feeStructureId;
                payStudentName.textContent = button.dataset.studentName;
                payFeeName.textContent = button.dataset.feeName;
                payDueAmountDisplay.value = Number(button.dataset.dueAmount).toFixed(2);
                payAmountInput.value = Number(button.dataset.dueAmount).toFixed(2);
                payAmountInput.max = Number(button.dataset.dueAmount).toFixed(2);
                resetGatewayFields();
                setStatus('Proceed to Razorpay checkout to complete payment.', 'small text-muted');
                modal.show();
            });
        });

        if (!razorpayReady || !payButton) {
            return;
        }

        payButton.addEventListener('click', async function () {
            const amount = Number(payAmountInput.value || 0);
            const dueAmount = Number(payDueAmountDisplay.value || 0);

            if (!amount || amount <= 0) {
                setStatus('Enter a valid payment amount.', 'small text-danger');
                payAmountInput.focus();
                return;
            }

            if (amount > dueAmount) {
                setStatus('Amount cannot exceed the due fee amount.', 'small text-danger');
                payAmountInput.focus();
                return;
            }

            payButton.disabled = true;
            setStatus('Creating Razorpay order...', 'small text-primary');

            try {
                const orderResponse = await fetch("{{ route('fees.my-fees.razorpay.order') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        student_id: payStudentId.value,
                        fee_structure_id: payFeeStructureId.value,
                        amount: amount,
                    }),
                });

                const orderData = await orderResponse.json();
                if (!orderResponse.ok) {
                    throw new Error(orderData.message || 'Unable to create payment order.');
                }

                const checkout = new Razorpay({
                    key: orderData.key_id,
                    amount: orderData.amount,
                    currency: orderData.currency,
                    name: orderData.name,
                    description: orderData.description,
                    order_id: orderData.order_id,
                    prefill: {
                        name: "{{ auth()->user()->name }}",
                        email: "{{ auth()->user()->email }}",
                    },
                    theme: {
                        color: '#2563eb',
                    },
                    handler: async function (response) {
                        const verifyResponse = await fetch("{{ route('api.fees.razorpay.verify') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                            }),
                        });

                        const verifyData = await verifyResponse.json();
                        if (!verifyResponse.ok || !verifyData.verified) {
                            throw new Error(verifyData.message || 'Payment verification failed.');
                        }

                        payOrderId.value = response.razorpay_order_id;
                        payPaymentId.value = response.razorpay_payment_id;
                        paySignature.value = response.razorpay_signature;
                        setStatus('Payment verified. Saving your payment record...', 'small text-success');
                        form.submit();
                    },
                    modal: {
                        ondismiss: function () {
                            setStatus('Payment was cancelled.', 'small text-warning');
                        },
                    },
                });

                checkout.on('payment.failed', function (response) {
                    const message = response?.error?.description || 'Payment failed. Please retry.';
                    setStatus(message, 'small text-danger');
                });

                checkout.open();
            } catch (error) {
                setStatus(error.message || 'Unable to process online payment.', 'small text-danger');
            } finally {
                payButton.disabled = false;
            }
        });
    })();
</script>
@endpush
