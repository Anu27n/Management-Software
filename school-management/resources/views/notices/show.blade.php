@extends('layouts.app')
@section('title', $notice->title)
@section('page-title', 'Notice Details')

@section('content')
<div class="card table-card">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1">{{ $notice->title }}</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-{{ $notice->target_audience == 'all' ? 'primary' : 'info' }}">{{ ucfirst($notice->target_audience) }}</span>
                @if($notice->schoolClass)
                <span class="badge bg-secondary">{{ $notice->schoolClass->name }}</span>
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small">Published: {{ $notice->publish_date->format('M d, Y') }}</div>
            @if($notice->expiry_date)
            <div class="text-muted small">Expires: {{ $notice->expiry_date->format('M d, Y') }}</div>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="border rounded p-3 bg-light">
            {!! nl2br(e($notice->content)) !!}
        </div>
        @if($notice->attachment)
        <div class="mt-3">
            <a href="{{ asset('storage/' . $notice->attachment) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-paperclip me-1"></i>Download Attachment
            </a>
        </div>
        @endif
        <div class="mt-3 text-muted small">Created by: {{ $notice->author->name }}</div>
    </div>
</div>
<div class="mt-3 d-flex gap-2">
    <a href="{{ route('notices.edit', $notice) }}" class="btn btn-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="{{ route('notices.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
