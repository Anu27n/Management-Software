<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ExportController;

// Auth routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Students
    Route::resource('students', StudentController::class);
    Route::get('/api/classes/{class}/sections', [StudentController::class, 'getSections'])->name('api.sections');

    // Fees
    Route::get('/fees/categories', [FeeController::class, 'categories'])->name('fees.categories');
    Route::post('/fees/categories', [FeeController::class, 'storeCategory'])->name('fees.categories.store');
    Route::delete('/fees/categories/{category}', [FeeController::class, 'destroyCategory'])->name('fees.categories.destroy');
    Route::get('/fees/structures', [FeeController::class, 'structures'])->name('fees.structures');
    Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
    Route::delete('/fees/structures/{structure}', [FeeController::class, 'destroyStructure'])->name('fees.structures.destroy');
    Route::get('/fees/payments', [FeeController::class, 'payments'])->name('fees.payments');
    Route::get('/fees/payments/create', [FeeController::class, 'createPayment'])->name('fees.payments.create');
    Route::post('/fees/payments', [FeeController::class, 'storePayment'])->name('fees.payments.store');
    Route::get('/fees/payments/{payment}', [FeeController::class, 'showPayment'])->name('fees.payments.show');
    Route::get('/api/students/{student}/fees', [FeeController::class, 'getStudentFees'])->name('api.student-fees');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');

    // Homework
    Route::resource('homework', HomeworkController::class);
    Route::get('/api/classes/{class}/subjects', [HomeworkController::class, 'getSubjects'])->name('api.subjects');

    // Notices
    Route::resource('notices', NoticeController::class);

    // Report Cards
    Route::get('/reportcards/exams', [ReportCardController::class, 'exams'])->name('reportcards.exams');
    Route::post('/reportcards/exams', [ReportCardController::class, 'storeExam'])->name('reportcards.exams.store');
    Route::delete('/reportcards/exams/{exam}', [ReportCardController::class, 'destroyExam'])->name('reportcards.exams.destroy');
    Route::get('/reportcards/enter-marks', [ReportCardController::class, 'enterMarks'])->name('reportcards.enter-marks');
    Route::post('/reportcards/enter-marks', [ReportCardController::class, 'storeMarks'])->name('reportcards.store-marks');
    Route::get('/reportcards/view', [ReportCardController::class, 'viewReportCard'])->name('reportcards.view');

    // Leave Applications
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('/leaves/{leaf}', [LeaveController::class, 'show'])->name('leaves.show');
    Route::post('/leaves/{leaf}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leaf}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

    // Settings (Admin only)
    Route::middleware('role:admin')->prefix('settings')->group(function () {

        // Exports
        Route::get('/export/students/csv', [ExportController::class, 'studentsCSV'])->name('export.students.csv');
        Route::get('/export/students/pdf', [ExportController::class, 'studentsPDF'])->name('export.students.pdf');
        Route::get('/export/payments/csv', [ExportController::class, 'paymentsCSV'])->name('export.payments.csv');
        Route::get('/export/payments/pdf', [ExportController::class, 'paymentsPDF'])->name('export.payments.pdf');
        Route::get('/export/attendance/csv', [ExportController::class, 'attendanceCSV'])->name('export.attendance.csv');
        Route::get('/export/reportcard/pdf', [ExportController::class, 'reportCardPDF'])->name('export.reportcard.pdf');

        Route::get('/classes', [SettingsController::class, 'classes'])->name('settings.classes');
        Route::post('/classes', [SettingsController::class, 'storeClass'])->name('settings.classes.store');
        Route::delete('/classes/{class}', [SettingsController::class, 'destroyClass'])->name('settings.classes.destroy');

        Route::get('/sections', [SettingsController::class, 'sections'])->name('settings.sections');
        Route::post('/sections', [SettingsController::class, 'storeSection'])->name('settings.sections.store');
        Route::delete('/sections/{section}', [SettingsController::class, 'destroySection'])->name('settings.sections.destroy');

        Route::get('/subjects', [SettingsController::class, 'subjects'])->name('settings.subjects');
        Route::post('/subjects', [SettingsController::class, 'storeSubject'])->name('settings.subjects.store');
        Route::delete('/subjects/{subject}', [SettingsController::class, 'destroySubject'])->name('settings.subjects.destroy');

        Route::get('/academic-years', [SettingsController::class, 'academicYears'])->name('settings.academic-years');
        Route::post('/academic-years', [SettingsController::class, 'storeAcademicYear'])->name('settings.academic-years.store');
        Route::delete('/academic-years/{year}', [SettingsController::class, 'destroyAcademicYear'])->name('settings.academic-years.destroy');
    });
});
