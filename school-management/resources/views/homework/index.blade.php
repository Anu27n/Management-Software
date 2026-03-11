@extends('layouts.app')
@section('title', 'Homework')
@section('page-title', 'Homework Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">All Homework</h5>
    <a href="{{ route('homework.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign Homework</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Class</th><th>Section</th><th>Subject</th><th>Assigned</th><th>Due</th><th>By</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($homeworks as $hw)
                <tr>
                    <td><a href="{{ route('homework.show', $hw) }}" class="text-decoration-none fw-semibold">{{ $hw->title }}</a></td>
                    <td>{{ $hw->schoolClass->name }}</td>
                    <td>{{ $hw->section->name }}</td>
                    <td>{{ $hw->subject->name }}</td>
                    <td>{{ $hw->assign_date->format('M d') }}</td>
                    <td>
                        <span class="{{ $hw->due_date->isPast() ? 'text-danger' : '' }}">{{ $hw->due_date->format('M d') }}</span>
                    </td>
                    <td>{{ $hw->assignedBy->name }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('homework.edit', $hw) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('homework.destroy', $hw) }}" method="POST" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No homework assigned yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($homeworks->hasPages())
    <div class="card-footer bg-white">{{ $homeworks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
