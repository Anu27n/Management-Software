@extends('layouts.app')
@section('title', 'Student Profile')
@section('page-title', 'Student Profile')

@section('content')
@php
    $profile = $student->profile;
    $display = function ($value) {
        if ($value instanceof \Carbon\CarbonInterface) {
            return \App\Support\DateFormatter::display($value);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return filled($value) ? $value : '-';
    };

    $detailSections = [
        [
            'title' => 'Student Basic Details',
            'icon' => 'bi-person',
            'fields' => [
                'Student S. No' => $profile?->student_s_no,
                'Surname' => $profile?->student_surname,
                'First Name' => $profile?->student_first_name ?? $student->first_name,
                'Middle Name' => $profile?->student_middle_name,
                'Gender' => ucfirst($student->gender),
                'Date of Birth' => $student->date_of_birth,
                'Nationality' => $profile?->nationality ?? $student->nationality,
                'Aadhaar Number' => $profile?->aadhaar_number,
                'PEN Number' => $profile?->student_pen_number,
                'Category' => $profile?->category ?? $student->caste,
                'Father Mobile Number' => $profile?->father_mobile_number ?? $student->father_phone,
                'Mother Mobile Number' => $profile?->mother_mobile_number ?? $student->mother_phone,
                'Blood Group' => $profile?->blood_group ?? $student->blood_group,
                'House' => $profile?->house,
            ],
        ],
        [
            'title' => 'Contact & Address',
            'icon' => 'bi-geo-alt',
            'fields' => [
                'Residential Address' => $profile?->residential_address ?? $student->address,
                'Primary Phone' => $student->phone,
                'Email' => $student->email,
                'City' => $student->city,
                'State' => $student->state,
                'Pincode' => $student->pincode,
            ],
        ],
        [
            'title' => 'Previous School',
            'icon' => 'bi-building',
            'fields' => [
                'Last School Name' => $profile?->last_school_name ?? $student->previous_school,
                'Last Class' => $profile?->last_class,
                'Report Card Attached' => $profile?->report_card_attached,
                'Transfer Certificate Attached' => $profile?->transfer_certificate_attached,
            ],
        ],
        [
            'title' => 'Father Details',
            'icon' => 'bi-person-vcard',
            'fields' => [
                'Father Name' => $profile?->father_name ?? $student->father_name,
                'Education' => $profile?->father_education,
                'Medium of Instruction' => $profile?->father_medium_of_instruction,
                'Occupation' => $profile?->father_occupation ?? $student->father_occupation,
                'Email' => $profile?->father_email,
            ],
        ],
        [
            'title' => 'Mother Details',
            'icon' => 'bi-person-hearts',
            'fields' => [
                'Mother Name' => $profile?->mother_name ?? $student->mother_name,
                'Education' => $profile?->mother_education,
                'Medium of Instruction' => $profile?->mother_medium_of_instruction,
                'Occupation' => $profile?->mother_occupation ?? $student->mother_occupation,
                'Email' => $profile?->mother_email,
            ],
        ],
        [
            'title' => 'Guardian & Family Details',
            'icon' => 'bi-people',
            'fields' => [
                'Guardian Required' => $profile?->has_guardian,
                'Guardian Name' => $profile?->guardian_name ?? $student->guardian_name,
                'Guardian Phone' => $student->guardian_phone,
                'Guardian Relation' => $profile?->guardian_relation,
                'Guardian Address' => $profile?->office_address,
                'Has Siblings' => $profile?->has_siblings,
                'Sibling Count' => $profile?->sibling_count,
                'Assigned Siblings' => collect($profile?->sibling_details ?? [])->map(function ($sibling) {
                    $label = trim((string) ($sibling['name'] ?? ''));
                    $admission = trim((string) ($sibling['admission_no'] ?? ''));
                    $className = trim((string) ($sibling['class_name'] ?? ''));

                    return collect([$label, $admission !== '' ? 'Adm: ' . $admission : null, $className !== '' ? 'Class: ' . $className : null])->filter()->implode(' | ');
                })->filter()->join(' || '),
            ],
        ],
        [
            'title' => 'Academic & Important Details',
            'icon' => 'bi-mortarboard',
            'fields' => [
                'Admission No' => $student->admission_no,
                'Class' => $student->schoolClass?->name,
                'Section' => $student->section?->name,
                'Academic Year' => $student->academicYear?->name,
                'Admission Date' => $student->admission_date,
                'BPL Beneficiary' => $profile?->bpl_beneficiary ? strtoupper($profile->bpl_beneficiary) : null,
                'RTE' => $profile?->rte ? strtoupper($profile->rte) : null,
                'Class Section Allotted' => $profile?->class_section_allotted,
                'Date of Admission' => $profile?->date_of_admission,
                'Registration Receipt Number' => $profile?->registration_receipt_number,
                'Registration Amount' => $profile?->registration_amount,
                'Fee Booklet Number' => $profile?->fee_booklet_number,
                'Security Receipt Number' => $profile?->security_receipt_number,
                'Security Amount' => $profile?->security_amount,
                'Remarks' => $profile?->remarks,
            ],
        ],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-1">{{ $student->full_name }}</h5>
        <div class="text-muted">Complete student record from Quick Admission and student profile</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Students
        </a>
        <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Student
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card table-card h-100">
            <div class="card-body text-center">
                @if($student->photo)
                    <img src="{{ asset('storage/' . $student->photo) }}" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover">
                @else
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:100px;height:100px;font-size:2rem;">
                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="mb-1">{{ $student->full_name }}</h5>
                <p class="text-muted mb-2">Admission No: {{ $student->admission_no }}</p>
                <span class="badge bg-{{ $student->status == 'active' ? 'success' : ($student->status == 'inactive' ? 'secondary' : 'warning') }}">
                    {{ ucfirst($student->status) }}
                </span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Class</span><span>{{ $display($student->schoolClass?->name) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Section</span><span>{{ $display($student->section?->name) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Academic Year</span><span>{{ $display($student->academicYear?->name) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Gender</span><span>{{ $display(ucfirst($student->gender)) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">DOB</span><span>{{ $display($student->date_of_birth) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Phone</span><span>{{ $display($student->phone ?? $student->father_phone) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Parent Login</span><span>{{ $display($student->parentUser?->email) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Admission</span><span>{{ $display($student->admission_date) }}</span></li>
            </ul>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row g-3 mb-3">
            @foreach($detailSections as $section)
                <div class="col-12">
                    <div class="card table-card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-semibold"><i class="bi {{ $section['icon'] }} me-2 text-primary"></i>{{ $section['title'] }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($section['fields'] as $label => $value)
                                    <div class="col-md-6">
                                        <div class="text-muted small">{{ $label }}</div>
                                        <div class="fw-semibold text-break">{{ $display($value) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

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
                            <td>{{ $payment->feeStructure->display_name ?? '-' }}</td>
                            <td>Rs {{ number_format((float) $payment->amount_paid, 2) }}</td>
                            <td>{{ \App\Support\DateFormatter::display($payment->payment_date) }}</td>
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
