@extends('layouts.app')
@section('title', 'Bulk Upload Students')
@section('page-title', 'Bulk Upload Students')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-upload me-1"></i>Upload Student CSV</h6>
                <a href="{{ route('students.bulk-upload.template') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Download Template
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('students.bulk-upload.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                        <small class="text-muted">Use the template to avoid format issues.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Default Class</label>
                        <select name="default_class_id" class="form-select">
                            <option value="">Use CSV value</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Default Section</label>
                        <select name="default_section_id" class="form-select">
                            <option value="">Use CSV value</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Default Academic Year</label>
                        <select name="default_academic_year_id" class="form-select">
                            <option value="">Use active year / CSV value</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Start Bulk Upload</button>
                        <a href="{{ route('students.index') }}" class="btn btn-light">Back to Students</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-1"></i>CSV Rules</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Required columns:</p>
                <ul class="small mb-3">
                    <li>admission_no</li>
                    <li>first_name</li>
                    <li>last_name</li>
                    <li>gender (male/female/other)</li>
                    <li>date_of_birth (YYYY-MM-DD)</li>
                    <li>admission_date (YYYY-MM-DD)</li>
                    <li>father_name</li>
                </ul>

                <p class="small text-muted mb-2">Useful optional columns:</p>
                <ul class="small mb-0">
                    <li>class_id, section_id, academic_year_id</li>
                    <li>father_phone, phone, email</li>
                    <li>parent_user_id or parent_email</li>
                    <li>status (active/inactive/graduated/transferred)</li>
                    <li>nationality</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
