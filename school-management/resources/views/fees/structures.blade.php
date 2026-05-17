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
                            <select name="frequency" id="frequency_select" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="half_yearly">Half Yearly</option>
                                <option value="yearly">Yearly</option>
                                <option value="one_time">One-time</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-light border py-2 small d-none mb-3" id="quarterly_note" role="status">
                        One row per quarter will be created for this session, each due on the <strong>15th</strong> of April, July, October, or January when that date falls inside the academic year.
                    </div>
                    <div class="mb-3" id="due_date_row">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" id="due_date_input">
                        <div class="form-text text-muted" id="due_date_help">Optional.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="new_admission_only" value="1" id="new_admission_only">
                        <label class="form-check-label" for="new_admission_only">New admission only (admission date from January through this academic year)</label>
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
                    <a href="{{ route('fees.discount-presets') }}" class="btn btn-outline-info">Discount Options</a>
                    <a href="{{ route('fees.payments') }}" class="btn btn-outline-primary">Payments</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Category</th><th>Class</th><th>Amount</th><th>Frequency</th><th>Applies</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                        @forelse($structures as $s)
                        <tr>
                            <td>{{ $s->display_name }}</td>
                            <td>{{ $s->schoolClass->name }}</td>
                            <td class="fw-semibold">₹{{ number_format($s->amount) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $s->frequency)) }}</td>
                            <td>
                                @if(($s->applies_to ?? 'all_students') === 'new_admission_only')
                                    <span class="badge text-bg-info">New admission</span>
                                @else
                                    <span class="text-muted">All</span>
                                @endif
                            </td>
                            <td>{{ $s->due_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                <form action="{{ route('fees.structures.destroy', $s) }}" method="POST" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No structures</td></tr>
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

@push('scripts')
<script>
(function () {
    const freq = document.getElementById('frequency_select');
    const dueRow = document.getElementById('due_date_row');
    const dueInput = document.getElementById('due_date_input');
    const quarterlyNote = document.getElementById('quarterly_note');
    function sync() {
        const isQuarterly = freq && freq.value === 'quarterly';
        if (quarterlyNote) quarterlyNote.classList.toggle('d-none', !isQuarterly);
        if (dueRow) dueRow.style.display = isQuarterly ? 'none' : '';
        if (dueInput) dueInput.disabled = isQuarterly;
    }
    if (freq) freq.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
