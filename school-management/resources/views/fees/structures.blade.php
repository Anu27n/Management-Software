@extends('layouts.app')
@section('title', 'Fee Structures')
@section('page-title', 'Fee Structures')

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Fee Structure</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fees.structures.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Fee Category <span class="text-danger">*</span></label>
                        <select name="fee_category_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-select" required>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Frequency <span class="text-danger">*</span></label>
                            <select name="frequency" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="half_yearly">Half Yearly</option>
                                <option value="yearly">Yearly</option>
                                <option value="one_time">One-time</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100">Add Structure</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">All Structures</h6>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('fees.categories') }}" class="btn btn-outline-primary">Categories</a>
                    <a href="{{ route('fees.payments') }}" class="btn btn-outline-primary">Payments</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Category</th><th>Class</th><th>Amount</th><th>Frequency</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                        @forelse($structures as $s)
                        <tr>
                            <td>{{ $s->feeCategory->name }}</td>
                            <td>{{ $s->schoolClass->name }}</td>
                            <td class="fw-semibold">₹{{ number_format($s->amount) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $s->frequency)) }}</td>
                            <td>{{ $s->due_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                <form action="{{ route('fees.structures.destroy', $s) }}" method="POST" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No structures</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($structures->hasPages())
            <div class="card-footer bg-white">{{ $structures->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
