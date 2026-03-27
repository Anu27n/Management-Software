@extends('layouts.app')
@section('title', 'Report Card')
@section('page-title', 'Report Card')

@section('content')
<div class="card table-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportcards.view') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Exam</label>
                <select name="exam_id" class="form-select" required>
                    <option value="">Select Exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Student</label>
                <select name="student_id" class="form-select" required>
                    <option value="">Select Student</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->full_name }} ({{ $s->admission_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">View</button></div>
        </form>
    </div>
</div>

@if(isset($student) && isset($results))
<div class="card table-card" id="reportCard">
    <div class="card-header bg-white text-center py-3">
        <h4 class="mb-1 fw-bold">{{ config('app.name') }}</h4>
        <h6 class="text-muted mb-0">{{ $selectedExam->name }} — Report Card</h6>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:140px">Name</td><td class="fw-semibold">{{ $student->full_name }}</td></tr>
                    <tr><td class="text-muted">Admission No.</td><td>{{ $student->admission_no }}</td></tr>
                    <tr><td class="text-muted">Status</td><td>{{ ucfirst($student->status) }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:140px">Class</td><td>{{ $student->schoolClass->name ?? '-' }} — {{ $student->section->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Academic Year</td><td>{{ $student->academicYear->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Date</td><td>{{ now()->format('M d, Y') }}</td></tr>
                </table>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Subject</th><th class="text-center">Marks Obtained</th><th class="text-center">Max Marks</th><th class="text-center">Percentage</th><th class="text-center">Grade</th></tr>
                </thead>
                <tbody>
                    @php $totalObtained = 0; $totalMax = 0; @endphp
                    @forelse($results as $i => $result)
                    @php $totalObtained += $result->marks_obtained; $totalMax += $result->total_marks; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $result->subject->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $result->marks_obtained }}</td>
                        <td class="text-center">{{ $result->total_marks }}</td>
                        <td class="text-center">{{ number_format($result->percentage, 1) }}%</td>
                        <td class="text-center"><span class="badge bg-{{ $result->grade == 'A+' || $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'primary') }}">{{ $result->grade }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No results found</td></tr>
                    @endforelse
                </tbody>
                @if($results->count())
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2">Total</td>
                        <td class="text-center">{{ $totalObtained }}</td>
                        <td class="text-center">{{ $totalMax }}</td>
                        <td class="text-center">{{ $totalMax > 0 ? number_format(($totalObtained / $totalMax) * 100, 1) : 0 }}%</td>
                        <td class="text-center">
                            @php
                                $overallPct = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
                                $overallGrade = $overallPct >= 90 ? 'A+' : ($overallPct >= 80 ? 'A' : ($overallPct >= 70 ? 'B' : ($overallPct >= 60 ? 'C' : ($overallPct >= 50 ? 'D' : 'F'))));
                            @endphp
                            <span class="badge bg-{{ $overallGrade == 'A+' || $overallGrade == 'A' ? 'success' : ($overallGrade == 'F' ? 'danger' : 'primary') }}">{{ $overallGrade }}</span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end d-print-none">
        @if(auth()->user()->hasPermission('exports.manage'))
            <a href="{{ route('export.reportcard.pdf', ['exam_id' => request('exam_id'), 'student_id' => request('student_id')]) }}" class="btn btn-outline-danger me-1"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
        @endif
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>
@endif
@endsection
