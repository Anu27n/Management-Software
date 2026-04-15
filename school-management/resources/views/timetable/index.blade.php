@extends('layouts.app')
@section('title', 'Timetable Generator')
@section('page-title', 'Timetable Generator')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-body">
                <form method="GET" action="{{ route('timetable.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (int)$selectedClassId === (int)$class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ (int)$selectedSectionId === (int)$section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($canManage)
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.generate') }}" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                    <input type="hidden" name="section_id" value="{{ $selectedSectionId }}">
                    <button class="btn btn-primary btn-sm w-100" {{ !$selectedClassId || !$selectedSectionId ? 'disabled' : '' }}>
                        <i class="bi bi-stars me-1"></i>Simple Timetable Generation
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Weekly Timetable</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 130px;">Day</th>
                    @foreach($slots as $slot)
                    <th style="min-width: 180px;">{{ $slot->name }}<div class="small text-muted">{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0,5) }} - {{ \Illuminate\Support\Str::of($slot->end_time)->substr(0,5) }}</div></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($day=1;$day<=6;$day++)
                <tr>
                    <td class="fw-semibold">{{ $dayLabels[$day] }}</td>
                    @foreach($slots as $slot)
                    @php $entry = $grid[$day][$slot->id] ?? null; @endphp
                    <td>
                        @if($entry)
                            <div class="fw-semibold">{{ $entry->subject->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $entry->teacher->name ?? '-' }}</div>
                            @if($entry->room)<div class="small text-muted">Room: {{ $entry->room }}</div>@endif
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

@if($canManage)
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Add Time Slot</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.slots.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input type="text" name="name" class="form-control form-control-sm" placeholder="Period 1" required></div>
                    <div class="col-6"><input type="time" name="start_time" class="form-control form-control-sm" required></div>
                    <div class="col-6"><input type="time" name="end_time" class="form-control form-control-sm" required></div>
                    <div class="col-6"><input type="number" name="display_order" min="1" value="1" class="form-control form-control-sm" placeholder="Order"></div>
                    <div class="col-6 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_break" value="1" id="is_break"><label class="form-check-label" for="is_break">Break</label></div></div>
                    <div class="col-12"><button class="btn btn-sm btn-outline-primary w-100">Save Slot</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">Quick Add Period</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.entries.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><select name="class_id" class="form-select form-select-sm" required><option value="">Class</option>@foreach($classes as $class)<option value="{{ $class->id }}" {{ (int)$selectedClassId === (int)$class->id ? 'selected' : '' }}>{{ $class->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="section_id" class="form-select form-select-sm" required><option value="">Section</option>@foreach($sections as $section)<option value="{{ $section->id }}" {{ (int)$selectedSectionId === (int)$section->id ? 'selected' : '' }}>{{ $section->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="day_of_week" class="form-select form-select-sm" required>@foreach($dayLabels as $idx => $label)<option value="{{ $idx }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="slot_id" class="form-select form-select-sm" required><option value="">Slot</option>@foreach($slots as $slot)<option value="{{ $slot->id }}">{{ $slot->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="subject_id" class="form-select form-select-sm" required><option value="">Subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="teacher_id" class="form-select form-select-sm" required><option value="">Teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><input type="text" name="room" class="form-control form-control-sm" placeholder="Room (optional)"></div>
                    <div class="col-md-6"><button class="btn btn-sm btn-primary w-100">Save Period</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
