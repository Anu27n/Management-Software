@extends('layouts.app')
@section('title', 'Add Student')
@section('page-title', 'Add Student - Complete Profile')

@section('content')
@php
    $qualificationOptions = [
        'illiterate' => 'Illiterate',
        'school' => 'School',
        'diploma' => 'Diploma',
        'graduate' => 'Graduate',
        'postgraduate' => 'Post Graduate',
        'doctorate' => 'Doctorate',
        'other' => 'Other',
    ];

    $mediumOptions = ['Hindi', 'English'];
    $fatherOccupationOptions = ['Private Job', 'Government Job', 'Business', 'Professional', 'Unemployed'];
    $motherOccupationOptions = ['Private Job', 'Government Job', 'Business', 'Professional', 'Housewife'];
    $siblingDetails = old('sibling_details', []);
@endphp

<div class="card table-card">
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('generated_credentials'))
            @php($credentials = session('generated_credentials'))
            <div class="alert alert-warning">
                <div class="fw-semibold mb-1">New parent login credentials</div>
                <div>{{ $credentials['message'] ?? 'Copy these credentials now.' }}</div>
                <div class="mt-2"><strong>Name:</strong> {{ $credentials['name'] ?? '-' }}</div>
                <div><strong>Username:</strong> {{ $credentials['username'] ?? '-' }}</div>
                <div><strong>Email:</strong> {{ $credentials['email'] ?? '-' }}</div>
                <div><strong>Password:</strong> {{ $credentials['password'] ?? '-' }}</div>
            </div>
        @endif

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

        <form method="POST" action="{{ route('students.store') }}" id="studentAdmissionForm">
            @csrf

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person me-1"></i>Student Basic Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Student S. No <span class="text-danger">*</span></label>
                    <input type="text" name="student_s_no" class="form-control" value="{{ old('student_s_no') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="student_first_name" class="form-control" value="{{ old('student_first_name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="student_middle_name" class="form-control" value="{{ old('student_middle_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Surname</label>
                    <input type="text" name="student_surname" class="form-control" value="{{ old('student_surname') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nationality <span class="text-danger">*</span></label>
                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', 'Indian') }}" required>
                </div>

                <div class="col-12 pt-2">
                    <h6 class="fw-semibold text-primary mb-0"><i class="bi bi-card-text me-1"></i>Government / ID Details</h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label">BPL</label>
                    <select name="bpl_beneficiary" class="form-select">
                        <option value="na" {{ old('bpl_beneficiary', 'na') == 'na' ? 'selected' : '' }}>NA</option>
                        <option value="yes" {{ old('bpl_beneficiary') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('bpl_beneficiary') == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aadhaar Number</label>
                    <input type="text" name="aadhaar_number" class="form-control" maxlength="12" value="{{ old('aadhaar_number') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PEN Number</label>
                    <input type="text" name="student_pen_number" class="form-control" value="{{ old('student_pen_number') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="">Select</option>
                        <option value="GEN" {{ old('category') == 'GEN' ? 'selected' : '' }}>GEN</option>
                        <option value="SC" {{ old('category') == 'SC' ? 'selected' : '' }}>SC</option>
                        <option value="ST" {{ old('category') == 'ST' ? 'selected' : '' }}>ST</option>
                        <option value="OBC" {{ old('category') == 'OBC' ? 'selected' : '' }}>OBC</option>
                    </select>
                </div>

                <div class="col-12 pt-2">
                    <h6 class="fw-semibold text-primary mb-0"><i class="bi bi-telephone me-1"></i>Parent Contact Details</h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father Mobile Number</label>
                    <input type="text" name="father_mobile_number" class="form-control" maxlength="10" value="{{ old('father_mobile_number') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mother Mobile Number</label>
                    <input type="text" name="mother_mobile_number" class="form-control" maxlength="10" value="{{ old('mother_mobile_number') }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-geo-alt me-1"></i>Address & Previous School</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Residential Address <span class="text-danger">*</span></label>
                    <textarea name="residential_address" class="form-control" rows="2" required>{{ old('residential_address') }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Last School Name</label>
                    <input type="text" name="last_school_name" class="form-control" value="{{ old('last_school_name') }}">
                </div>
                <div class="col-md-3">
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

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person-vcard me-1"></i>Father Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Father Name</label>
                    <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Qualification</label>
                    <select name="father_education" class="form-select">
                        <option value="">Select</option>
                        @foreach($qualificationOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('father_education') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Medium of Instruction</label>
                    <select name="father_medium_of_instruction" class="form-select">
                        <option value="">Select</option>
                        @foreach($mediumOptions as $option)
                            <option value="{{ $option }}" {{ old('father_medium_of_instruction') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Occupation</label>
                    <select name="father_occupation" class="form-select">
                        <option value="">Select</option>
                        @foreach($fatherOccupationOptions as $option)
                            <option value="{{ $option }}" {{ old('father_occupation') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Office Address</label>
                    <input type="text" name="father_office_address" class="form-control" value="{{ old('father_office_address') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="father_email" class="form-control" value="{{ old('father_email') }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person-hearts me-1"></i>Mother Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Mother Name</label>
                    <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Qualification</label>
                    <select name="mother_education" class="form-select">
                        <option value="">Select</option>
                        @foreach($qualificationOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('mother_education') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Medium of Instruction</label>
                    <select name="mother_medium_of_instruction" class="form-select">
                        <option value="">Select</option>
                        @foreach($mediumOptions as $option)
                            <option value="{{ $option }}" {{ old('mother_medium_of_instruction') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Occupation</label>
                    <select name="mother_occupation" class="form-select">
                        <option value="">Select</option>
                        @foreach($motherOccupationOptions as $option)
                            <option value="{{ $option }}" {{ old('mother_occupation') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Office Address</label>
                    <input type="text" name="mother_office_address" class="form-control" value="{{ old('mother_office_address') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="mother_email" class="form-control" value="{{ old('mother_email') }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-shield-check me-1"></i>Guardian Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Guardian Available? <span class="text-danger">*</span></label>
                    <select name="has_guardian" id="has_guardian" class="form-select" required>
                        <option value="0" {{ old('has_guardian', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('has_guardian') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-4 {{ old('has_guardian') == '1' ? '' : 'd-none' }}" id="guardian_fields">
                <div class="col-md-3">
                    <label class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" maxlength="10" value="{{ old('phone_number') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Relation</label>
                    <input type="text" name="guardian_relation" class="form-control" value="{{ old('guardian_relation') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="office_address" class="form-control" value="{{ old('office_address') }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-people me-1"></i>Sibling Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Any Siblings? <span class="text-danger">*</span></label>
                    <select name="has_siblings" id="has_siblings" class="form-select" required>
                        <option value="0" {{ old('has_siblings', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('has_siblings') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-3 {{ old('has_siblings') == '1' ? '' : 'd-none' }}" id="sibling_count_wrap">
                    <label class="form-label">Number of Siblings</label>
                    <input type="number" min="1" max="10" name="sibling_count" id="sibling_count" class="form-control" value="{{ old('sibling_count', max(1, count($siblingDetails))) }}">
                </div>
            </div>
            <div id="siblings_container" class="{{ old('has_siblings') == '1' ? '' : 'd-none' }}"></div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-journal-text me-1"></i>Personal Record</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">House</label><input type="text" name="house" class="form-control" value="{{ old('house') }}"></div>
                <div class="col-md-2"><label class="form-label">Blood Group</label><input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}"></div>
                <div class="col-md-2"><label class="form-label">Height (cm)</label><input type="number" step="0.01" name="height_cm" class="form-control" value="{{ old('height_cm') }}"></div>
                <div class="col-md-2"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="{{ old('weight_kg') }}"></div>
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
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-mortarboard me-1"></i>Academic Mapping</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)' data-rte-eligible="{{ \App\Support\ClassEligibility::isRteEligible($class->name) ? '1' : '0' }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" id="rte_field_wrapper" style="display: none;">
                    <label class="form-label">RTE <span class="text-danger">*</span></label>
                    <select name="rte" id="rte" class="form-select" disabled>
                        <option value="">Select</option>
                        <option value="yes" {{ old('rte', 'no') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('rte', 'no') == 'no' ? 'selected' : '' }}>No</option>
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
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Go To Students</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const rteFieldWrapper = document.getElementById('rte_field_wrapper');
    const rteSelect = document.getElementById('rte');
    const hasGuardianSelect = document.getElementById('has_guardian');
    const guardianFields = document.getElementById('guardian_fields');
    const hasSiblingsSelect = document.getElementById('has_siblings');
    const siblingCountWrap = document.getElementById('sibling_count_wrap');
    const siblingCountInput = document.getElementById('sibling_count');
    const siblingsContainer = document.getElementById('siblings_container');
    const siblingDetails = @json(array_values($siblingDetails));
    const siblingClassOptions = @json($classes->map(fn ($class) => ['id' => $class->id, 'name' => $class->name])->values());

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

    function toggleRteField() {
        const option = classSelect.options[classSelect.selectedIndex];
        const shouldShowRte = option && option.dataset.rteEligible === '1';

        rteFieldWrapper.style.display = shouldShowRte ? '' : 'none';
        rteSelect.disabled = !shouldShowRte;
        rteSelect.required = shouldShowRte;

        if (!shouldShowRte) {
            rteSelect.value = '';
        }
    }

    function toggleGuardianFields() {
        const enabled = hasGuardianSelect.value === '1';
        guardianFields.classList.toggle('d-none', !enabled);
    }

    function buildSiblingCard(index, sibling) {
        const isStudying = sibling && (String(sibling.is_studying) === '1' || sibling.is_studying === true);
        const selectedClassId = sibling && sibling.class_id ? String(sibling.class_id) : '';
        const classOptions = siblingClassOptions.map(function (schoolClass) {
            return `<option value="${schoolClass.id}" ${selectedClassId === String(schoolClass.id) ? 'selected' : ''}>${schoolClass.name}</option>`;
        }).join('');

        return `
            <div class="border rounded p-3 mb-3">
                <div class="fw-semibold mb-3">Sibling ${index + 1}</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="sibling_details[${index}][name]" class="form-control" value="${sibling?.name ?? ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Studying?</label>
                        <select name="sibling_details[${index}][is_studying]" class="form-select sibling-studying-toggle" data-index="${index}">
                            <option value="0" ${!isStudying ? 'selected' : ''}>No</option>
                            <option value="1" ${isStudying ? 'selected' : ''}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-3 sibling-class-wrap ${isStudying ? '' : 'd-none'}" data-index="${index}">
                        <label class="form-label">Class</label>
                        <select name="sibling_details[${index}][class_id]" class="form-select">
                            <option value="">Select Class</option>
                            ${classOptions}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Notes</label>
                        <input type="text" name="sibling_details[${index}][notes]" class="form-control" value="${sibling?.notes ?? ''}" placeholder="Optional">
                    </div>
                </div>
            </div>
        `;
    }

    function renderSiblings() {
        const enabled = hasSiblingsSelect.value === '1';
        siblingCountWrap.classList.toggle('d-none', !enabled);
        siblingsContainer.classList.toggle('d-none', !enabled);

        if (!enabled) {
            siblingsContainer.innerHTML = '';
            return;
        }

        const count = Math.max(1, parseInt(siblingCountInput.value || '1', 10));
        siblingCountInput.value = count;

        let html = '';
        for (let i = 0; i < count; i++) {
            html += buildSiblingCard(i, siblingDetails[i] || {});
        }

        siblingsContainer.innerHTML = html;
    }

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('sibling-studying-toggle')) {
            const index = event.target.dataset.index;
            const classWrap = document.querySelector(`.sibling-class-wrap[data-index="${index}"]`);
            if (classWrap) {
                classWrap.classList.toggle('d-none', event.target.value !== '1');
            }
        }
    });

    classSelect.addEventListener('change', function () {
        bindSections();
        toggleRteField();
    });

    hasGuardianSelect.addEventListener('change', toggleGuardianFields);
    hasSiblingsSelect.addEventListener('change', renderSiblings);
    siblingCountInput?.addEventListener('input', renderSiblings);

    if (!classSelect.value && classSelect.options.length === 2) {
        classSelect.selectedIndex = 1;
    }

    bindSections();
    toggleRteField();
    toggleGuardianFields();
    renderSiblings();
</script>
@endpush
