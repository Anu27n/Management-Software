@extends('layouts.app')
@section('title', 'Assign Homework')
@section('page-title', 'Assign Homework')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <form method="POST" action="{{ route('homework.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-sections='@json($class->sections)'>{{ $class->name }}</option>
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
                    <select name="subject_id" id="subject_id" class="form-select" required>
                        <option value="">Select Subject</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" data-class="{{ $s->class_id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assign Date <span class="text-danger">*</span></label>
                    <input type="date" name="assign_date" class="form-control" value="{{ old('assign_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-control">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Assign</button>
                <a href="{{ route('homework.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('class_id').addEventListener('change', function() {
    const sectionSelect = document.getElementById('section_id');
    const subjectSelect = document.getElementById('subject_id');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    const option = this.options[this.selectedIndex];
    if (option.dataset.sections) {
        JSON.parse(option.dataset.sections).forEach(s => {
            sectionSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
    }
    // Filter subjects by class
    const classId = this.value;
    Array.from(subjectSelect.options).forEach(opt => {
        if (opt.value === '') return;
        opt.style.display = opt.dataset.class === classId ? '' : 'none';
    });
    subjectSelect.value = '';
});
</script>
@endpush
