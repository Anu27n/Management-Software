@extends('layouts.app')
@section('title', 'Edit Student')
@section('page-title', 'Edit Student')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person me-1"></i>Basic Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Admission No <span class="text-danger">*</span></label>
                    <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no', $student->admission_no) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $student->first_name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="male" {{ old('gender', $student->gender) == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $student->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $student->gender) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $student->date_of_birth->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $student->blood_group) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control" value="{{ old('religion', $student->religion) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $student->nationality) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ $student->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $student->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="graduated" {{ $student->status == 'graduated' ? 'selected' : '' }}>Graduated</option>
                        <option value="transferred" {{ $student->status == 'transferred' ? 'selected' : '' }}>Transferred</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-mortarboard me-1"></i>Academic Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)' {{ $student->class_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
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
                            <option value="{{ $year->id }}" {{ $student->academic_year_id == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                    <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $student->admission_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Previous School</label>
                    <input type="text" name="previous_school" class="form-control" value="{{ old('previous_school', $student->previous_school) }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-geo-alt me-1"></i>Address</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $student->address) }}</textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $student->city) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $student->state) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $student->pincode) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-people me-1"></i>Parent / Guardian Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Father's Name <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Phone</label>
                    <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $student->father_phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Occupation</label>
                    <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', $student->father_occupation) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $student->mother_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mother's Phone</label>
                    <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $student->mother_phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mother's Occupation</label>
                    <input type="text" name="mother_occupation" class="form-control" value="{{ old('mother_occupation', $student->mother_occupation) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $student->guardian_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Phone</label>
                    <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $student->guardian_phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Relation</label>
                    <input type="text" name="guardian_relation" class="form-control" value="{{ old('guardian_relation', $student->guardian_relation) }}">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Student</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const currentSectionId = {{ $student->section_id }};
function loadSections() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    const option = classSelect.options[classSelect.selectedIndex];
    if (option.dataset.sections) {
        JSON.parse(option.dataset.sections).forEach(s => {
            sectionSelect.innerHTML += `<option value="${s.id}" ${s.id == currentSectionId ? 'selected' : ''}>${s.name}</option>`;
        });
    }
}
document.getElementById('class_id').addEventListener('change', loadSections);
loadSections();
</script>
@endpush
