<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Homework;
use App\Models\LeaveApplication;
use App\Models\Notice;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $academicYear = AcademicYear::current();
        $user = auth()->user();

        if ($user->isCashier()) {
            return view('dashboard', $this->cashierDashboardData($academicYear));
        }

        if ($user->isParent()) {
            return view('dashboard', $this->familyDashboardData($user, $academicYear, 'parent'));
        }

        if ($user->isStudent()) {
            return view('dashboard', $this->familyDashboardData($user, $academicYear, 'student'));
        }

        if ($user->isTeacher()) {
            return view('dashboard', $this->teacherDashboardData($user, $academicYear));
        }

        return view('dashboard', $this->adminDashboardData($academicYear));
    }

    private function adminDashboardData(?AcademicYear $academicYear): array
    {
        return Cache::remember(
            $this->dashboardCacheKey('admin', [
                'academic_year' => $academicYear?->id,
                'date' => today()->toDateString(),
            ]),
            now()->addSeconds(60),
            function () use ($academicYear) {
                $activeUserCounts = User::query()
                    ->selectRaw('role, COUNT(*) as total')
                    ->where('is_active', true)
                    ->whereIn('role', ['teacher', 'parent'])
                    ->groupBy('role')
                    ->pluck('total', 'role');

                $attendanceToday = Attendance::query()
                    ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
                    ->whereDate('date', today())
                    ->first();

                return [
                    'dashboardType' => 'admin',
                    'totalStudents' => Student::query()->where('status', 'active')->count(),
                    'totalTeachers' => (int) ($activeUserCounts['teacher'] ?? 0),
                    'totalParents' => (int) ($activeUserCounts['parent'] ?? 0),
                    'pendingFees' => FeePayment::query()->where('status', 'pending')->count(),
                    'todayAttendance' => (int) ($attendanceToday->present_count ?? 0),
                    'totalPresent' => (int) ($attendanceToday->total_count ?? 0),
                    'recentNotices' => $this->publishedNotices()
                        ->latest('publish_date')
                        ->take(5)
                        ->get(),
                    'pendingLeaves' => LeaveApplication::query()->where('status', 'pending')->count(),
                    'recentPayments' => FeePayment::query()
                        ->select(['id', 'student_id', 'receipt_no', 'amount_paid', 'payment_date', 'status'])
                        ->with('student:id,first_name,last_name')
                        ->latest()
                        ->take(5)
                        ->get(),
                    'academicYear' => $academicYear,
                ];
            }
        );
    }

    private function teacherDashboardData(User $user, ?AcademicYear $academicYear): array
    {
        return Cache::remember(
            $this->dashboardCacheKey('teacher', [
                'user' => $user->id,
                'academic_year' => $academicYear?->id,
                'date' => today()->toDateString(),
            ]),
            now()->addSeconds(60),
            function () use ($user, $academicYear) {
                $assignments = TeacherAssignment::query()
                    ->select(['id', 'user_id', 'class_id', 'section_id', 'subject_id', 'academic_year_id', 'is_class_teacher'])
                    ->with([
                        'schoolClass:id,name',
                        'section:id,name',
                        'subject:id,name',
                    ])
                    ->where('user_id', $user->id)
                    ->get();

                $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
                $attendanceTotals = null;

                if ($classIds->isNotEmpty()) {
                    $attendanceTotals = Attendance::query()
                        ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count, COUNT(*) as total_count")
                        ->whereIn('class_id', $classIds)
                        ->whereDate('date', today())
                        ->first();
                }

                return [
                    'dashboardType' => 'teacher',
                    'assignedClasses' => $assignments,
                    'totalStudents' => $classIds->isEmpty()
                        ? 0
                        : Student::query()->whereIn('class_id', $classIds)->where('status', 'active')->count(),
                    'todayAttendance' => (int) ($attendanceTotals->present_count ?? 0),
                    'totalPresent' => (int) ($attendanceTotals->total_count ?? 0),
                    'pendingLeaves' => $classIds->isEmpty()
                        ? 0
                        : LeaveApplication::query()->whereIn('class_id', $classIds)->where('status', 'pending')->count(),
                    'recentHomework' => Homework::query()
                        ->select(['id', 'title', 'class_id', 'section_id', 'assigned_by', 'created_at'])
                        ->with(['schoolClass:id,name', 'section:id,name'])
                        ->where('assigned_by', $user->id)
                        ->latest()
                        ->take(5)
                        ->get(),
                    'recentNotices' => $this->visibleNoticesForUser($user, 5),
                    'academicYear' => $academicYear,
                ];
            }
        );
    }

    private function cashierDashboardData(?AcademicYear $academicYear): array
    {
        return Cache::remember(
            $this->dashboardCacheKey('cashier', [
                'academic_year' => $academicYear?->id,
                'date' => today()->toDateString(),
            ]),
            now()->addSeconds(60),
            function () use ($academicYear) {
                return [
                    'dashboardType' => 'cashier',
                    'paymentsTodayCount' => FeePayment::query()->whereDate('payment_date', today())->count(),
                    'paymentsTodayAmount' => (float) FeePayment::query()->whereDate('payment_date', today())->sum('amount_paid'),
                    'pendingFees' => FeePayment::query()->where('status', 'pending')->count(),
                    'partialPayments' => FeePayment::query()->where('status', 'partial')->count(),
                    'recentPayments' => FeePayment::query()
                        ->select(['id', 'student_id', 'fee_structure_id', 'collected_by', 'receipt_no', 'amount_paid', 'payment_date', 'status'])
                        ->with([
                            'student:id,first_name,last_name',
                            'feeStructure:id,fee_category_id',
                            'feeStructure.feeCategory:id,name',
                            'collector:id,name',
                        ])
                        ->latest()
                        ->take(8)
                        ->get(),
                    'recentNotices' => $this->publishedNotices()
                        ->whereIn('target_audience', ['all', 'teachers'])
                        ->latest('publish_date')
                        ->take(5)
                        ->get(),
                    'academicYear' => $academicYear,
                ];
            }
        );
    }

    private function familyDashboardData(User $user, ?AcademicYear $academicYear, string $portal): array
    {
        return Cache::remember(
            $this->dashboardCacheKey($portal, [
                'user' => $user->id,
                'academic_year' => $academicYear?->id,
                'date' => today()->toDateString(),
            ]),
            now()->addSeconds(60),
            function () use ($user, $academicYear, $portal) {
                $studentsQuery = Student::query()
                    ->select([
                        'id',
                        'first_name',
                        'last_name',
                        'class_id',
                        'section_id',
                        'academic_year_id',
                        'parent_user_id',
                        'email',
                        'status',
                    ])
                    ->with(['schoolClass:id,name', 'section:id,name']);

                if ($portal === 'parent') {
                    $studentsQuery->where('parent_user_id', $user->id);
                } else {
                    $studentsQuery->where('email', $user->email);
                }

                $students = $studentsQuery
                    ->where('status', 'active')
                    ->get();

                $studentIds = $students->pluck('id');
                $feeOverview = $this->buildFeeOverviewForStudents($students);
                $attendanceTotals = null;

                if ($studentIds->isNotEmpty()) {
                    $attendanceTotals = Attendance::query()
                        ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
                        ->whereIn('student_id', $studentIds)
                        ->whereDate('date', today())
                        ->first();
                }

                return [
                    'dashboardType' => $portal,
                    'students' => $students,
                    'dueAmount' => $feeOverview['due_amount'],
                    'dueItemsCount' => $feeOverview['items']->where('due_amount', '>', 0)->count(),
                    'todayAttendance' => (int) ($attendanceTotals->present_count ?? 0),
                    'totalPresent' => $students->count(),
                    'recentNotices' => $this->visibleNoticesForUser($user, 5),
                    'pendingLeaves' => $studentIds->isEmpty()
                        ? 0
                        : LeaveApplication::query()->whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
                    'recentPayments' => $studentIds->isEmpty()
                        ? collect()
                        : FeePayment::query()
                            ->select(['id', 'student_id', 'fee_structure_id', 'amount_paid', 'payment_date', 'status'])
                            ->with(['student:id,first_name,last_name', 'feeStructure:id,fee_category_id', 'feeStructure.feeCategory:id,name'])
                            ->whereIn('student_id', $studentIds)
                            ->latest()
                            ->take(5)
                            ->get(),
                    'academicYear' => $academicYear,
                ];
            }
        );
    }

    private function buildFeeOverviewForStudents(Collection $students): array
    {
        if ($students->isEmpty()) {
            return [
                'items' => collect(),
                'due_amount' => 0,
            ];
        }

        $classYearPairs = $students
            ->map(fn (Student $student) => [
                'class_id' => $student->class_id,
                'academic_year_id' => $student->academic_year_id,
            ])
            ->unique(fn (array $pair) => $pair['class_id'] . '-' . $pair['academic_year_id'])
            ->values();

        $structures = FeeStructure::query()
            ->select(['id', 'fee_category_id', 'class_id', 'academic_year_id', 'amount'])
            ->with('feeCategory:id,name')
            ->where(function ($query) use ($classYearPairs) {
                foreach ($classYearPairs as $pair) {
                    $query->orWhere(function ($pairQuery) use ($pair) {
                        $pairQuery
                            ->where('class_id', $pair['class_id'])
                            ->where('academic_year_id', $pair['academic_year_id']);
                    });
                }
            })
            ->get()
            ->groupBy(fn (FeeStructure $structure) => $structure->class_id . '-' . $structure->academic_year_id);

        $paidAmounts = FeePayment::query()
            ->select('student_id', 'fee_structure_id', DB::raw('SUM(amount_paid) as total_paid'))
            ->whereIn('student_id', $students->pluck('id'))
            ->groupBy('student_id', 'fee_structure_id')
            ->get()
            ->mapWithKeys(fn ($payment) => [$payment->student_id . '-' . $payment->fee_structure_id => (float) $payment->total_paid]);

        $items = [];
        $dueAmount = 0;

        foreach ($students as $student) {
            $studentStructures = $structures->get($student->class_id . '-' . $student->academic_year_id, collect());

            foreach ($studentStructures as $structure) {
                $paidAmount = (float) ($paidAmounts[$student->id . '-' . $structure->id] ?? 0);
                $pendingAmount = max(0, (float) $structure->amount - $paidAmount);
                $dueAmount += $pendingAmount;

                $items[] = [
                    'student' => $student,
                    'structure' => $structure,
                    'due_amount' => $pendingAmount,
                ];
            }
        }

        return [
            'items' => collect($items),
            'due_amount' => $dueAmount,
        ];
    }

    private function publishedNotices()
    {
        return Notice::query()
            ->select(['id', 'title', 'content', 'publish_date', 'expiry_date', 'target_audience', 'class_id'])
            ->where('is_published', true)
            ->whereDate('publish_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
            });
    }

    private function visibleNoticesForUser(User $user, int $limit = 5): Collection
    {
        return Cache::remember(
            $this->dashboardCacheKey('notices', [
                'user' => $user->id,
                'limit' => $limit,
                'date' => today()->toDateString(),
            ]),
            now()->addSeconds(60),
            function () use ($user, $limit) {
                $query = $this->publishedNotices();

                if ($user->isParent()) {
                    $classIds = Student::query()
                        ->where('parent_user_id', $user->id)
                        ->pluck('class_id')
                        ->unique()
                        ->filter();

                    $query->where(function ($noticeQuery) {
                        $noticeQuery->where('target_audience', 'all')->orWhere('target_audience', 'parents');
                    });

                    if ($classIds->isNotEmpty()) {
                        $query->where(function ($noticeQuery) use ($classIds) {
                            $noticeQuery->whereNull('class_id')->orWhereIn('class_id', $classIds);
                        });
                    }
                } elseif ($user->isStudent()) {
                    $classIds = Student::query()
                        ->where('email', $user->email)
                        ->pluck('class_id')
                        ->unique()
                        ->filter();

                    $query->where(function ($noticeQuery) {
                        $noticeQuery->where('target_audience', 'all')->orWhere('target_audience', 'students');
                    });

                    if ($classIds->isNotEmpty()) {
                        $query->where(function ($noticeQuery) use ($classIds) {
                            $noticeQuery->whereNull('class_id')->orWhereIn('class_id', $classIds);
                        });
                    }
                } elseif ($user->isTeacher() || $user->isCashier()) {
                    $query->whereIn('target_audience', ['all', 'teachers']);
                }

                return $query->latest('publish_date')->take($limit)->get();
            }
        );
    }

    private function dashboardCacheKey(string $scope, array $parts = []): string
    {
        $suffix = collect($parts)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $key) => $key . ':' . $value)
            ->implode('|');

        return 'dashboard:' . $scope . ($suffix !== '' ? ':' . $suffix : '');
    }
}
