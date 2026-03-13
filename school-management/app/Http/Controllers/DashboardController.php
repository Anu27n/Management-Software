<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\FeePayment;
use App\Models\Attendance;
use App\Models\Notice;
use App\Models\LeaveApplication;
use App\Models\AcademicYear;

class DashboardController extends Controller
{
    public function index()
    {
        $academicYear = AcademicYear::current();
        $user = auth()->user();

        if ($user->isParent()) {
            $studentIds = Student::where('parent_user_id', $user->id)->pluck('id');

            $data = [
                'totalStudents' => $studentIds->count(),
                'totalTeachers' => User::where('role', 'teacher')->where('is_active', true)->count(),
                'totalParents' => User::where('role', 'parent')->where('is_active', true)->count(),
                'pendingFees' => FeePayment::whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
                'todayAttendance' => Attendance::whereIn('student_id', $studentIds)->where('date', today())->where('status', 'present')->count(),
                'totalPresent' => Attendance::whereIn('student_id', $studentIds)->where('date', today())->count(),
                'recentNotices' => Notice::where('is_published', true)
                    ->whereIn('target_audience', ['all', 'parents'])
                    ->latest()->take(5)->get(),
                'pendingLeaves' => LeaveApplication::whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
                'recentPayments' => FeePayment::with('student')->whereIn('student_id', $studentIds)->latest()->take(5)->get(),
                'academicYear' => $academicYear,
            ];
        } elseif ($user->isStudent()) {
            $studentIds = Student::where('email', $user->email)->pluck('id');

            $data = [
                'totalStudents' => $studentIds->count(),
                'totalTeachers' => User::where('role', 'teacher')->where('is_active', true)->count(),
                'totalParents' => User::where('role', 'parent')->where('is_active', true)->count(),
                'pendingFees' => FeePayment::whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
                'todayAttendance' => Attendance::whereIn('student_id', $studentIds)->where('date', today())->where('status', 'present')->count(),
                'totalPresent' => Attendance::whereIn('student_id', $studentIds)->where('date', today())->count(),
                'recentNotices' => Notice::where('is_published', true)
                    ->whereIn('target_audience', ['all', 'students'])
                    ->latest()->take(5)->get(),
                'pendingLeaves' => LeaveApplication::whereIn('student_id', $studentIds)->where('status', 'pending')->count(),
                'recentPayments' => FeePayment::with('student')->whereIn('student_id', $studentIds)->latest()->take(5)->get(),
                'academicYear' => $academicYear,
            ];
        } else {
            $data = [
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

        return view('dashboard', $data);
    }
}
