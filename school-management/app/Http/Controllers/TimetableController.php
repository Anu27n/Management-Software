<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\FacultyCoverPreference;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\StaffLeaveRecord;
use App\Models\Student;
use App\Models\StudentCourseSelection;
use App\Models\SubstituteAssignment;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\TimetableEntry;
use App\Models\TimetableSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    private const DAY_LABELS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function dashboard()
    {
        $today = now()->toDateString();

        $stats = [
            'total_slots' => TimetableSlot::count(),
            'total_entries' => TimetableEntry::count(),
            'pending_staff_leaves' => StaffLeaveRecord::where('status', 'pending')->count(),
            'today_substitute_count' => SubstituteAssignment::whereDate('cover_date', $today)->count(),
            'upcoming_events' => SchoolEvent::whereDate('start_date', '>=', $today)->count(),
        ];

        $todaySubstitutes = SubstituteAssignment::with(['absentStaff:id,name', 'substituteStaff:id,name', 'timetableEntry.schoolClass:id,name', 'timetableEntry.section:id,name', 'timetableEntry.subject:id,name', 'timetableEntry.slot:id,name,start_time,end_time'])
            ->whereDate('cover_date', $today)
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        return view('timetable.dashboard', [
            'stats' => $stats,
            'todaySubstitutes' => $todaySubstitutes,
            'dayLabels' => self::DAY_LABELS,
        ]);
    }

    public function index(Request $request)
    {
        $classId = (int) $request->input('class_id');
        $sectionId = (int) $request->input('section_id');

        $classes = SchoolClass::orderBy('name')->get();
        $sections = $classId > 0
            ? \App\Models\Section::where('class_id', $classId)->orderBy('name')->get()
            : collect();

        $slots = TimetableSlot::orderBy('display_order')->orderBy('start_time')->get();

        $entriesQuery = TimetableEntry::with(['subject:id,name', 'teacher:id,name', 'slot:id,name,start_time,end_time'])
            ->when($classId > 0, fn ($q) => $q->where('class_id', $classId))
            ->when($sectionId > 0, fn ($q) => $q->where('section_id', $sectionId));

        if (!$request->user()->hasPermission('timetable.manage')) {
            if ($request->user()->isStudent()) {
                $student = Student::query()
                    ->where('student_user_id', $request->user()->id)
                    ->orWhere('email', $request->user()->email)
                    ->first();
                if ($student) {
                    $classId = $student->class_id;
                    $sectionId = $student->section_id;
                    $entriesQuery->where('class_id', $classId)->where('section_id', $sectionId);
                } else {
                    $entriesQuery->whereRaw('1 = 0');
                }
            }

            if ($request->user()->isParent()) {
                $ward = Student::where('parent_user_id', $request->user()->id)->first();
                if ($ward) {
                    $classId = $ward->class_id;
                    $sectionId = $ward->section_id;
                    $entriesQuery->where('class_id', $classId)->where('section_id', $sectionId);
                } else {
                    $entriesQuery->whereRaw('1 = 0');
                }
            }
        }

        $entries = $entriesQuery->get();

        $grid = [];
        foreach ($entries as $entry) {
            $grid[$entry->day_of_week][$entry->slot_id] = $entry;
        }

        $subjects = Subject::when($classId > 0, fn ($q) => $q->where('class_id', $classId))->orderBy('name')->get();
        $teachers = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('role', 'teacher')
                    ->orWhereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
            })
            ->orderBy('name')
            ->get();

        return view('timetable.index', [
            'classes' => $classes,
            'sections' => $sections,
            'slots' => $slots,
            'grid' => $grid,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'dayLabels' => self::DAY_LABELS,
            'selectedClassId' => $classId,
            'selectedSectionId' => $sectionId,
            'canManage' => $request->user()->hasPermission('timetable.manage'),
        ]);
    }

    public function storeSlot(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'display_order' => 'nullable|integer|min:1|max:50',
            'is_break' => 'nullable|boolean',
        ]);

        TimetableSlot::create([
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'display_order' => $validated['display_order'] ?? 1,
            'is_break' => (bool) ($validated['is_break'] ?? false),
        ]);

        return back()->with('success', 'Time slot created.');
    }

    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'slot_id' => 'required|exists:timetable_slots,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'room' => 'nullable|string|max:100',
        ]);

        $teacherBusy = TimetableEntry::where('teacher_id', $validated['teacher_id'])
            ->where('slot_id', $validated['slot_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->exists();

        if ($teacherBusy) {
            return back()->with('error', 'Selected teacher already has another class in this slot.');
        }

        TimetableEntry::updateOrCreate(
            [
                'class_id' => $validated['class_id'],
                'section_id' => $validated['section_id'],
                'slot_id' => $validated['slot_id'],
                'day_of_week' => $validated['day_of_week'],
            ],
            [
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'room' => $validated['room'] ?? null,
                'is_auto_generated' => false,
            ]
        );

        return back()->with('success', 'Timetable entry saved.');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $slots = TimetableSlot::where('is_break', false)->orderBy('display_order')->orderBy('start_time')->get();
        if ($slots->isEmpty()) {
            return back()->with('error', 'Create time slots before generating timetable.');
        }

        $subjects = Subject::where('class_id', $validated['class_id'])->orderBy('display_order')->orderBy('name')->get();
        if ($subjects->isEmpty()) {
            return back()->with('error', 'No subjects found for selected class.');
        }

        $teacherBySubject = TeacherAssignment::query()
            ->where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->pluck('user_id', 'subject_id');

        DB::transaction(function () use ($validated, $slots, $subjects, $teacherBySubject) {
            TimetableEntry::where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'])
                ->delete();

            $subjectIndex = 0;
            $subjectList = $subjects->values();

            for ($day = 1; $day <= 6; $day++) {
                foreach ($slots as $slot) {
                    $subject = $subjectList[$subjectIndex % max($subjectList->count(), 1)];
                    $teacherId = (int) ($teacherBySubject[$subject->id] ?? 0);

                    if ($teacherId <= 0) {
                        $teacherId = (int) TeacherAssignment::where('subject_id', $subject->id)->value('user_id');
                    }

                    if ($teacherId <= 0) {
                        $teacherId = (int) User::where('role', 'teacher')->orWhereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->value('id');
                    }

                    if ($teacherId > 0) {
                        TimetableEntry::create([
                            'class_id' => $validated['class_id'],
                            'section_id' => $validated['section_id'],
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacherId,
                            'slot_id' => $slot->id,
                            'day_of_week' => $day,
                            'is_auto_generated' => true,
                        ]);
                    }

                    $subjectIndex++;
                }
            }
        });

        return back()->with('success', 'Simple timetable generated successfully.');
    }

    public function studentCourses(Request $request)
    {
        $studentId = (int) $request->input('student_id');

        $students = Student::with(['schoolClass:id,name', 'section:id,name'])->orderBy('first_name')->orderBy('last_name')->limit(200)->get();
        $student = $studentId > 0 ? Student::find($studentId) : null;
        $subjects = collect();
        $selectedSubjectIds = [];

        if ($student) {
            $subjects = Subject::where('class_id', $student->class_id)->orderBy('name')->get();
            $selectedSubjectIds = StudentCourseSelection::where('student_id', $student->id)->pluck('subject_id')->toArray();
        }

        return view('timetable.student-courses', [
            'students' => $students,
            'selectedStudent' => $student,
            'subjects' => $subjects,
            'selectedSubjectIds' => $selectedSubjectIds,
        ]);
    }

    public function saveStudentCourses(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $subjectIds = collect($validated['subject_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        DB::transaction(function () use ($student, $subjectIds) {
            StudentCourseSelection::where('student_id', $student->id)->delete();

            foreach ($subjectIds as $subjectId) {
                StudentCourseSelection::create([
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $student->academic_year_id,
                ]);
            }
        });

        return redirect()->route('timetable.student-courses', ['student_id' => $student->id])
            ->with('success', 'Student course selection updated.');
    }

    public function staffLeaves(Request $request)
    {
        $staffId = (int) $request->input('staff_id');
        $status = $request->input('status');

        $teachers = $this->teacherUsers();

        $leaves = StaffLeaveRecord::with(['staff:id,name', 'approvedBy:id,name'])
            ->when($staffId > 0, fn ($q) => $q->where('staff_id', $staffId))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20);

        return view('timetable.staff-leaves', [
            'teachers' => $teachers,
            'leaves' => $leaves,
        ]);
    }

    public function storeStaffLeave(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        StaffLeaveRecord::create($validated + ['status' => 'pending']);

        return back()->with('success', 'Staff leave request logged.');
    }

    public function approveStaffLeave(StaffLeaveRecord $leave)
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Staff leave approved.');
    }

    public function rejectStaffLeave(StaffLeaveRecord $leave)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Staff leave rejected.');
    }

    public function substitutes(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $teachers = $this->teacherUsers();
        $coverPreferences = FacultyCoverPreference::whereIn('staff_id', $teachers->pluck('id'))
            ->get()
            ->keyBy('staff_id');

        $assignments = SubstituteAssignment::with([
            'absentStaff:id,name',
            'substituteStaff:id,name',
            'timetableEntry.schoolClass:id,name',
            'timetableEntry.section:id,name',
            'timetableEntry.subject:id,name',
            'timetableEntry.slot:id,name,start_time,end_time',
        ])
            ->whereDate('cover_date', $date)
            ->orderByDesc('id')
            ->paginate(30);

        return view('timetable.substitutes', [
            'assignments' => $assignments,
            'selectedDate' => $date,
            'teachers' => $teachers,
            'coverPreferences' => $coverPreferences,
        ]);
    }

    public function runAutoSubstitutes(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $dayOfWeek = (int) Carbon::parse($date)->isoWeekday();

        $approvedLeaves = StaffLeaveRecord::with('staff:id,name')
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->get();

        $created = 0;

        DB::transaction(function () use ($approvedLeaves, $date, $dayOfWeek, &$created) {
            foreach ($approvedLeaves as $leave) {
                $affectedEntries = TimetableEntry::with(['subject:id,name', 'slot:id,name,start_time,end_time'])
                    ->where('teacher_id', $leave->staff_id)
                    ->where('day_of_week', $dayOfWeek)
                    ->get();

                foreach ($affectedEntries as $entry) {
                    $substitute = $this->findBestSubstitute($leave->staff_id, $entry, $date, $dayOfWeek);

                    SubstituteAssignment::updateOrCreate(
                        [
                            'timetable_entry_id' => $entry->id,
                            'cover_date' => $date,
                        ],
                        [
                            'staff_leave_record_id' => $leave->id,
                            'absent_staff_id' => $leave->staff_id,
                            'substitute_staff_id' => $substitute?->id,
                            'status' => $substitute ? 'assigned' : 'unassigned',
                            'auto_assigned' => true,
                            'notes' => $substitute ? 'Auto-matched using subject, availability, and workload.' : 'No available substitute found.',
                        ]
                    );

                    $created++;
                }
            }
        });

        return redirect()->route('timetable.substitutes', ['date' => $date])
            ->with('success', "Auto substitute assignment completed for {$created} period(s).");
    }

    public function calendar(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $events = SchoolEvent::whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get();

        return view('timetable.calendar', [
            'events' => $events,
            'selectedMonth' => $month,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'required|string|max:50',
            'is_public' => 'nullable|boolean',
        ]);

        SchoolEvent::create($validated + [
            'created_by' => auth()->id(),
            'is_public' => (bool) ($validated['is_public'] ?? true),
        ]);

        return back()->with('success', 'Event added to school calendar.');
    }

    public function destroyEvent(SchoolEvent $event)
    {
        $event->delete();
        return back()->with('success', 'Event deleted.');
    }

    public function updateCoverPreference(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'max_daily_covers' => 'required|integer|min:0|max:8',
            'exclude_from_cover' => 'nullable|boolean',
        ]);

        FacultyCoverPreference::updateOrCreate(
            ['staff_id' => $validated['staff_id']],
            [
                'max_daily_covers' => $validated['max_daily_covers'],
                'exclude_from_cover' => (bool) ($validated['exclude_from_cover'] ?? false),
            ]
        );

        return back()->with('success', 'Cover preference updated.');
    }

    private function findBestSubstitute(int $absentStaffId, TimetableEntry $entry, string $date, int $dayOfWeek): ?User
    {
        $teachers = $this->teacherUsers();

        if ($teachers->isEmpty()) {
            return null;
        }

        $preferences = FacultyCoverPreference::whereIn('staff_id', $teachers->pluck('id'))->get()->keyBy('staff_id');
        $absentIds = StaffLeaveRecord::where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $busyTeacherIds = TimetableEntry::where('day_of_week', $dayOfWeek)
            ->where('slot_id', $entry->slot_id)
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $coverLoadByTeacher = SubstituteAssignment::whereDate('cover_date', $date)
            ->whereNotNull('substitute_staff_id')
            ->selectRaw('substitute_staff_id, COUNT(*) as total')
            ->groupBy('substitute_staff_id')
            ->pluck('total', 'substitute_staff_id');

        $subjectMatchedTeacherIds = TeacherAssignment::where('subject_id', $entry->subject_id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $candidates = $teachers
            ->reject(fn (User $teacher) => $teacher->id === $absentStaffId)
            ->reject(fn (User $teacher) => in_array($teacher->id, $absentIds, true))
            ->reject(fn (User $teacher) => in_array($teacher->id, $busyTeacherIds, true))
            ->reject(function (User $teacher) use ($preferences) {
                $pref = $preferences->get($teacher->id);
                return $pref && $pref->exclude_from_cover;
            })
            ->filter(function (User $teacher) use ($preferences, $coverLoadByTeacher) {
                $pref = $preferences->get($teacher->id);
                $maxLoad = $pref?->max_daily_covers ?? 2;
                $current = (int) ($coverLoadByTeacher[$teacher->id] ?? 0);
                return $current < $maxLoad;
            })
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $ranked = $candidates->sortBy(function (User $teacher) use ($subjectMatchedTeacherIds, $coverLoadByTeacher) {
            $subjectPenalty = in_array($teacher->id, $subjectMatchedTeacherIds, true) ? 0 : 1;
            $load = (int) ($coverLoadByTeacher[$teacher->id] ?? 0);
            return ($subjectPenalty * 100) + $load;
        })->values();

        return $ranked->first();
    }

    /**
     * @return Collection<int, User>
     */
    private function teacherUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('role', 'teacher')
                    ->orWhereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'is_active']);
    }
}
