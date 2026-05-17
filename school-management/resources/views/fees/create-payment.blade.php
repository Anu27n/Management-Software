@extends('layouts.app')
@section('title', 'Record Payment')
@section('page-title', 'Record Fee Payment')

@section('content')
@php
    $studentPayload = $students->map(fn ($student) => [
        'id' => $student->id,
        'label' => trim($student->admission_no . ' - ' . $student->full_name),
        'full_name' => $student->full_name,
        'admission_no' => $student->admission_no,
        'class_name' => $student->schoolClass?->name,
        'section_name' => $student->section?->name,
        'parent_name' => $student->parentUser?->name,
        'bb_number' => $student->profile?->fee_booklet_number,
        'search' => strtolower(implode(' ', array_filter([
            $student->admission_no,
            $student->full_name,
            $student->schoolClass?->name,
            $student->section?->name,
            $student->father_name,
            $student->mother_name,
            $student->guardian_name,
            $student->parentUser?->name,
            $student->profile?->fee_booklet_number,
        ]))),
    ])->values();
    $discountPresetPayload = $discountPresets->map(fn ($preset) => [
        'id' => $preset->id,
        'name' => $preset->name,
        'fee_category_id' => $preset->fee_category_id,
        'discount_type' => $preset->discount_type,
        'value' => (float) $preset->value,
        'label' => $preset->name . ' (' . ($preset->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $preset->value, 2), '0'), '.') . '%' : 'Rs ' . number_format((float) $preset->value, 2)) . ')',
    ])->values();
@endphp

<div class="card table-card">
    <div class="card-body">
        <form id="feePaymentForm" method="POST" action="{{ route('fees.payments.store') }}">
            @csrf

            <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id', $selectedStudent?->id) }}">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Quick Student Search <span class="text-danger">*</span></label>
                    <input type="text" id="student_search" class="form-control" placeholder="Search by student name, admission number, class, parent, guardian" autocomplete="off" value="{{ $selectedStudent ? $selectedStudent->admission_no . ' - ' . $selectedStudent->full_name : '' }}" required>
                    <div class="form-text">Select student to load assigned fee heads. Paid heads are locked automatically.</div>
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

                <div class="col-md-3">
                    <label class="form-label">B.B Number</label>
                    <input type="text" name="bb_number" id="bb_number" class="form-control" value="{{ old('bb_number') }}" placeholder="Auto from admission; editable">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                </div>
                <input type="hidden" name="discount" value="{{ old('discount', 0) }}">
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

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>

                <div class="col-12" id="feeChartContainer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h6 class="mb-0 fw-semibold">Fees Chart</h6>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" id="printChartBtn"><i class="bi bi-printer me-1"></i>Print</button>
                            <button type="button" class="btn btn-outline-danger" id="pdfChartBtn"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</button>
                        </div>
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm align-middle mb-0" id="feeChartTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">S.No.</th>
                                    <th>Fee Head</th>
                                    <th style="width: 140px;">Assigned</th>
                                    <th style="width: 140px;">Paid</th>
                                    <th style="width: 140px;">Discounted</th>
                                    <th style="width: 140px;">Due</th>
                                    <th style="width: 260px;">Discount Now</th>
                                    <th style="width: 220px;">Collect Now</th>
                                </tr>
                            </thead>
                            <tbody id="feeChartBody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">Select a student to load fee chart.</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th id="assignedTotalCell">Rs 0.00</th>
                                    <th id="paidTotalCell">Rs 0.00</th>
                                    <th id="discountTotalCell">Rs 0.00</th>
                                    <th id="dueTotalCell">Rs 0.00</th>
                                    <th id="discountNowTotalCell">Rs 0.00</th>
                                    <th id="collectTotalCell">Rs 0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Paid rows are locked. Enter amount only in due rows to collect quickly.</small>
                </div>

                <div class="col-12 d-none print-only" id="printSignatures">
                    <div class="d-flex justify-content-between mt-5">
                        <div class="text-center" style="width: 45%;">
                            <div style="border-top: 1px solid #333; margin-bottom: 4px;"></div>
                            <strong>Sign. of Accountant</strong>
                        </div>
                        <div class="text-center" style="width: 45%;">
                            <div style="border-top: 1px solid #333; margin-bottom: 4px;"></div>
                            <strong>Sign. of Office - Incharge</strong>
                        </div>
                    </div>
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

