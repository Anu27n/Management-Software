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

        return view('dashboard', $data);
    }
}
