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
        return [
            'dashboardType' => 'admin',
            'totalStudents' => Student::where('status', 'active')->count(),
            'totalTeachers' => User::where('role', 'teacher')->where('is_active', true)->count(),
            'totalParents' => User::where('role', 'parent')->where('is_active', true)->count(),
            'pendingFees' => FeePayment::where('status', 'pending')->count(),
            'todayAttendance' => Attendance::where('date', today())->where('status', 'present')->count(),
            'totalPresent' => Attendance::where('date', today())->count(),
            'recentNotices' => Notice::where('is_published', true)->latest()->take(5)->get(),
            'pendingLeaves' => LeaveApplication::where('status', 'pending')->count(),
            'recentPayments' => FeePayment::with('student')->latest()->take(5)->get(),
            'academicYear' => $academicYear,
        ];
    }

    private function teacherDashboardData(User $user, ?AcademicYear $academicYear): array
    {
        $assignments = TeacherAssignment::with(['schoolClass', 'section', 'subject'])
            ->where('user_id', $user->id)
            ->get();

        $classIds = $assignments->pluck('class_id')->filter()->unique()->values();
        $studentCount = $classIds->isEmpty()
            ? 0
            : Student::whereIn('class_id', $classIds)->where('status', 'active')->count();

        $todayAttendance = $classIds->isEmpty()
            ? 0
            : Attendance::whereIn('class_id', $classIds)->where('date', today())->where('status', 'present')->count();

        $markedToday = $classIds->isEmpty()
            ? 0
            : Attendance::whereIn('class_id', $classIds)->where('date', today())->count();

        $pendingLeaves = $classIds->isEmpty()
            ? 0
            : LeaveApplication::whereIn('class_id', $classIds)->where('status', 'pending')->count();

        $recentHomework = Homework::with(['schoolClass', 'section'])
            ->where('assigned_by', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return [
            'dashboardType' => 'teacher',
            'assignedClasses' => $assignments,
            'totalStudents' => $studentCount,
            'todayAttendance' => $todayAttendance,
            'totalPresent' => $markedToday,
            'pendingLeaves' => $pendingLeaves,
            'recentHomework' => $recentHomework,
            'recentNotices' => $this->visibleNoticesForUser($user, 5),
            'academicYear' => $academicYear,
        ];
    }

    private function cashierDashboardData(?AcademicYear $academicYear): array
    {
        return [
            'dashboardType' => 'cashier',
            'paymentsTodayCount' => FeePayment::whereDate('payment_date', today())->count(),
            'paymentsTodayAmount' => (float) FeePayment::whereDate('payment_date', today())->sum('amount_paid'),
            'pendingFees' => FeePayment::where('status', 'pending')->count(),
            'partialPayments' => FeePayment::where('status', 'partial')->count(),
            'recentPayments' => FeePayment::with(['student', 'feeStructure.feeCategory', 'collector'])
                ->latest()
                ->take(8)
                ->get(),
            'recentNotices' => $this->publishedNotices()
                ->whereIn('target_audience', ['all', 'teachers'])
                ->latest()
                ->take(5)
                ->get(),
            'academicYear' => $academicYear,
        ];
    }

    private function familyDashboardData(User $user, ?AcademicYear $academicYear, string $portal): array
    {
        $studentsQuery = Student::with(['schoolClass', 'section']);

        if ($portal === 'parent') {
            $studentsQuery->where('parent_user_id', $user->id);
        } else {
            $studentsQuery->where('email', $user->email);
        }

        $students = $studentsQuery->where('status', 'active')->get();
        $studentIds = $students->pluck('id');
        $feeOverview = $this->buildFeeOverviewForStudents($students);

        return [
            'dashboardType' => $portal,
            'students' => $students,
            'dueAmount' => $feeOverview['due_amount'],
            'dueItemsCount' => $feeOverview['items']->where('due_amount', '>', 0)->count(),
            'todayAttendance' => Attendance::whereIn('student_id', $studentIds)->where('date', today())->where('status', 'present')->count(),
            'totalPresent' => $students->count(),
            'recentNotices' => $this->visibleNoticesForUser($user, 5),
            'pendingLeaves' => LeaveApplication::whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
            'recentPayments' => FeePayment::with(['student', 'feeStructure.feeCategory'])
                ->whereIn('student_id', $studentIds)
                ->latest()
                ->take(5)
                ->get(),
            'academicYear' => $academicYear,
        ];
    }

    private function buildFeeOverviewForStudents(Collection $students): array
    {
        $items = [];
        $dueAmount = 0;

        foreach ($students as $student) {
            $structures = FeeStructure::with('feeCategory')
                ->where('class_id', $student->class_id)
                ->where('academic_year_id', $student->academic_year_id)
                ->get();

            foreach ($structures as $structure) {
                $paidAmount = (float) FeePayment::where('student_id', $student->id)
                    ->where('fee_structure_id', $structure->id)
                    ->sum('amount_paid');

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
            ->where('is_published', true)
            ->whereDate('publish_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
            });
    }

    private function visibleNoticesForUser(User $user, int $limit = 5): Collection
    {
        $query = $this->publishedNotices();

        if ($user->isParent()) {
            $classIds = Student::where('parent_user_id', $user->id)->pluck('class_id')->unique()->filter();

            $query->where(function ($noticeQuery) {
                $noticeQuery->where('target_audience', 'all')->orWhere('target_audience', 'parents');
            });

            if ($classIds->isNotEmpty()) {
                $query->where(function ($noticeQuery) use ($classIds) {
                    $noticeQuery->whereNull('class_id')->orWhereIn('class_id', $classIds);
                });
            } else {
                $query->whereNull('class_id');
            }
        } elseif ($user->isStudent()) {
            $classIds = Student::where('email', $user->email)->pluck('class_id')->unique()->filter();

            $query->where(function ($noticeQuery) {
                $noticeQuery->where('target_audience', 'all')->orWhere('target_audience', 'students');
            });

            if ($classIds->isNotEmpty()) {
                $query->where(function ($noticeQuery) use ($classIds) {
                    $noticeQuery->whereNull('class_id')->orWhereIn('class_id', $classIds);
                });
            } else {
                $query->whereNull('class_id');
            }
        } elseif ($user->isTeacher() || $user->isCashier()) {
            $query->whereIn('target_audience', ['all', 'teachers']);
        }

        return $query->latest()->take($limit)->get();
    }
}
