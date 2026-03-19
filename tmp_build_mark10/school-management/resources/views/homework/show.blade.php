@extends('layouts.app')
@section('title', 'Homework Details')
@section('page-title', 'Homework Details')

@section('content')
<div class="row g-3">
    <div class="{{ auth()->user()->hasPermission('homework.manage') ? 'col-lg-8' : 'col-12' }}">
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
                    <a href="{{ route('homework.attachment.download', $homework) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-paperclip me-1"></i>Download Attachment
                    </a>
                </div>
                @endif
                <div class="mt-3 text-muted small">Assigned by: {{ $homework->assignedBy->name }}</div>
            </div>
        </div>
    </div>
    @if(auth()->user()->hasPermission('homework.manage'))
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
                        @if($sub->attachment)
                        <a href="{{ route('homework.submissions.attachment.download', $sub) }}" class="small text-decoration-none">Download submission</a>
                        @endif
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
    @endif
</div>

@if(!auth()->user()->hasPermission('homework.manage'))
    <div class="card table-card mt-3">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold">Submit Homework</h6>
        </div>
        <div class="card-body">
            @forelse($studentSubmissions as $entry)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $entry['student']->full_name }}</div>
                            <small class="text-muted">{{ $entry['student']->schoolClass?->name }} {{ $entry['student']->section?->name ? '- ' . $entry['student']->section->name : '' }}</small>
                        </div>
                        @if($entry['submission'])
                            <span class="badge bg-{{ $entry['submission']->status === 'late' ? 'warning' : ($entry['submission']->status === 'graded' ? 'success' : 'primary') }}">
                                {{ ucfirst($entry['submission']->status) }}
                            </span>
                        @else
                            <span class="badge bg-secondary">Not Submitted</span>
                        @endif
                    </div>

                    @if($entry['submission'])
                        <div class="alert alert-light border mb-3">
                            <div><strong>Submitted:</strong> {{ $entry['submission']->submitted_at?->format('d M Y h:i A') ?? '-' }}</div>
                            @if($entry['submission']->submission_text)
                                <div class="mt-2">{{ $entry['submission']->submission_text }}</div>
                            @endif
                            @if($entry['submission']->attachment)
                                <div class="mt-2">
                                    <a href="{{ route('homework.submissions.attachment.download', $entry['submission']) }}" class="btn btn-sm btn-outline-primary">Download Uploaded File</a>
                                </div>
                            @endif
                            @if($entry['submission']->feedback)
                                <div class="mt-2"><strong>Feedback:</strong> {{ $entry['submission']->feedback }}</div>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('homework.submit', $homework) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $entry['student']->id }}">
                        <div class="mb-3">
                            <label class="form-label">Submission Notes</label>
                            <textarea name="submission_text" class="form-control" rows="3" placeholder="Write homework notes or submission details">{{ old('submission_text', $entry['submission']->submission_text ?? '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Attachment</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>{{ $entry['submission'] ? 'Update Submission' : 'Submit Homework' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-4">No linked student is eligible for this homework.</div>
            @endforelse
        </div>
    </div>
@endif

<div class="mt-3 d-flex gap-2">
    @if(auth()->user()->hasPermission('homework.manage'))
        <a href="{{ route('homework.edit', $homework) }}" class="btn btn-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
    @endif
    <a href="{{ route('homework.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
