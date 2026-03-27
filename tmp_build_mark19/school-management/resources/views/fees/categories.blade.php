@extends('layouts.app')
@section('title', 'Fee Categories')
@section('page-title', 'Fee Categories')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Category</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fees.categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition Fee">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">All Categories</h6>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('fees.structures') }}" class="btn btn-outline-primary">Structures</a>
                    <a href="{{ route('fees.payments') }}" class="btn btn-outline-primary">Payments</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Description</th><th>Structures</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td class="fw-semibold">{{ $cat->name }}</td>
                            <td>{{ $cat->description ?? '-' }}</td>
                            <td>{{ $cat->fee_structures_count }}</td>
                            <td>
                                <form action="{{ route('fees.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No categories</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
