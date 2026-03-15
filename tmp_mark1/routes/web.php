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
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RolePermissionController;

// Auth routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shared routes for all logged-in users
    Route::get('/homework', [HomeworkController::class, 'index'])->middleware('permission:homework.view')->name('homework.index');
    Route::get('/homework/{homework}', [HomeworkController::class, 'show'])
        ->whereNumber('homework')
        ->middleware('permission:homework.view')
        ->name('homework.show');

    Route::get('/notices', [NoticeController::class, 'index'])->middleware('permission:notices.view')->name('notices.index');
    Route::get('/notices/{notice}', [NoticeController::class, 'show'])
        ->whereNumber('notice')
        ->middleware('permission:notices.view')
        ->name('notices.show');

    Route::get('/reportcards/view', [ReportCardController::class, 'viewReportCard'])
        ->middleware('permission:reportcards.view')
        ->name('reportcards.view');

    Route::middleware('permission:leaves.apply')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::get('/leaves/{leaf}', [LeaveController::class, 'show'])->name('leaves.show');
    });

    Route::middleware('permission:students.manage')->group(function () {
        Route::get('/students/bulk-upload', [StudentController::class, 'bulkUploadForm'])->name('students.bulk-upload');
        Route::post('/students/bulk-upload', [StudentController::class, 'bulkUploadStore'])->name('students.bulk-upload.store');
        Route::get('/students/bulk-template', [StudentController::class, 'downloadBulkTemplate'])->name('students.bulk-upload.template');

        // Students
        Route::resource('students', StudentController::class);
        Route::get('/api/classes/{class}/sections', [StudentController::class, 'getSections'])->name('api.sections');
    });

    Route::middleware('permission:attendance.manage')->group(function () {
        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    });

    Route::middleware('permission:homework.manage')->group(function () {
        // Homework management
        Route::resource('homework', HomeworkController::class)->except(['index', 'show']);
        Route::get('/api/classes/{class}/subjects', [HomeworkController::class, 'getSubjects'])->name('api.subjects');
    });

    Route::middleware('permission:notices.manage')->group(function () {
        // Notice management
        Route::resource('notices', NoticeController::class)->except(['index', 'show']);
    });

    Route::middleware('permission:reportcards.manage')->group(function () {
        // Report card management
        Route::get('/reportcards/exams', [ReportCardController::class, 'exams'])->name('reportcards.exams');
        Route::post('/reportcards/exams', [ReportCardController::class, 'storeExam'])->name('reportcards.exams.store');
        Route::delete('/reportcards/exams/{exam}', [ReportCardController::class, 'destroyExam'])->name('reportcards.exams.destroy');
        Route::get('/reportcards/enter-marks', [ReportCardController::class, 'enterMarks'])->name('reportcards.enter-marks');
        Route::post('/reportcards/enter-marks', [ReportCardController::class, 'storeMarks'])->name('reportcards.store-marks');
        Route::get('/api/reportcards/classes/{class}/lookups', [ReportCardController::class, 'classLookups'])->name('api.reportcards.class-lookups');
    });

    Route::middleware('permission:leaves.approve')->group(function () {
        // Leave approvals
        Route::post('/leaves/{leaf}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leaf}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
    });

    Route::middleware('permission:fees.manage')->group(function () {
        // Fees
        Route::get('/fees/categories', [FeeController::class, 'categories'])->name('fees.categories');
        Route::post('/fees/categories', [FeeController::class, 'storeCategory'])->name('fees.categories.store');
        Route::delete('/fees/categories/{category}', [FeeController::class, 'destroyCategory'])->name('fees.categories.destroy');
        Route::get('/fees/structures', [FeeController::class, 'structures'])->name('fees.structures');
        Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
        Route::delete('/fees/structures/{structure}', [FeeController::class, 'destroyStructure'])->name('fees.structures.destroy');
    });

    Route::middleware('permission:fees.payments.manage')->group(function () {
        Route::get('/fees/payments', [FeeController::class, 'payments'])->name('fees.payments');
        Route::get('/fees/payments/create', [FeeController::class, 'createPayment'])->name('fees.payments.create');
        Route::post('/fees/payments', [FeeController::class, 'storePayment'])->name('fees.payments.store');
        Route::get('/fees/payments/{payment}', [FeeController::class, 'showPayment'])->name('fees.payments.show');
        Route::get('/api/students/{student}/fees', [FeeController::class, 'getStudentFees'])->name('api.student-fees');
        Route::post('/api/fees/razorpay/order', [FeeController::class, 'createRazorpayOrder'])->name('api.fees.razorpay.order');
        Route::post('/api/fees/razorpay/verify', [FeeController::class, 'verifyRazorpayPayment'])->name('api.fees.razorpay.verify');
    });

    // Settings
    Route::prefix('settings')->group(function () {

            Route::middleware('permission:exports.manage')->group(function () {
            // Exports
            Route::get('/export/students/csv', [ExportController::class, 'studentsCSV'])->name('export.students.csv');
            Route::get('/export/students/pdf', [ExportController::class, 'studentsPDF'])->name('export.students.pdf');
            Route::get('/export/payments/csv', [ExportController::class, 'paymentsCSV'])->name('export.payments.csv');
            Route::get('/export/payments/pdf', [ExportController::class, 'paymentsPDF'])->name('export.payments.pdf');
            Route::get('/export/attendance/csv', [ExportController::class, 'attendanceCSV'])->name('export.attendance.csv');
            Route::get('/export/reportcard/pdf', [ExportController::class, 'reportCardPDF'])->name('export.reportcard.pdf');
            });

            Route::middleware('permission:settings.manage')->group(function () {
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

            // User account management (staff/parents/students)
            Route::middleware('permission:users.manage')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('settings.users');
            Route::post('/users', [UserManagementController::class, 'store'])->name('settings.users.store');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('settings.users.update');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('settings.users.destroy');
            });

            // Notification settings
            Route::middleware('permission:notifications.manage')->group(function () {
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
            Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
            });

            // Payment gateway settings for fee collection
            Route::middleware('permission:fees.manage')->group(function () {
            Route::get('/payment-gateway', [SettingsController::class, 'paymentGateway'])->name('settings.payment-gateway');
            Route::post('/payment-gateway', [SettingsController::class, 'updatePaymentGateway'])->name('settings.payment-gateway.update');
            });

            // Roles & permissions
            Route::middleware('permission:roles.manage')->group(function () {
                Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('settings.roles-permissions');
                Route::post('/roles-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('settings.roles-permissions.store-role');
                Route::post('/roles-permissions/roles/{role}/permissions', [RolePermissionController::class, 'updateRolePermissions'])->name('settings.roles-permissions.update-role-permissions');
                Route::delete('/roles-permissions/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('settings.roles-permissions.destroy-role');
            });
        });
});
