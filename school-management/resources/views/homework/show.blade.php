@extends('layouts.app')
@section('title', 'Homework Details')
@section('page-title', 'Homework Details')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">{{ $homework->title }}</h6>
                <span class="badge {{ $homework->due_date->isPast() ? 'bg-danger' : 'bg-primary' }}">Due: {{ $homework->due_date->format('M d, Y') }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-4"><span class="text-muted">Class:</span> {{ $homework->schoolClass->name }} - {{ $homework->section->name }}</div>
                    <div class="col-4"><span class="text-muted">Subject:</span> {{ $homework->subject->name }}</div>
                    <div class="col-4"><span class="text-muted">Assigned:</span> {{ $homework->assign_date->format('M d, Y') }}</div>
                </div>
                <div class="border rounded p-3 bg-light">
                    {!! nl2br(e($homework->description)) !!}
                </div>
                @if($homework->attachment)
                <div class="mt-3">
                    <a href="{{ asset('storage/' . $homework->attachment) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-paperclip me-1"></i>Download Attachment
                    </a>
                </div>
                @endif
                <div class="mt-3 text-muted small">Assigned by: {{ $homework->assignedBy->name }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Submissions ({{ $homework->submissions->count() }})</h6></div>
            <div class="list-group list-group-flush">
                @forelse($homework->submissions as $sub)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">{{ $sub->student->full_name }}</span>
                        <span class="badge bg-{{ $sub->status == 'graded' ? 'success' : ($sub->status == 'late' ? 'warning' : 'primary') }}">{{ ucfirst($sub->status) }}</span>
                    </div>
                    @if($sub->grade)
                    <small class="text-muted">Grade: {{ $sub->grade }}</small>
                    @endif
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-3">No submissions</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="{{ route('homework.edit', $homework) }}" class="btn btn-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="{{ route('homework.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
