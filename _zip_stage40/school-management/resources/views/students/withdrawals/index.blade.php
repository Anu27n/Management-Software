@extends('layouts.app')
@section('title', 'Student Withdrawals')
@section('page-title', 'Student Withdrawals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Withdrawal Workflow</h5>
    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">Back To Students</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name or phone number">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Class</label>
                <input type="text" name="class_search" class="form-control form-control-sm" value="{{ request('class_search') }}" placeholder="Search class">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Section</label>
                <input type="text" name="section_search" class="form-control form-control-sm" value="{{ request('section_search') }}" placeholder="Search section">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Search Results</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Class</th><th>Phone</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $student->full_name }}</div>
                                    <small class="text-muted">{{ $student->admission_no }}</small>
                                </td>
                                <td>{{ $student->schoolClass?->name }} {{ $student->section?->name ? '- ' . $student->section->name : '' }}</td>
                                <td>{{ $student->profile?->father_mobile_number ?: $student->profile?->mother_mobile_number ?: $student->profile?->phone_number ?: $student->phone ?: '-' }}</td>
                                <td>
                                    <a href="{{ route('students.withdrawals.index', array_merge(request()->query(), ['student_id' => $student->id])) }}" class="btn btn-outline-primary btn-sm">Select</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No matching students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $students->links() }}</div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Withdrawal Details</h6></div>
            <div class="card-body">
                @if($selectedStudent)
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="fw-semibold">{{ $selectedStudent->full_name }}</div>
                        <div class="small text-muted">{{ $selectedStudent->admission_no }} | {{ $selectedStudent->schoolClass?->name }} {{ $selectedStudent->section?->name ? '- ' . $selectedStudent->section->name : '' }}</div>
                        <div class="small text-muted mt-1">Security Amount: Rs {{ number_format((float) ($selectedStudent->profile?->security_amount ?? 0), 2) }}</div>
                        <div class="small text-muted">Security Receipt No: {{ $selectedStudent->profile?->security_receipt_number ?: '-' }}</div>
                    </div>

                    <form method="POST" action="{{ route('students.withdrawals.store') }}" id="withdrawalForm">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Withdrawal Date <span class="text-danger">*</span></label>
                                <input type="date" name="withdrawal_date" class="form-control" value="{{ old('withdrawal_date', optional($selectedStudent->withdrawal?->withdrawal_date)->format('Y-m-d') ?: date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TC Issued <span class="text-danger">*</span></label>
                                <select name="tc_issued" id="tc_issued" class="form-select" required>
                                    <option value="0" {{ old('tc_issued', $selectedStudent->withdrawal?->tc_issued ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('tc_issued', $selectedStudent->withdrawal?->tc_issued ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="2" required>{{ old('reason', $selectedStudent->withdrawal?->reason) }}</textarea>
                            </div>
                            <div class="col-md-6 tc-fields {{ old('tc_issued', $selectedStudent->withdrawal?->tc_issued ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">TC Number</label>
                                <input type="text" name="tc_number" class="form-control" value="{{ old('tc_number', $selectedStudent->withdrawal?->tc_number) }}">
                            </div>
                            <div class="col-md-6 tc-fields {{ old('tc_issued', $selectedStudent->withdrawal?->tc_issued ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">TC Date</label>
                                <input type="date" name="tc_date" class="form-control" value="{{ old('tc_date', optional($selectedStudent->withdrawal?->tc_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Security Refunded <span class="text-danger">*</span></label>
                                <select name="security_refunded" id="security_refunded" class="form-select" required>
                                    <option value="0" {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                            <div class="col-md-6 refund-fields {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">Refund Amount</label>
                                <input type="number" step="0.01" name="refund_amount" class="form-control" value="{{ old('refund_amount', $selectedStudent->withdrawal?->refund_amount ?? $selectedStudent->profile?->security_amount) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Security Amount</label>
                                <input type="text" class="form-control" value="Rs {{ number_format((float) ($selectedStudent->profile?->security_amount ?? 0), 2) }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Security Receipt Number</label>
                                <input type="text" class="form-control" value="{{ $selectedStudent->profile?->security_receipt_number ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-6 refund-fields {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">Refund Date</label>
                                <input type="date" name="refund_date" class="form-control" value="{{ old('refund_date', optional($selectedStudent->withdrawal?->refund_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 refund-fields {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">Payment Mode</label>
                                <select name="payment_mode" id="payment_mode" class="form-select">
                                    <option value="">Select</option>
                                    @foreach($paymentModes as $mode)
                                        <option value="{{ $mode }}" {{ old('payment_mode', $selectedStudent->withdrawal?->payment_mode) === $mode ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 refund-fields {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">UTR Number</label>
                                <input type="text" name="utr_number" class="form-control" value="{{ old('utr_number', $selectedStudent->withdrawal?->utr_number) }}">
                            </div>
                            <div class="col-md-6 refund-fields {{ old('security_refunded', $selectedStudent->withdrawal?->security_refunded ? '1' : '0') == '1' ? '' : 'd-none' }}">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number', $selectedStudent->withdrawal?->cheque_number) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $selectedStudent->withdrawal?->remarks) }}</textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" type="submit">Save Withdrawal</button>
                        </div>
                    </form>
                @else
                    <div class="text-muted">Search and select a student to start the withdrawal workflow.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card table-card mt-3">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Recent Withdrawals</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Withdrawal Date</th><th>TC</th><th>Security Refund</th></tr></thead>
            <tbody>
                @forelse($recentWithdrawals as $withdrawal)
                    <tr>
                        <td>{{ $withdrawal->student?->full_name ?? '-' }}</td>
                        <td>{{ \App\Support\DateFormatter::display($withdrawal->withdrawal_date) }}</td>
                        <td>{{ $withdrawal->tc_issued ? 'Issued' : 'No' }}</td>
                        <td>{{ $withdrawal->security_refunded ? 'Refunded' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No withdrawals recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $recentWithdrawals->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const tcIssued = document.getElementById('tc_issued');
        const securityRefunded = document.getElementById('security_refunded');

        function toggleBySelector(control, selector) {
            document.querySelectorAll(selector).forEach(function (element) {
                element.classList.toggle('d-none', control.value !== '1');
            });
        }

        if (tcIssued) {
            tcIssued.addEventListener('change', function () {
                toggleBySelector(tcIssued, '.tc-fields');
            });
        }

        if (securityRefunded) {
            securityRefunded.addEventListener('change', function () {
                toggleBySelector(securityRefunded, '.refund-fields');
            });
        }
    })();
</script>
@endpush
