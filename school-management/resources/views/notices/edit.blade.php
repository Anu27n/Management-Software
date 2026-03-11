@extends('layouts.app')
@section('title', 'Edit Notice')
@section('page-title', 'Edit Notice')

@section('content')
<div class="card table-card">
    <div class="card-body">
        <form method="POST" action="{{ route('notices.update', $notice) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $notice->title) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="6" required>{{ old('content', $notice->content) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Target Audience</label>
                    <select name="target_audience" class="form-select" required>
                        <option value="all" {{ $notice->target_audience == 'all' ? 'selected' : '' }}>All</option>
                        <option value="teachers" {{ $notice->target_audience == 'teachers' ? 'selected' : '' }}>Teachers</option>
                        <option value="parents" {{ $notice->target_audience == 'parents' ? 'selected' : '' }}>Parents</option>
                        <option value="students" {{ $notice->target_audience == 'students' ? 'selected' : '' }}>Students</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ $notice->class_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Publish Date</label>
                    <input type="date" name="publish_date" class="form-control" value="{{ old('publish_date', $notice->publish_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $notice->expiry_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" {{ $notice->is_published ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
                <a href="{{ route('notices.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
