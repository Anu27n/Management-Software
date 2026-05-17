@extends('layouts.app')
@section('title', 'Discount Options')
@section('page-title', 'Discount Options')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Discount Option</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fees.discount-presets.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Sibling discount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fee Header</label>
                        <select name="fee_category_id" class="form-select">
                            <option value="">Any fee header</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) old('fee_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="discount_type" class="form-select" required>
                                <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Value <span class="text-danger">*</span></label>
                            <input type="number" name="value" class="form-control" step="0.01" min="0.01" value="{{ old('value') }}" required>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button class="btn btn-primary w-100">Save Option</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Saved Options</h6>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('fees.discounts') }}" class="btn btn-outline-info">Discount Records</a>
                    <a href="{{ route('fees.payments.create') }}" class="btn btn-outline-primary">Quick Record</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Fee Header</th>
                            <th>Discount</th>
                            <th>Status</th>
                            <th style="width: 260px;">Edit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presets as $preset)
                            <tr>
                                <td class="fw-semibold">{{ $preset->name }}</td>
                                <td>{{ $preset->feeCategory?->name ?? 'Any fee header' }}</td>
                                <td>
                                    @if($preset->discount_type === 'percentage')
                                        {{ number_format((float) $preset->value, 2) }}%
                                    @else
                                        Rs {{ number_format((float) $preset->value, 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($preset->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('fees.discount-presets.update', $preset) }}" class="row g-1">
                                        @csrf @method('PUT')
                                        <div class="col-12">
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $preset->name }}" required>
                                        </div>
                                        <div class="col-6">
                                            <select name="fee_category_id" class="form-select form-select-sm">
                                                <option value="">Any header</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ (int) $preset->fee_category_id === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <select name="discount_type" class="form-select form-select-sm">
                                                <option value="percentage" {{ $preset->discount_type === 'percentage' ? 'selected' : '' }}>%</option>
                                                <option value="fixed" {{ $preset->discount_type === 'fixed' ? 'selected' : '' }}>Rs</option>
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <input type="number" name="value" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ $preset->value }}" required>
                                        </div>
                                        <div class="col-7">
                                            <div class="form-check form-switch small pt-1">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="preset_active_{{ $preset->id }}" {{ $preset->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="preset_active_{{ $preset->id }}">Active</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <button class="btn btn-outline-primary btn-sm w-100">Update</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('fees.discount-presets.destroy', $preset) }}" onsubmit="return confirm('Delete this discount option?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No discount options saved.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($presets->hasPages())
                <div class="card-footer bg-white">{{ $presets->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