@push('styles')
<style>
    @media print {
        .btn, .form-text, #student_search_results, .navbar, .sidebar, .main-header {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('feePaymentForm');
        if (!form) {
            return;
        }

        const paymentLocationEl = document.getElementById('payment_location');
        const paymentChannelEl = document.getElementById('payment_channel');
        const studentIdEl = document.getElementById('student_id');
        const bbNumberEl = document.getElementById('bb_number');
        const studentSearchEl = document.getElementById('student_search');
        const studentResultsEl = document.getElementById('student_search_results');
        const selectedStudentCardEl = document.getElementById('selected_student_card');
        const selectedStudentNameEl = document.getElementById('selected_student_name');
        const selectedStudentMetaEl = document.getElementById('selected_student_meta');
        const feeChartBodyEl = document.getElementById('feeChartBody');
        const assignedTotalCellEl = document.getElementById('assignedTotalCell');
        const paidTotalCellEl = document.getElementById('paidTotalCell');
        const discountTotalCellEl = document.getElementById('discountTotalCell');
        const dueTotalCellEl = document.getElementById('dueTotalCell');
        const discountNowTotalCellEl = document.getElementById('discountNowTotalCell');
        const collectTotalCellEl = document.getElementById('collectTotalCell');
        const printChartBtn = document.getElementById('printChartBtn');
        const pdfChartBtn = document.getElementById('pdfChartBtn');
        const feeChartContainerEl = document.getElementById('feeChartContainer');
        const students = @json($studentPayload);
        const discountPresets = @json($discountPresetPayload);
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

        function currency(amount) {
            return 'Rs ' + Number(amount || 0).toFixed(2);
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function recalculateCollectTotal() {
            const inputs = feeChartBodyEl.querySelectorAll('input[data-collect-input="1"]');
            const discountInputs = feeChartBodyEl.querySelectorAll('input[data-discount-input="1"]');
            let total = 0;
            let discountTotal = 0;
            inputs.forEach(function (input) {
                total += Number(input.value || 0);
            });
            discountInputs.forEach(function (input) {
                discountTotal += Number(input.value || 0);
            });
            collectTotalCellEl.textContent = currency(total);
            discountNowTotalCellEl.textContent = currency(discountTotal);
        }

        function getDueInputState() {
            const allInputs = Array.from(feeChartBodyEl.querySelectorAll('input[data-collect-input="1"]'));
            const allDiscountInputs = Array.from(feeChartBodyEl.querySelectorAll('input[data-discount-input="1"]'));
            const dueInputs = allInputs.filter(function (input) {
                return !input.disabled && Number(input.max || 0) > 0;
            });
            const dueDiscountInputs = allDiscountInputs.filter(function (input) {
                return !input.disabled && Number(input.max || 0) > 0;
            });
            const hasAnyAmount = dueInputs.concat(dueDiscountInputs).some(function (input) {
                return Number(input.value || 0) > 0;
            });

            return {
                dueInputs: dueInputs,
                hasAnyAmount: hasAnyAmount,
            };
        }

        async function loadStudentFeesChart(studentId) {
            const response = await fetch('/api/students/' + studentId + '/fees', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                throw new Error('Unable to load fee structures for the selected student.');
            }

            const payload = await response.json();
            const rows = payload.rows || [];
            let assignedTotal = 0;
            let paidTotal = 0;
            let discountTotal = 0;
            let dueTotal = 0;

            feeChartBodyEl.innerHTML = '';

            if (!rows.length) {
                feeChartBodyEl.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No assigned fee heads found for this student.</td></tr>';
            } else {
                rows.forEach(function (row, index) {
                    assignedTotal += Number(row.assigned_amount || 0);
                    paidTotal += Number(row.paid_amount || 0);
                    discountTotal += Number(row.discount_amount || 0);
                    dueTotal += Number(row.due_amount || 0);

                    const tr = document.createElement('tr');
                    const lockedBadge = row.is_locked ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Due</span>';
                    const matchingPresets = discountPresets.filter(function (preset) {
                        return !preset.fee_category_id || String(preset.fee_category_id) === String(row.fee_category_id);
                    });
                    const presetOptions = matchingPresets.map(function (preset) {
                        return `<option value="${preset.id}" data-type="${escapeHtml(preset.discount_type)}" data-value="${Number(preset.value || 0)}">${escapeHtml(preset.label)}</option>`;
                    }).join('');

                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td><div class="fw-semibold">${escapeHtml(row.fee_head)}</div>${lockedBadge}</td>
                        <td>${currency(row.assigned_amount)}</td>
                        <td>${currency(row.paid_amount)}</td>
                        <td class="text-success fw-semibold">${currency(row.discount_amount)}</td>
                        <td class="${Number(row.due_amount) > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold'}">${currency(row.due_amount)}</td>
                        <td>
                            <div class="input-group input-group-sm mb-1">
                                <select class="form-select" name="payments[${index}][discount_preset_id]" data-discount-preset="1" ${row.is_locked ? 'disabled' : ''}>
                                    <option value="">Manual</option>
                                    ${presetOptions}
                                </select>
                            </div>
                            <input type="number" class="form-control form-control-sm"
                                name="payments[${index}][discount]"
                                min="0"
                                max="${Number(row.due_amount).toFixed(2)}"
                                step="0.01"
                                value=""
                                placeholder="${row.is_locked ? 'Locked' : '0.00'}"
                                data-discount-input="1"
                                data-due-amount="${Number(row.due_amount).toFixed(2)}"
                                ${row.is_locked ? 'disabled' : ''}>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm"
                                name="payments[${index}][amount]"
                                min="0"
                                max="${Number(row.due_amount).toFixed(2)}"
                                step="0.01"
                                value=""
                                placeholder="${row.is_locked ? 'Locked' : '0.00'}"
                                data-collect-input="1"
                                ${row.is_locked ? 'disabled' : ''}>
                            <input type="hidden" name="payments[${index}][fee_structure_id]" value="${row.id}">
                        </td>
                    `;

                    feeChartBodyEl.appendChild(tr);
                });
            }

            assignedTotalCellEl.textContent = currency(assignedTotal);
            paidTotalCellEl.textContent = currency(paidTotal);
            discountTotalCellEl.textContent = currency(discountTotal);
            dueTotalCellEl.textContent = currency(dueTotal);
            recalculateCollectTotal();

            if (payload.bb_number && !bbNumberEl.value) {
                bbNumberEl.value = payload.bb_number;
            }

            feeChartBodyEl.querySelectorAll('input[data-collect-input="1"], input[data-discount-input="1"]').forEach(function (input) {
                input.addEventListener('input', function () {
                    input.classList.remove('is-invalid');
                    const rowEl = input.closest('tr');
                    const collectInput = rowEl ? rowEl.querySelector('input[data-collect-input="1"]') : null;
                    const discountInput = rowEl ? rowEl.querySelector('input[data-discount-input="1"]') : null;
                    const due = Number((discountInput && discountInput.dataset.dueAmount) || input.max || 0);
                    const other = input === collectInput ? Number(discountInput.value || 0) : Number(collectInput.value || 0);
                    const max = Math.max(0, due - other);
                    const val = Number(input.value || 0);
                    if (val > max) {
                        input.value = max.toFixed(2);
                    }
                    recalculateCollectTotal();
                });
            });

            feeChartBodyEl.querySelectorAll('select[data-discount-preset="1"]').forEach(function (select) {
                select.addEventListener('change', function () {
                    const selected = select.options[select.selectedIndex];
                    const rowEl = select.closest('tr');
                    const discountInput = rowEl ? rowEl.querySelector('input[data-discount-input="1"]') : null;
                    const collectInput = rowEl ? rowEl.querySelector('input[data-collect-input="1"]') : null;
                    if (!discountInput || !selected || !selected.value) {
                        return;
                    }

                    const due = Number(discountInput.dataset.dueAmount || 0);
                    const currentCollect = Number(collectInput.value || 0);
                    const available = Math.max(0, due - currentCollect);
                    const presetType = selected.dataset.type;
                    const presetValue = Number(selected.dataset.value || 0);
                    const discount = presetType === 'percentage' ? (due * presetValue / 100) : presetValue;

                    discountInput.value = Math.min(available, discount).toFixed(2);
                    recalculateCollectTotal();
                });
            });
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
            if (student.bb_number && !bbNumberEl.value) {
                bbNumberEl.value = student.bb_number;
            }
            loadStudentFeesChart(student.id).catch(function (error) {
                feeChartBodyEl.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Unable to load fee chart.</td></tr>';
                alert(error.message);
            });
            if (feeChartContainerEl && typeof feeChartContainerEl.scrollIntoView === 'function') {
                feeChartContainerEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function showStudentResults(filteredStudents) {
            studentResultsEl.innerHTML = '';

            if (!filteredStudents.length) {
                hideStudentResults();
                return;
            }

            const countBadge = document.createElement('div');
            countBadge.className = 'list-group-item list-group-item-light small text-muted';
            countBadge.textContent = filteredStudents.length + ' student(s) found';
            studentResultsEl.appendChild(countBadge);

            filteredStudents.forEach(function (student) {
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

        studentSearchEl.addEventListener('input', function () {
            const term = studentSearchEl.value.trim().toLowerCase();

            if (!term) {
                studentIdEl.value = '';
                selectedStudentCardEl.classList.add('d-none');
                feeChartBodyEl.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Select a student to load fee chart.</td></tr>';
                assignedTotalCellEl.textContent = currency(0);
                paidTotalCellEl.textContent = currency(0);
                discountTotalCellEl.textContent = currency(0);
                dueTotalCellEl.textContent = currency(0);
                discountNowTotalCellEl.textContent = currency(0);
                collectTotalCellEl.textContent = currency(0);
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

        form.addEventListener('submit', function (event) {
            if (!studentIdEl.value) {
                event.preventDefault();
                alert('Select a student before recording payment.');
                studentSearchEl.focus();
                return;
            }

            const state = getDueInputState();
            if (!state.hasAnyAmount) {
                event.preventDefault();
                if (state.dueInputs.length === 0) {
                    alert('All fee heads for this student are already paid. Select another student to test collection.');
                    return;
                }

                state.dueInputs.forEach(function (input) {
                    input.classList.add('is-invalid');
                });
                alert('Enter payment or discount in at least one due fee head.');
                state.dueInputs[0].focus();
            }
        });

        function openPrintDialog() {
            window.print();
        }

        if (printChartBtn) {
            printChartBtn.addEventListener('click', openPrintDialog);
        }
        if (pdfChartBtn) {
            pdfChartBtn.addEventListener('click', openPrintDialog);
        }

        renderPaymentModes();

        paymentLocationEl.addEventListener('change', function () {
            renderPaymentModes();
        });

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
