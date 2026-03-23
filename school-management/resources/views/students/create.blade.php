@extends('layouts.app')
@section('title', 'Add Student')
@section('page-title', 'Add Student - Complete Profile')

@section('content')
<div class="card table-card">
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
            @csrf

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person me-1"></i>Student Basic Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Student S. No <span class="text-danger">*</span></label>
                    <input type="text" name="student_s_no" class="form-control" value="{{ old('student_s_no') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Surname</label>
                    <input type="text" name="student_surname" class="form-control" value="{{ old('student_surname') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="student_first_name" class="form-control" value="{{ old('student_first_name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="student_middle_name" class="form-control" value="{{ old('student_middle_name') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nationality <span class="text-danger">*</span></label>
                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', 'Indian') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
                    <input type="text" name="aadhaar_number" class="form-control" maxlength="12" value="{{ old('aadhaar_number') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">PEN Number</label>
                    <input type="text" name="student_pen_number" class="form-control" value="{{ old('student_pen_number') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="">Select</option>
                        <option value="GEN" {{ old('category') == 'GEN' ? 'selected' : '' }}>GEN</option>
                        <option value="SC" {{ old('category') == 'SC' ? 'selected' : '' }}>SC</option>
                        <option value="ST" {{ old('category') == 'ST' ? 'selected' : '' }}>ST</option>
                        <option value="OBC" {{ old('category') == 'OBC' ? 'selected' : '' }}>OBC</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class Applied For <span class="text-danger">*</span></label>
                    <input type="text" name="class_applied_for" class="form-control" value="{{ old('class_applied_for') }}" required>
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-geo-alt me-1"></i>Contact & Address</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Residential Address <span class="text-danger">*</span></label>
                    <textarea name="residential_address" class="form-control" rows="2" required>{{ old('residential_address') }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Father Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="father_mobile_number" class="form-control" maxlength="10" value="{{ old('father_mobile_number') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mother Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="mother_mobile_number" class="form-control" maxlength="10" value="{{ old('mother_mobile_number') }}" required>
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-building me-1"></i>Previous School</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Last School Name</label>
                    <input type="text" name="last_school_name" class="form-control" value="{{ old('last_school_name') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Last Class</label>
                    <input type="text" name="last_class" class="form-control" value="{{ old('last_class') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Report Card Attached</label>
                    <input type="hidden" name="report_card_attached" value="0">
                    <input type="checkbox" class="form-check-input me-2" name="report_card_attached" value="1" {{ old('report_card_attached') ? 'checked' : '' }}> Yes
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Transfer Certificate Attached</label>
                    <input type="hidden" name="transfer_certificate_attached" value="0">
                    <input type="checkbox" class="form-check-input me-2" name="transfer_certificate_attached" value="1" {{ old('transfer_certificate_attached') ? 'checked' : '' }}> Yes
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-heart-pulse me-1"></i>Health</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Is Child Healthy? <span class="text-danger">*</span></label>
                    <select name="is_child_healthy" class="form-select" required>
                        <option value="yes" {{ old('is_child_healthy', 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('is_child_healthy') == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Health Report Attached</label>
                    <input type="hidden" name="health_report_attached" value="0">
                    <input type="checkbox" class="form-check-input me-2" name="health_report_attached" value="1" {{ old('health_report_attached') ? 'checked' : '' }}> Yes
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person-vcard me-1"></i>Parents Details - Father</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Father Name <span class="text-danger">*</span></label><input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}" required></div>
                <div class="col-md-3"><label class="form-label">Education</label><input type="text" name="father_education" class="form-control" value="{{ old('father_education') }}"></div>
                <div class="col-md-3"><label class="form-label">Medium of Instruction</label><input type="text" name="father_medium_of_instruction" class="form-control" value="{{ old('father_medium_of_instruction') }}"></div>
                <div class="col-md-3"><label class="form-label">Occupation</label><input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation') }}"></div>
                <div class="col-md-3"><label class="form-label">Business Designation</label><input type="text" name="father_business_designation" class="form-control" value="{{ old('father_business_designation') }}"></div>
                <div class="col-md-3"><label class="form-label">Organization Name</label><input type="text" name="father_organization_name" class="form-control" value="{{ old('father_organization_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Office Address</label><input type="text" name="father_office_address" class="form-control" value="{{ old('father_office_address') }}"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="father_phone" class="form-control" maxlength="10" value="{{ old('father_phone') }}"></div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="father_email" class="form-control" value="{{ old('father_email') }}"></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person-hearts me-1"></i>Parents Details - Mother</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Mother Name <span class="text-danger">*</span></label><input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}" required></div>
                <div class="col-md-3"><label class="form-label">Education</label><input type="text" name="mother_education" class="form-control" value="{{ old('mother_education') }}"></div>
                <div class="col-md-3"><label class="form-label">Medium of Instruction</label><input type="text" name="mother_medium_of_instruction" class="form-control" value="{{ old('mother_medium_of_instruction') }}"></div>
                <div class="col-md-3"><label class="form-label">Occupation</label><input type="text" name="mother_occupation" class="form-control" value="{{ old('mother_occupation') }}"></div>
                <div class="col-md-3"><label class="form-label">Business Designation</label><input type="text" name="mother_business_designation" class="form-control" value="{{ old('mother_business_designation') }}"></div>
                <div class="col-md-3"><label class="form-label">Organization Name</label><input type="text" name="mother_organization_name" class="form-control" value="{{ old('mother_organization_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Office Address</label><input type="text" name="mother_office_address" class="form-control" value="{{ old('mother_office_address') }}"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="mother_phone" class="form-control" maxlength="10" value="{{ old('mother_phone') }}"></div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="mother_email" class="form-control" value="{{ old('mother_email') }}"></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-pen me-1"></i>Declaration</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><label class="form-label">Parent/Guardian Signature <span class="text-danger">*</span></label><input type="text" name="parent_guardian_signature" class="form-control" value="{{ old('parent_guardian_signature') }}" required></div>
                <div class="col-md-3"><label class="form-label">Declaration Date <span class="text-danger">*</span></label><input type="date" name="declaration_date" class="form-control" value="{{ old('declaration_date', date('Y-m-d')) }}" required></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-journal-text me-1"></i>Personal Record</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Student Name <span class="text-danger">*</span></label><input type="text" name="student_name" class="form-control" value="{{ old('student_name') }}" required></div>
                <div class="col-md-3"><label class="form-label">Class <span class="text-danger">*</span></label><input type="text" name="personal_record_class" class="form-control" value="{{ old('personal_record_class') }}" required></div>
                <div class="col-md-3"><label class="form-label">Section <span class="text-danger">*</span></label><input type="text" name="personal_record_section" class="form-control" value="{{ old('personal_record_section') }}" required></div>
                <div class="col-md-3"><label class="form-label">House</label><input type="text" name="house" class="form-control" value="{{ old('house') }}"></div>
                <div class="col-md-2"><label class="form-label">Blood Group</label><input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}"></div>
                <div class="col-md-2"><label class="form-label">Height (cm)</label><input type="number" step="0.01" name="height_cm" class="form-control" value="{{ old('height_cm') }}"></div>
                <div class="col-md-2"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="{{ old('weight_kg') }}"></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-bus-front me-1"></i>Transport</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Transport Mode <span class="text-danger">*</span></label>
                    <select name="transport_mode" class="form-select" required>
                        <option value="">Select</option>
                        <option value="parents" {{ old('transport_mode') == 'parents' ? 'selected' : '' }}>parents</option>
                        <option value="van" {{ old('transport_mode') == 'van' ? 'selected' : '' }}>van</option>
                        <option value="auto" {{ old('transport_mode') == 'auto' ? 'selected' : '' }}>auto</option>
                        <option value="rickshaw" {{ old('transport_mode') == 'rickshaw' ? 'selected' : '' }}>rickshaw</option>
                        <option value="self" {{ old('transport_mode') == 'self' ? 'selected' : '' }}>self</option>
                    </select>
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-people me-1"></i>Family, Address, Siblings & Other</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Phone Number</label><input type="text" name="phone_number" class="form-control" maxlength="10" value="{{ old('phone_number') }}"></div>
                <div class="col-md-3"><label class="form-label">Office Address</label><input type="text" name="office_address" class="form-control" value="{{ old('office_address') }}"></div>
                <div class="col-md-3"><label class="form-label">Father Mobile</label><input type="text" name="father_mobile" class="form-control" maxlength="10" value="{{ old('father_mobile') }}"></div>
                <div class="col-md-3"><label class="form-label">Mother Mobile</label><input type="text" name="mother_mobile" class="form-control" maxlength="10" value="{{ old('mother_mobile') }}"></div>
                <div class="col-md-3"><label class="form-label">Sibling 1 Name</label><input type="text" name="sibling_1_name" class="form-control" value="{{ old('sibling_1_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Sibling 1 Class</label><input type="text" name="sibling_1_class" class="form-control" value="{{ old('sibling_1_class') }}"></div>
                <div class="col-md-3"><label class="form-label">Sibling 2 Name</label><input type="text" name="sibling_2_name" class="form-control" value="{{ old('sibling_2_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Sibling 2 Class</label><input type="text" name="sibling_2_class" class="form-control" value="{{ old('sibling_2_class') }}"></div>
                <div class="col-md-3">
                    <label class="form-label">BPL Beneficiary <span class="text-danger">*</span></label>
                    <select name="bpl_beneficiary" class="form-select" required>
                        <option value="yes" {{ old('bpl_beneficiary', 'no') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('bpl_beneficiary', 'no') == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Father Signature</label><input type="text" name="father_signature" class="form-control" value="{{ old('father_signature') }}"></div>
                <div class="col-md-3"><label class="form-label">Mother Signature</label><input type="text" name="mother_signature" class="form-control" value="{{ old('mother_signature') }}"></div>
                <div class="col-md-3"><label class="form-label">Guardian Signature</label><input type="text" name="guardian_signature" class="form-control" value="{{ old('guardian_signature') }}"></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-building-gear me-1"></i>Office Use Only</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Registration Receipt Number</label><input type="text" name="registration_receipt_number" class="form-control" value="{{ old('registration_receipt_number') }}"></div>
                <div class="col-md-3"><label class="form-label">Registration Amount</label><input type="number" step="0.01" name="registration_amount" class="form-control" value="{{ old('registration_amount') }}"></div>
                <div class="col-md-3"><label class="form-label">Class Section Allotted</label><input type="text" name="class_section_allotted" class="form-control" value="{{ old('class_section_allotted') }}"></div>
                <div class="col-md-3"><label class="form-label">Date of Admission</label><input type="date" name="date_of_admission" class="form-control" value="{{ old('date_of_admission') }}"></div>
                <div class="col-md-3"><label class="form-label">Fee Booklet Number</label><input type="text" name="fee_booklet_number" class="form-control" value="{{ old('fee_booklet_number') }}"></div>
                <div class="col-md-3"><label class="form-label">Security Receipt Number</label><input type="text" name="security_receipt_number" class="form-control" value="{{ old('security_receipt_number') }}"></div>
                <div class="col-md-3"><label class="form-label">Security Amount</label><input type="number" step="0.01" name="security_amount" class="form-control" value="{{ old('security_amount') }}"></div>
                <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea></div>
                <div class="col-md-3"><label class="form-label">Principal Signature</label><input type="text" name="principal_signature" class="form-control" value="{{ old('principal_signature') }}"></div>
                <div class="col-md-3"><label class="form-label">Office Incharge Signature</label><input type="text" name="office_incharge_signature" class="form-control" value="{{ old('office_incharge_signature') }}"></div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-mortarboard me-1"></i>Academic Mapping</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)' {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section <span class="text-danger">*</span></label>
                    <select name="section_id" id="section_id" class="form-select" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="form-select" required>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id', $year->is_active ? $year->id : null) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                    <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Student</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    function bindSections() {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        const option = classSelect.options[classSelect.selectedIndex];

        if (!option || !option.dataset.sections) {
            return;
        }

        JSON.parse(option.dataset.sections).forEach(function (section) {
            const selected = String(section.id) === String('{{ old('section_id') }}');
            sectionSelect.innerHTML += `<option value="${section.id}" ${selected ? 'selected' : ''}>${section.name}</option>`;
        });
    }

    classSelect.addEventListener('change', bindSections);
    bindSections();
</script>
@endpush
