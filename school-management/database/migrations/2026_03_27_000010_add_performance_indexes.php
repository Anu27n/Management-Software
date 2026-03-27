<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'users_role_is_active_index');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['class_id', 'section_id', 'academic_year_id', 'status'], 'students_class_section_year_status_index');
            $table->index(['parent_user_id', 'status'], 'students_parent_status_index');
            $table->index(['email', 'status'], 'students_email_status_index');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->index(['class_id', 'academic_year_id'], 'fee_structures_class_year_index');
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->index(['student_id', 'fee_structure_id'], 'fee_payments_student_structure_index');
            $table->index(['status', 'payment_date'], 'fee_payments_status_payment_date_index');
            $table->index(['payment_date', 'collected_by'], 'fee_payments_payment_date_collected_by_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['class_id', 'date', 'status'], 'attendances_class_date_status_index');
            $table->index(['student_id', 'status'], 'attendances_student_status_index');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->index(['student_id', 'status'], 'leave_applications_student_status_index');
            $table->index(['class_id', 'status'], 'leave_applications_class_status_index');
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->index(['is_published', 'publish_date'], 'notices_published_date_index');
            $table->index(['target_audience', 'class_id'], 'notices_audience_class_index');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropIndex('notices_published_date_index');
            $table->dropIndex('notices_audience_class_index');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex('leave_applications_student_status_index');
            $table->dropIndex('leave_applications_class_status_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_class_date_status_index');
            $table->dropIndex('attendances_student_status_index');
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropIndex('fee_payments_student_structure_index');
            $table->dropIndex('fee_payments_status_payment_date_index');
            $table->dropIndex('fee_payments_payment_date_collected_by_index');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropIndex('fee_structures_class_year_index');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_class_section_year_status_index');
            $table->dropIndex('students_parent_status_index');
            $table->dropIndex('students_email_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_is_active_index');
        });
    }
};
