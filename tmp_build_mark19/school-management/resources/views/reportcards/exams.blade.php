@extends('layouts.app')
@section('title', 'Exams')
@section('page-title', 'Exam Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="btn-group btn-group-sm">
        <a href="{{ route('reportcards.enter-marks') }}" class="btn btn-outline-primary">Enter Marks</a>
        <a href="{{ route('reportcards.view') }}" class="btn btn-outline-primary">View Report Card</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Exam</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('reportcards.exams.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mid-Term Exam">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select" required>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <button class="btn btn-primary w-100">Create Exam</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">All Exams</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Academic Year</th><th>Start</th><th>End</th><th></th></tr></thead>
                    <tbody>
                        @forelse($exams as $exam)
                        <tr>
                            <td class="fw-semibold">{{ $exam->name }}</td>
                            <td>{{ $exam->academicYear->name }}</td>
                            <td>{{ $exam->start_date?->format('M d, Y') ?? '-' }}</td>
                            <td>{{ $exam->end_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                <form action="{{ route('reportcards.exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No exams</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
