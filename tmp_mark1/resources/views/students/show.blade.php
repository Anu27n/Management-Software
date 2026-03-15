@extends('layouts.app')
@section('title', 'Student Profile')
@section('page-title', 'Student Profile')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-body text-center">
                @if($student->photo)
                    <img src="{{ asset('storage/' . $student->photo) }}" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover">
                @else
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:100px;height:100px;font-size:2rem;">
                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="mb-1">{{ $student->full_name }}</h5>
                <p class="text-muted mb-1">Admission No: {{ $student->admission_no }}</p>
                <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Class</span> <span>{{ $student->schoolClass->name }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Section</span> <span>{{ $student->section->name }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Gender</span> <span>{{ ucfirst($student->gender) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">DOB</span> <span>{{ $student->date_of_birth->format('M d, Y') }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Phone</span> <span>{{ $student->phone ?? '-' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span> <span>{{ $student->email ?? '-' }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Admission</span> <span>{{ $student->admission_date->format('M d, Y') }}</span></li>
            </ul>
            <div class="card-body">
                <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm w-100"><i class="bi bi-pencil me-1"></i>Edit Student</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        {{-- Parent Info --}}
        <div class="card table-card mb-3">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Parent / Guardian Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Father's Name</div>
                        <div class="fw-semibold">{{ $student->father_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Father's Phone</div>
                        <div>{{ $student->father_phone ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Father's Occupation</div>
                        <div>{{ $student->father_occupation ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Mother's Name</div>
                        <div>{{ $student->mother_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Mother's Phone</div>
                        <div>{{ $student->mother_phone ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Address</div>
                        <div>{{ $student->address ?? '-' }}, {{ $student->city }} {{ $student->state }} {{ $student->pincode }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exam Results --}}
        <div class="card table-card mb-3">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Exam Results</h6></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th></tr>
                    </thead>
                    <tbody>
                        @forelse($student->examResults as $result)
                        <tr>
                            <td>{{ $result->exam->name }}</td>
                            <td>{{ $result->subject->name }}</td>
                            <td>{{ $result->marks_obtained }}</td>
                            <td>{{ $result->total_marks }}</td>
                            <td>{{ $result->percentage }}%</td>
                            <td><span class="badge bg-{{ $result->grade == 'F' ? 'danger' : 'primary' }}">{{ $result->grade }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No exam results</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Fee Payments --}}
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Fee Payments</h6></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Receipt</th><th>Category</th><th>Amount</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($student->feePayments as $payment)
                        <tr>
                            <td>{{ $payment->receipt_no }}</td>
                            <td>{{ $payment->feeStructure->feeCategory->name ?? '-' }}</td>
                            <td>₹{{ number_format($payment->amount_paid) }}</td>
                            <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td><span class="badge bg-{{ $payment->status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payment->status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No fee payments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
