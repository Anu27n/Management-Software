@extends('layouts.app')
@section('title', 'Edit Homework')
@section('page-title', 'Edit Homework')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <form method="POST" action="{{ route('homework.update', $homework) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)' {{ $homework->class_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Section <span class="text-danger">*</span></label>
                    <select name="section_id" id="section_id" class="form-select" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ $homework->subject_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $homework->title) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="4" required>{{ old('description', $homework->description) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assign Date <span class="text-danger">*</span></label>
                    <input type="date" name="assign_date" class="form-control" value="{{ old('assign_date', $homework->assign_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $homework->due_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-control">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
                <a href="{{ route('homework.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const currentSection = {{ $homework->section_id }};
function loadSections() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    const option = classSelect.options[classSelect.selectedIndex];
    if (option.dataset.sections) {
        JSON.parse(option.dataset.sections).forEach(s => {
            sectionSelect.innerHTML += `<option value="${s.id}" ${s.id == currentSection ? 'selected' : ''}>${s.name}</option>`;
        });
    }
}
document.getElementById('class_id').addEventListener('change', loadSections);
loadSections();
</script>
@endpush
