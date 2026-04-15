@extends('layouts.app')
@section('title', 'Student Course Selection')
@section('page-title', 'Student Course Selection')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Choose Student</h6></div>
            <div class="card-body">
                <form method="GET" action="{{ route('timetable.student-courses') }}">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ (int)request('student_id') === (int)$student->id ? 'selected' : '' }}>
                                {{ $student->full_name }} ({{ $student->schoolClass->name ?? '-' }}/{{ $student->section->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Individualized Student Schedules</h6></div>
            <div class="card-body">
                @if($selectedStudent)
                <form method="POST" action="{{ route('timetable.student-courses.save') }}">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                    <div class="row g-2">
                        @forelse($subjects as $subject)
                        <div class="col-md-6">
                            <div class="form-check border rounded p-2">
                                <input class="form-check-input" type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" id="subject_{{ $subject->id }}" {{ in_array($subject->id, $selectedSubjectIds, true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="subject_{{ $subject->id }}">{{ $subject->name }}</label>
                            </div>
                        </div>
                        @empty
                        <div class="col-12"><div class="text-muted">No subjects found for this student's class.</div></div>
                        @endforelse
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-primary">Save Course Selection</button>
                    </div>
                </form>
                @else
                <div class="text-muted">Select a student to configure course choices.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
