@extends('layouts.app')
@section('title', 'Students')
@section('page-title', 'Student Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">All Students</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('students.bulk-upload') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i>Bulk Upload</a>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('export.students.csv', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
            <a href="{{ route('export.students.pdf', request()->query()) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
        </div>
        <a href="{{ route('students.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Student</a>
    </div>
</div>

{{-- Filters --}}
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small">Class</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>
            <div class="col-8 col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or Admission No" value="{{ request('search') }}">
            </div>
            <div class="col-4 col-md-2">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Adm. No</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Father's Name</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="fw-semibold">{{ $student->admission_no }}</td>
                    <td>
                        <a href="{{ route('students.show', $student) }}" class="text-decoration-none">{{ $student->full_name }}</a>
                    </td>
                    <td>{{ $student->schoolClass->name }}</td>
                    <td>{{ $student->section->name }}</td>
                    <td>{{ $student->father_name }}</td>
                    <td>{{ $student->phone ?? $student->father_phone }}</td>
                    <td>
                        <span class="badge bg-{{ $student->status == 'active' ? 'success' : ($student->status == 'inactive' ? 'secondary' : 'warning') }}">
                            {{ ucfirst($student->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('students.edit', $student) }}" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('students.destroy', $student) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No students found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white">{{ $students->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
