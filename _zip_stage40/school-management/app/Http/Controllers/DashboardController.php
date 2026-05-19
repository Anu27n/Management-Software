<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\LeaveApplication;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Models\TeacherAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = AcademicYear::current();
        $user = auth()->user();

        if ($user->isParent()) {
            return view('dashboard', $this->familyDashboardData($user, $academicYear, 'parent'));
        }

        if ($user->isStudent()) {
            return view('dashboard', $this->familyDashboardData($user, $academicYear, 'student'));
        }

        return view('dashboard', $this->academicDashboardData($user, $request, $academicYear));
    }

    private function academicDashboardData(User $user, Request $request, ?AcademicYear $currentAcademicYear): array
    {
        $selectedAcademicYearId = (int) ($request->input('academic_year_id') ?: $currentAcademicYear?->id);
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $selectedSectionId = $request->filled('section_id') ? (int) $request->input('section_id') : null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $assignedClassIds = $user->isTeacher()
            ? TeacherAssignment::query()->where('user_id', $user->id)->pluck('class_id')->filter()->unique()->values()
            : collect();

        $classesQuery = SchoolClass::query()->select(['id', 'name', 'numeric_name'])->orderBy('numeric_name')->orderBy('name');
        if ($assignedClassIds->isNotEmpty()) {
            $classesQuery->whereIn('id', $assignedClassIds);
        }
        $classes = $classesQuery->get();

        $sectionsQuery = Section::query()->select(['id', 'class_id', 'name'])->orderBy('name');
        if ($selectedClassId) {
            $sectionsQuery->where('class_id', $selectedClassId);
        } elseif ($classes->isNotEmpty()) {
            $sectionsQuery->whereIn('class_id', $classes->pluck('id'));
        }
        $sections = $sectionsQuery->get();

        $studentsBase = Student::query()
            ->select([
                'id', 'first_name', 'last_name', 'admission_no', 'gender', 'date_of_birth',
                'class_id', 'section_id', 'academic_year_id', 'admission_date', 'status', 'created_at'
            ])
            ->with(['schoolClass:id,name', 'section:id,name']);

        if ($selectedAcademicYearId) {
            $studentsBase->where('academic_year_id', $selectedAcademicYearId);
        }
        if ($selectedClassId) {
            $studentsBase->where('class_id', $selectedClassId);
        }
        if ($selectedSectionId) {
            $studentsBase->where('section_id', $selectedSectionId);
        }
        if ($assignedClassIds->isNotEmpty()) {
            $studentsBase->whereIn('class_id', $assignedClassIds);
        }

        $allFilteredStudents = (clone $studentsBase)->get();
        $activeStudents = $allFilteredStudents->where('status', 'active')->values();
        $studentIds = $allFilteredStudents->pluck('id');

        $withdrawalsBase = StudentWithdrawal::query()->with(['student:id,first_name,last_name,admission_no,class_id,section_id', 'student.schoolClass:id,name', 'student.section:id,name']);
        if ($studentIds->isNotEmpty()) {
            $withdrawalsBase->whereIn('student_id', $studentIds);
        } else {
            $withdrawalsBase->whereRaw('1 = 0');
        }

        $attendanceBase = Attendance::query()
            ->with(['student:id,first_name,last_name,admission_no', 'schoolClass:id,name', 'section:id,name'])
            ->whereBetween('date', [$dateFrom, $dateTo]);
        if ($selectedAcademicYearId) {
            $attendanceBase->where('academic_year_id', $selectedAcademicYearId);
        }
        if ($selectedClassId) {
            $attendanceBase->where('class_id', $selectedClassId);
        }
        if ($selectedSectionId) {
            $attendanceBase->where('section_id', $selectedSectionId);
        }
        if ($assignedClassIds->isNotEmpty()) {
            $attendanceBase->whereIn('class_id', $assignedClassIds);
        }

        $performanceBase = ExamResult::query()
            ->join('exams', 'exams.id', '=', 'exam_results.exam_id')
            ->join('students', 'students.id', '=', 'exam_results.student_id')
            ->where('exam_results.subject_category', 'scholastic');

        if ($selectedAcademicYearId) {
            $performanceBase->where('exams.academic_year_id', $selectedAcademicYearId);
        }
        if ($selectedClassId) {
            $performanceBase->where('students.class_id', $selectedClassId);
        }
        if ($selectedSectionId) {
            $performanceBase->where('students.section_id', $selectedSectionId);
        }
        if ($assignedClassIds->isNotEmpty()) {
            $performanceBase->whereIn('students.class_id', $assignedClassIds);
        }

        $todayAttendanceStats = (clone $attendanceBase)
            ->whereDate('date', today())
            ->selectRaw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
            ->first();

        $rangeAttendanceStats = (clone $attendanceBase)
            ->selectRaw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
            ->first();

        $classAttendance = (clone $attendanceBase)
            ->join('classes', 'classes.id', '=', 'attendances.class_id')
            ->selectRaw("classes.name as class_name, SUM(CASE WHEN attendances.status IN ('present','late') THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->class_name,
                'value' => (int) $row->total_count > 0 ? round(((int) $row->present_count / (int) $row->total_count) * 100, 2) : 0,
            ]);

        $monthlyAttendance = (clone $attendanceBase)
            ->selectRaw($this->monthExpression('date') . " as month_key, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->month_key,
                'value' => (int) $row->total_count > 0 ? round(((int) $row->present_count / (int) $row->total_count) * 100, 2) : 0,
            ]);

        $classPerformance = (clone $performanceBase)
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->selectRaw('classes.name as class_name, AVG(((COALESCE(exam_results.calculated_total, exam_results.marks_obtained) * 100.0) / NULLIF(exam_results.total_marks, 0))) as average_percentage')
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        $studentPerformance = (clone $performanceBase)
            ->selectRaw('students.id as student_id, students.first_name, students.last_name, students.admission_no, AVG(((COALESCE(exam_results.calculated_total, exam_results.marks_obtained) * 100.0) / NULLIF(exam_results.total_marks, 0))) as average_percentage')
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'students.admission_no')
            ->get()
            ->map(function ($row) {
                return [
                    'student_id' => $row->student_id,
                    'name' => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                    'admission_no' => $row->admission_no,
                    'average_percentage' => round((float) $row->average_percentage, 2),
                ];
            })
            ->filter(fn ($row) => $row['average_percentage'] > 0)
            ->values();

        $recentAdmissions = $allFilteredStudents
            ->sortByDesc(fn ($student) => $student->admission_date ?? $student->created_at)
            ->take(5)
            ->values();

        $recentWithdrawals = (clone $withdrawalsBase)
            ->latest('withdrawal_date')
            ->take(5)
            ->get();

        $recentAttendanceUpdates = (clone $attendanceBase)
            ->latest('updated_at')
            ->take(5)
            ->get();

        $todayBirthdays = $activeStudents
            ->filter(fn ($student) => $student->date_of_birth && $student->date_of_birth->format('m-d') === now()->format('m-d'))
            ->take(8)
            ->values();

        $announcements = $this->visibleNoticesForUser($user, 5, $selectedClassId);

        return [
            'dashboardType' => 'academic',
            'filters' => [
                'academic_year_id' => $selectedAcademicYearId,
                'class_id' => $selectedClassId,
                'section_id' => $selectedSectionId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'classes' => $classes,
            'sections' => $sections,
            'totalStudents' => $allFilteredStudents->count(),
            'activeStudents' => $activeStudents->count(),
            'newAdmissions' => $allFilteredStudents->filter(function ($student) use ($dateFrom, $dateTo) {
                if (!$student->admission_date) {
                    return false;
                }
                return $student->admission_date->between(Carbon::parse($dateFrom), Carbon::parse($dateTo));
            })->count(),
            'withdrawalsThisPeriod' => (clone $withdrawalsBase)
                ->whereBetween('withdrawal_date', [$dateFrom, $dateTo])
                ->count(),
            'genderLabels' => ['Male', 'Female', 'Other'],
            'genderValues' => [
                $activeStudents->where('gender', 'male')->count(),
                $activeStudents->where('gender', 'female')->count(),
                $activeStudents->where('gender', 'other')->count(),
            ],
            'classDistributionLabels' => $activeStudents->groupBy(fn ($student) => $student->schoolClass?->name ?? 'Unassigned')->keys()->values(),
            'classDistributionValues' => $activeStudents->groupBy(fn ($student) => $student->schoolClass?->name ?? 'Unassigned')->map->count()->values(),
            'sectionStrength' => $activeStudents
                ->groupBy(fn ($student) => trim(($student->schoolClass?->name ?? 'Unassigned') . ' - ' . ($student->section?->name ?? 'No Section')))
                ->map(fn ($group, $label) => ['label' => $label, 'total' => $group->count()])
                ->sortByDesc('total')
                ->values(),
            'todayAttendancePercentage' => (int) ($todayAttendanceStats->total_count ?? 0) > 0
                ? round(((int) $todayAttendanceStats->present_count / (int) $todayAttendanceStats->total_count) * 100, 2)
                : 0,
            'rangeAttendancePercentage' => (int) ($rangeAttendanceStats->total_count ?? 0) > 0
                ? round(((int) $rangeAttendanceStats->present_count / (int) $rangeAttendanceStats->total_count) * 100, 2)
                : 0,
            'classAttendanceLabels' => $classAttendance->pluck('label')->values(),
            'classAttendanceValues' => $classAttendance->pluck('value')->values(),
            'monthlyAttendanceLabels' => $monthlyAttendance->pluck('label')->values(),
            'monthlyAttendanceValues' => $monthlyAttendance->pluck('value')->values(),
            'classPerformanceLabels' => $classPerformance->pluck('class_name')->values(),
            'classPerformanceValues' => $classPerformance->map(fn ($row) => round((float) $row->average_percentage, 2))->values(),
            'topPerformers' => $studentPerformance->sortByDesc('average_percentage')->take(5)->values(),
            'lowPerformers' => $studentPerformance->sortBy('average_percentage')->take(5)->values(),
            'recentAdmissions' => $recentAdmissions,
            'recentWithdrawals' => $recentWithdrawals,
            'recentAttendanceUpdates' => $recentAttendanceUpdates,
            'todayBirthdays' => $todayBirthdays,
            'announcements' => $announcements,
            'academicYear' => AcademicYear::find($selectedAcademicYearId),
        ];
    }

    private function familyDashboardData(User $user, ?AcademicYear $academicYear, string $portal): array
    {
        $studentsQuery = Student::query()
            ->select([
                'id', 'first_name', 'last_name', 'class_id', 'section_id', 'academic_year_id',
                'parent_user_id', 'email', 'status'
            ])
            ->with(['schoolClass:id,name', 'section:id,name']);

        if ($portal === 'parent') {
            $studentsQuery->where('parent_user_id', $user->id);
        } else {
            $studentsQuery->where('email', $user->email);
        }

        $students = $studentsQuery->where('status', 'active')->get();
        $studentIds = $students->pluck('id');

        $attendanceTotals = null;
        if ($studentIds->isNotEmpty()) {
            $attendanceTotals = Attendance::query()
                ->selectRaw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_count")
                ->whereIn('student_id', $studentIds)
                ->whereDate('date', today())
                ->first();
        }

        return [
            'dashboardType' => $portal,
            'students' => $students,
            'todayAttendance' => (int) ($attendanceTotals->present_count ?? 0),
            'totalPresent' => $students->count(),
            'recentNotices' => $this->visibleNoticesForUser($user, 5),
            'pendingLeaves' => $studentIds->isEmpty()
                ? 0
                : LeaveApplication::query()->whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
            'recentHomework' => $studentIds->isEmpty()
                ? collect()
                : Homework::query()->latest()->take(5)->get(),
            'academicYear' => $academicYear,
        ];
    }

    private function visibleNoticesForUser(User $user, int $limit = 5, ?int $classId = null): Collection
    {
        $query = Notice::query()
            ->select(['id', 'title', 'content', 'publish_date', 'expiry_date', 'target_audience', 'class_id'])
            ->where('is_published', true)
            ->whereDate('publish_date', '<=', now())
            ->where(function ($builder) {
                $builder->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
            });

        if ($user->isParent()) {
            $query->whereIn('target_audience', ['all', 'parents']);
        } elseif ($user->isStudent()) {
            $query->whereIn('target_audience', ['all', 'students']);
        } else {
            $query->whereIn('target_audience', ['all', 'teachers']);
        }

        if ($classId) {
            $query->where(function ($builder) use ($classId) {
                $builder->whereNull('class_id')->orWhere('class_id', $classId);
            });
        }

        return $query->latest('publish_date')->take($limit)->get();
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
