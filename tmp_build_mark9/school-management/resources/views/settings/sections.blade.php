@extends('layouts.app')
@section('title', 'Manage Sections')
@section('page-title', 'Manage Sections')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Section</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.sections.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. A">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary w-100">Add Section</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">All Sections</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Section</th><th>Class</th><th></th></tr></thead>
                    <tbody>
                        @forelse($sections as $section)
                        <tr>
                            <td class="fw-semibold">{{ $section->name }}</td>
                            <td>{{ $section->schoolClass->name }}</td>
                            <td>
                                <form action="{{ route('settings.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No sections added</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
