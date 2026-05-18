@extends('layouts.app')
@section('title', 'Report Card')
@section('page-title', 'Report Card')

@section('content')
@php($templateOptions = \App\Support\ReportTemplateRegistry::all())
<div class="card table-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportcards.view') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">Select Academic Year</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (string) request('academic_year_id', $selectedAcademicYear) === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Marksheet Template</label>
                <select name="report_template" class="form-select" required>
                    @foreach($templateOptions as $templateKey => $templateMeta)
                        <option value="{{ $templateKey }}" {{ $selectedTemplate === $templateKey ? 'selected' : '' }}>{{ $templateMeta['label'] }}</option>
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
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill">View</button>
                @if($student && $selectedExam && auth()->user()->hasPermission('exports.manage'))
                    <a href="{{ route('export.reportcard.pdf', ['academic_year_id' => $selectedAcademicYear, 'report_template' => $selectedTemplate, 'student_id' => $student->id]) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($marksheet)
<div class="card table-card" id="reportCard">
    <div class="card-body reportcard-print-area">
        @include('reportcards.partials.marksheet', ['marksheet' => $marksheet, 'forPdf' => false])
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    .marksheet-shell { max-width: 100%; }
    .marksheet-header {
        border: 2px solid #7a4a00;
        background: #fff;
        padding: 0.75rem 0.75rem 0.5rem;
    }
    .marksheet-header-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding-bottom: 0.35rem;
    }
    .marksheet-logo {
        max-height: 52px;
        width: auto;
        display: block;
        mix-blend-mode: multiply;
    }
    .marksheet-title-bar {
        background: #b8860b;
        color: #fff;
        padding: 0.25rem 0;
        letter-spacing: 0.03em;
    }
    .marksheet-meta td:first-child { width: 140px; color: #6c757d; }
    .marksheet-table th,
    .marksheet-table td { vertical-align: middle; }
    .marksheet-card { border: 1px solid rgba(15, 23, 42, 0.08); }
    .remarks-table td {
        vertical-align: middle;
    }
    .remarks-label {
        width: 45%;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
    }
    .signature-line {
        height: 1px;
        background: #111827;
        margin: 0 auto 0.5rem;
        width: 70%;
    }
    @media print {
        body { background: #fff !important; }
        .app-main, .reportcard-print-area { padding: 0 !important; }
        .card, .card-body { border: none !important; box-shadow: none !important; }
    }
</style>
@endpush
