@extends('layouts.app')
@section('title', 'Default fee amounts')
@section('page-title', 'Default fee amounts')

@section('content')
<div class="row g-3">
    <div class="col-lg-10">
        <div class="card table-card">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-currency-rupee me-1"></i>Default fee amounts (bootstrap)</h6>
                <div class="d-flex flex-wrap gap-2">
                    <form action="{{ route('settings.fee-defaults.reset') }}" method="POST" class="m-0" onsubmit="return confirm('Discard saved amounts and use only config/school_fees.php?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" @if(!$usesDatabase) disabled title="Already using file defaults" @endif>
                            Reset to config file
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <p class="text-muted small mb-4">
                    These values are used when running <code class="small">php artisan db:seed --class=SchoolFeeStructureSeeder</code> (and the initial migration) to create fee categories and structures for each class.
                    @if($usesDatabase)
                        <span class="text-dark fw-semibold">Saved copy in the database is active</span> and overrides <code>config/school_fees.php</code>.
                    @else
                        Amounts come from <code>config/school_fees.php</code> until you save here.
                    @endif
                    Existing fee structure rows in the database are not changed automatically—edit or delete them under Fee Structures if needed.
                </p>

                <form method="POST" action="{{ route('settings.fee-defaults.update') }}">
                    @csrf
                    <h6 class="fw-semibold mb-3">Annual & admission (all slabs)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Miscellaneous (annual) ₹</label>
                            <input type="number" step="0.01" name="amounts[misc_annual]" class="form-control" required
                                value="{{ old('amounts.misc_annual', $feeConfig['amounts']['misc_annual'] ?? 0) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Admission fee (one-time) ₹</label>
                            <input type="number" step="0.01" name="amounts[admission_one_time]" class="form-control" required
                                value="{{ old('amounts.admission_one_time', $feeConfig['amounts']['admission_one_time'] ?? 0) }}">
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3">Grade slabs (quarterly + new-admission registration & security)</h6>
                    <p class="small text-muted mb-3">Typical brochure layout: grades 1–8, 9–10, 11–12. You can change ranges if your school uses different groupings.</p>

                    @foreach($feeConfig['tiers'] ?? [] as $i => $tier)
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="fw-semibold small text-secondary mb-2">Slab {{ $i + 1 }}</div>
                            <div class="row g-2">
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Grade from</label>
                                    <input type="number" name="tiers[{{ $i }}][grade_min]" class="form-control" min="1" max="12" required
                                        value="{{ old('tiers.'.$i.'.grade_min', $tier['grade_min'] ?? 1) }}">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Grade to</label>
                                    <input type="number" name="tiers[{{ $i }}][grade_max]" class="form-control" min="1" max="12" required
                                        value="{{ old('tiers.'.$i.'.grade_max', $tier['grade_max'] ?? 12) }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Quarterly ₹</label>
                                    <input type="number" step="0.01" name="tiers[{{ $i }}][quarterly]" class="form-control" required
                                        value="{{ old('tiers.'.$i.'.quarterly', $tier['quarterly'] ?? 0) }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Registration ₹</label>
                                    <input type="number" step="0.01" name="tiers[{{ $i }}][registration]" class="form-control" required
                                        value="{{ old('tiers.'.$i.'.registration', $tier['registration'] ?? 0) }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Security ₹</label>
                                    <input type="number" step="0.01" name="tiers[{{ $i }}][security]" class="form-control" required
                                        value="{{ old('tiers.'.$i.'.security', $tier['security'] ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">Save defaults</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
