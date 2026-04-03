@extends('layouts.app')
@section('title', 'Record Payment')
@section('page-title', 'Record Fee Payment')

@section('content')
@php
    $razorpayReady = $gatewaySettings->is_enabled
        && !blank($gatewaySettings->key_id)
        && !blank($gatewaySettings->key_secret);

    $studentPayload = $students->map(fn ($student) => [
        'id' => $student->id,
        'label' => trim($student->admission_no . ' - ' . $student->full_name),
        'full_name' => $student->full_name,
        'admission_no' => $student->admission_no,
        'class_name' => $student->schoolClass?->name,
        'section_name' => $student->section?->name,
        'parent_name' => $student->parentUser?->name,
        'search' => strtolower(implode(' ', array_filter([
            $student->admission_no,
            $student->full_name,
            $student->schoolClass?->name,
            $student->section?->name,
            $student->father_name,
            $student->mother_name,
            $student->guardian_name,
            $student->parentUser?->name,
        ]))),
    ])->values();
@endphp

<div class="card table-card">
    <div class="card-body">
        <form id="feePaymentForm" method="POST" action="{{ route('fees.payments.store') }}">
            @csrf

            <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id', $selectedStudent?->id) }}">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="{{ old('razorpay_order_id') }}">
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="{{ old('razorpay_payment_id') }}">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="{{ old('razorpay_signature') }}">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Quick Student Search <span class="text-danger">*</span></label>
                    <input type="text" id="student_search" class="form-control" placeholder="Search by student name, admission number, class, parent, guardian" autocomplete="off" value="{{ $selectedStudent ? $selectedStudent->admission_no . ' - ' . $selectedStudent->full_name : '' }}" required>
                    <div class="form-text">Search first, select the student, then choose the fee head and record payment.</div>
                    <div id="student_search_results" class="list-group mt-2 d-none"></div>
                </div>

                <div class="col-12">
                    <div id="selected_student_card" class="border rounded p-3 bg-light {{ old('student_id', $selectedStudent?->id) ? '' : 'd-none' }}">
                        <div class="fw-semibold" id="selected_student_name">{{ $selectedStudent?->full_name }}</div>
                        <small class="text-muted" id="selected_student_meta">
                            @if($selectedStudent)
                                {{ collect([$selectedStudent->admission_no, $selectedStudent->schoolClass?->name, $selectedStudent->section?->name, $selectedStudent->parentUser?->name ? 'Parent: ' . $selectedStudent->parentUser->name : null])->filter()->join(' | ') }}
                            @endif
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fee Structure <span class="text-danger">*</span></label>
                    <select name="fee_structure_id" id="fee_structure_id" class="form-select" required>
                        <option value="">Select Fee</option>
                        @foreach($selectedStructures as $structure)
                            <option value="{{ $structure->id }}" {{ old('fee_structure_id') == $structure->id ? 'selected' : '' }}>
                                {{ $structure->feeCategory->name }} - {{ $structure->schoolClass->name }} (Rs {{ number_format($structure->amount) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Paid (Rs) <span class="text-danger">*</span></label>
                    <input id="amount_paid" type="number" name="amount_paid" class="form-control" step="0.01" value="{{ old('amount_paid') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount (Rs)</label>
                    <input type="number" name="discount" class="form-control" step="0.01" value="{{ old('discount', 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fine (Rs)</label>
                    <input type="number" name="fine" class="form-control" step="0.01" value="{{ old('fine', 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Location <span class="text-danger">*</span></label>
                    <select id="payment_location" name="payment_location" class="form-select" required>
                        <option value="school" {{ old('payment_location', 'school') === 'school' ? 'selected' : '' }}>School</option>
                        <option value="bank" {{ old('payment_location') === 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                    <select id="payment_channel" name="payment_channel" class="form-select" required>
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Fully Paid</option>
                        <option value="partial" {{ old('status') === 'partial' ? 'selected' : '' }}>Partially Paid</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Transaction ID</label>
                    <input id="transaction_id" type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UTR Number</label>
                    <input type="text" name="utr_number" class="form-control" value="{{ old('utr_number') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cheque Number</label>
                    <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number') }}">
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

        const paymentLocationEl = document.getElementById('payment_location');
        const paymentChannelEl = document.getElementById('payment_channel');
        const amountEl = document.getElementById('amount_paid');
        const transactionEl = document.getElementById('transaction_id');
        const statusEl = document.getElementById('status');
        const studentIdEl = document.getElementById('student_id');
        const studentSearchEl = document.getElementById('student_search');
        const studentResultsEl = document.getElementById('student_search_results');
        const selectedStudentCardEl = document.getElementById('selected_student_card');
        const selectedStudentNameEl = document.getElementById('selected_student_name');
        const selectedStudentMetaEl = document.getElementById('selected_student_meta');
        const feeStructureEl = document.getElementById('fee_structure_id');
        const razorpaySectionEl = document.getElementById('razorpaySection');
        const onlineGatewayNoticeEl = document.getElementById('onlineGatewayNotice');
        const payWithRazorpayBtn = document.getElementById('payWithRazorpayBtn');
        const razorpayStatusEl = document.getElementById('razorpayStatus');
        const orderIdEl = document.getElementById('razorpay_order_id');
        const paymentIdEl = document.getElementById('razorpay_payment_id');
        const signatureEl = document.getElementById('razorpay_signature');

        const razorpayReady = {{ $razorpayReady ? 'true' : 'false' }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const students = @json($studentPayload);
        const selectedFeeStructureId = "{{ old('fee_structure_id') }}";
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

        function resetRazorpayPayload() {
            orderIdEl.value = '';
            paymentIdEl.value = '';
            signatureEl.value = '';
        }

        function setStatus(message, className) {
            razorpayStatusEl.className = className;
            razorpayStatusEl.textContent = message;
        }

        function renderFeeStructures(structures) {
            feeStructureEl.innerHTML = '<option value="">Select Fee</option>';

            structures.forEach(function (structure) {
                const option = document.createElement('option');
                option.value = structure.id;
                option.textContent = structure.fee_category.name + ' - ' + (structure.school_class?.name || '') + ' (Rs ' + Number(structure.amount).toLocaleString() + ')';
                if (String(structure.id) === String(selectedFeeStructureId)) {
                    option.selected = true;
                }
                feeStructureEl.appendChild(option);
            });
        }

        async function loadStudentFees(studentId) {
            const response = await fetch('/api/students/' + studentId + '/fees', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                throw new Error('Unable to load fee structures for the selected student.');
            }

            const structures = await response.json();
            renderFeeStructures(structures);
        }

        function hideStudentResults() {
            studentResultsEl.classList.add('d-none');
            studentResultsEl.innerHTML = '';
        }

        function selectStudent(student) {
            studentIdEl.value = student.id;
            studentSearchEl.value = student.label;
            selectedStudentCardEl.classList.remove('d-none');
            selectedStudentNameEl.textContent = student.full_name;
            selectedStudentMetaEl.textContent = [student.admission_no, student.class_name, student.section_name, student.parent_name ? 'Parent: ' + student.parent_name : '']
                .filter(Boolean)
                .join(' | ');
            hideStudentResults();
            loadStudentFees(student.id).catch(function (error) {
                feeStructureEl.innerHTML = '<option value="">Select Fee</option>';
                alert(error.message);
            });
        }

        function showStudentResults(filteredStudents) {
            studentResultsEl.innerHTML = '';

            if (!filteredStudents.length) {
                hideStudentResults();
                return;
            }

            filteredStudents.slice(0, 8).forEach(function (student) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = '<div class="fw-semibold">' + student.label + '</div><small class="text-muted">' + [student.class_name, student.section_name, student.parent_name].filter(Boolean).join(' | ') + '</small>';
                button.addEventListener('click', function () {
                    selectStudent(student);
                });
                studentResultsEl.appendChild(button);
            });

            studentResultsEl.classList.remove('d-none');
        }

        function renderPaymentModes() {
            const selectedValue = paymentChannelEl.value || "{{ old('payment_channel', 'cash') }}";
            const options = locationModeMap[paymentLocationEl.value] || [];

            paymentChannelEl.innerHTML = '<option value="">Select</option>';
            options.forEach(function (option) {
                const selected = option.value === selectedValue ? 'selected' : '';
                paymentChannelEl.innerHTML += `<option value="${option.value}" ${selected}>${option.label}</option>`;
            });
        }

        function toggleOnlineMode() {
            const isOnline = paymentChannelEl.value === 'upi';

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

        studentSearchEl.addEventListener('input', function () {
            const term = studentSearchEl.value.trim().toLowerCase();

            if (!term) {
                studentIdEl.value = '';
                selectedStudentCardEl.classList.add('d-none');
                feeStructureEl.innerHTML = '<option value="">Select Fee</option>';
                hideStudentResults();
                return;
            }

            showStudentResults(students.filter(function (student) {
                return student.search.includes(term);
            }));
        });

        document.addEventListener('click', function (event) {
            if (!studentResultsEl.contains(event.target) && event.target !== studentSearchEl) {
                hideStudentResults();
            }
        });

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
            if (!studentIdEl.value) {
                event.preventDefault();
                alert('Select a student before recording payment.');
                studentSearchEl.focus();
                return;
            }

            const isOnline = paymentChannelEl.value === 'upi';
            if (!isOnline || !razorpayReady) {
                return;
            }

            if (!orderIdEl.value || !paymentIdEl.value || !signatureEl.value) {
                event.preventDefault();
                setStatus('Complete Razorpay checkout before recording an online payment.', 'text-danger');
            }
        });

        paymentLocationEl.addEventListener('change', function () {
            renderPaymentModes();
            toggleOnlineMode();
        });
        paymentChannelEl.addEventListener('change', toggleOnlineMode);
        renderPaymentModes();
        toggleOnlineMode();

        if (studentIdEl.value) {
            const selectedStudent = students.find(function (student) {
                return String(student.id) === String(studentIdEl.value);
            });

            if (selectedStudent) {
                selectStudent(selectedStudent);
            }
        }
    })();
</script>
@endpush
