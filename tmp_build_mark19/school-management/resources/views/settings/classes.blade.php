@extends('layouts.app')
@section('title', 'Manage Classes')
@section('page-title', 'Manage Classes')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Class</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.classes.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Class Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Class 1">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Numeric Name</label>
                        <input type="text" name="numeric_name" class="form-control" value="{{ old('numeric_name') }}" placeholder="e.g. 1">
                    </div>
                    <button class="btn btn-primary w-100">Add Class</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">All Classes</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Numeric</th><th>Sections</th><th>Students</th><th></th></tr></thead>
                    <tbody>
                        @forelse($classes as $class)
                        <tr>
                            <td class="fw-semibold">{{ $class->name }}</td>
                            <td>{{ $class->numeric_name ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $class->sections_count }}</span></td>
                            <td><span class="badge bg-primary">{{ $class->students_count }}</span></td>
                            <td>
                                <form action="{{ route('settings.classes.destroy', $class) }}" method="POST" onsubmit="return confirm('Delete this class?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No classes added</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
