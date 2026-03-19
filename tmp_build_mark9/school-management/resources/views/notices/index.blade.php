@extends('layouts.app')
@section('title', 'Notices')
@section('page-title', 'Notices & Announcements')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">All Notices</h5>
    @if(auth()->user()->hasPermission('notices.manage'))
        <a href="{{ route('notices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Notice</a>
    @endif
</div>

<div class="row g-3">
    @forelse($notices as $notice)
    <div class="col-md-6 col-lg-4">
        <div class="card table-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-{{ $notice->target_audience == 'all' ? 'primary' : ($notice->target_audience == 'teachers' ? 'info' : 'success') }}">
                        {{ ucfirst($notice->target_audience) }}
                    </span>
                    <small class="text-muted">{{ $notice->publish_date->format('M d, Y') }}</small>
                </div>
                <h6 class="fw-semibold">{{ $notice->title }}</h6>
                <p class="text-muted small">{{ Str::limit(strip_tags($notice->content), 120) }}</p>
            </div>
            <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                <small class="text-muted">By {{ $notice->author->name }}</small>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('notices.show', $notice) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                    @if(auth()->user()->hasPermission('notices.manage'))
                        <a href="{{ route('notices.edit', $notice) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('notices.destroy', $notice) }}" method="POST" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">No notices yet</div>
    @endforelse
</div>

@if($notices->hasPages())
<div class="mt-3">{{ $notices->withQueryString()->links() }}</div>
@endif
@endsection
