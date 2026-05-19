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
    .progress-sheet {
        max-width: 980px;
        margin: 0 auto;
        color: #1a1309;
        font-family: Georgia, "Times New Roman", serif;
    }
    .progress-sheet__frame {
        background: #fffef8;
        border: 2px solid #4b3723;
        box-shadow: inset 0 0 0 2px #e4d8c7;
    }
    .progress-sheet__header {
        border-bottom: 1px solid #4b3723;
    }
    .progress-sheet__brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.75rem 0.8rem 0.45rem;
    }
    .progress-sheet__logo {
        max-height: 54px;
        width: auto;
        display: block;
        object-fit: contain;
    }
    .progress-sheet__heading {
        text-align: center;
    }
    .progress-sheet__school {
        font-size: 1.45rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        line-height: 1.05;
    }
    .progress-sheet__subhead {
        font-size: 0.78rem;
        line-height: 1.15;
    }
    .progress-sheet__title {
        border-top: 1px solid #4b3723;
        background: #f1e7d8;
        text-align: center;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.32rem 0.55rem;
        letter-spacing: 0.04em;
    }
    .progress-sheet__meta,
    .progress-sheet__layout-table,
    .progress-sheet__table,
    .progress-sheet__side-table,
    .progress-sheet__scale-table {
        width: 100%;
        border-collapse: collapse;
    }
    .progress-sheet__meta td,
    .progress-sheet__table th,
    .progress-sheet__table td,
    .progress-sheet__side-table th,
    .progress-sheet__side-table td,
    .progress-sheet__scale-table th,
    .progress-sheet__scale-table td {
        border: 1px solid #4b3723;
        padding: 0.2rem 0.28rem;
        vertical-align: middle;
    }
    .progress-sheet__meta td {
        width: 33.33%;
        font-size: 0.76rem;
        padding: 0.23rem 0.35rem;
    }
    .progress-sheet__layout-table td {
        vertical-align: top;
        padding: 0;
        border: none;
    }
    .progress-sheet__layout-main {
        width: 72%;
        padding-right: 0.18rem !important;
    }
    .progress-sheet__layout-side {
        width: 28%;
        padding-left: 0.18rem !important;
    }
    .progress-sheet__panel {
        border: 1px solid #4b3723;
        background: #fffef8;
    }
    .progress-sheet__panel--full {
        height: 100%;
        box-sizing: border-box;
    }
    .progress-sheet__panel + .progress-sheet__panel {
        margin-top: 0.18rem;
    }
    .progress-sheet__section-title {
        background: #f1e7d8;
        border-bottom: 1px solid #4b3723;
        text-align: center;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.18rem 0.3rem;
    }
    .progress-sheet__table th,
    .progress-sheet__side-table th,
    .progress-sheet__scale-table th {
        background: #f7f1e7;
        text-transform: uppercase;
        font-size: 0.61rem;
        font-weight: 700;
    }
    .progress-sheet__table td,
    .progress-sheet__side-table td,
    .progress-sheet__scale-table td {
        font-size: 0.68rem;
        line-height: 1.15;
    }
    .progress-sheet__table td,
    .progress-sheet__table th,
    .progress-sheet__side-table td:last-child,
    .progress-sheet__side-table th:last-child,
    .progress-sheet__scale-table td:first-child,
    .progress-sheet__scale-table th:first-child {
        text-align: center;
    }
    .progress-sheet__table .subject-col,
    .progress-sheet__side-table td:first-child {
        text-align: left;
        font-weight: 700;
    }
    .progress-sheet__table .subject-col {
        width: 36%;
    }
    .progress-sheet__remarks-body {
        padding: 0.35rem 0.4rem;
        font-size: 0.68rem;
        line-height: 1.28;
    }
    .progress-sheet__footer-wrap {
        border-top: 1px solid #4b3723;
        margin-top: 1rem;
        padding: 0.95rem 0.75rem 0.65rem;
    }
    .progress-sheet__footer {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    .progress-sheet__sign {
        text-align: center;
        font-size: 0.74rem;
        font-weight: 700;
    }
    .progress-sheet__sign-line {
        border-top: 2px solid #1a1309;
        margin: 0 auto 0.38rem;
        width: 88%;
    }
    .progress-sheet__sign-label {
        display: inline-block;
        padding-top: 0.12rem;
        letter-spacing: 0.01em;
    }
    @media (max-width: 900px) {
        .progress-sheet__brand {
            flex-direction: column;
        }
        .progress-sheet__meta td {
            display: block;
            width: 100%;
        }
        .progress-sheet__meta tr {
            display: block;
        }
        .progress-sheet__layout-table,
        .progress-sheet__layout-table tbody,
        .progress-sheet__layout-table tr,
        .progress-sheet__layout-table td {
            display: block;
            width: 100% !important;
        }
        .progress-sheet__layout-main,
        .progress-sheet__layout-side {
            padding: 0 !important;
        }
        .progress-sheet__panel + .progress-sheet__panel,
        .progress-sheet__layout-table tr + tr td {
            margin-top: 0.35rem;
        }
    }
    @media print {
        body { background: #fff !important; }
        .app-main, .reportcard-print-area { padding: 0 !important; }
        .card, .card-body { border: none !important; box-shadow: none !important; }
    }
</style>
@endpush
