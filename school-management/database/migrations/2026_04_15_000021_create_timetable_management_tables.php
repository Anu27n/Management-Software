<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('display_order')->default(1);
            $table->boolean('is_break')->default(false);
            $table->timestamps();
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('timetable_slots')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon ... 7=Sun
            $table->string('room')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();

            $table->unique(['class_id', 'section_id', 'slot_id', 'day_of_week'], 'tt_class_slot_day_unique');
            $table->index(['teacher_id', 'slot_id', 'day_of_week'], 'tt_teacher_slot_day_idx');
        });

        Schema::create('staff_leave_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('reason');
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'from_date', 'to_date']);
            $table->index(['status', 'from_date']);
        });

        Schema::create('faculty_cover_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('max_daily_covers')->default(2);
            $table->boolean('exclude_from_cover')->default(false);
            $table->timestamps();

            $table->unique('staff_id');
        });

        Schema::create('substitute_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_leave_record_id')->constrained('staff_leave_records')->cascadeOnDelete();
            $table->foreignId('timetable_entry_id')->constrained('timetable_entries')->cascadeOnDelete();
            $table->foreignId('absent_staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('substitute_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('cover_date');
            $table->enum('status', ['assigned', 'unassigned', 'cancelled'])->default('assigned');
            $table->boolean('auto_assigned')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cover_date', 'substitute_staff_id']);
            $table->unique(['timetable_entry_id', 'cover_date'], 'substitute_entry_date_unique');
        });

        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('event_type')->default('general');
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::create('student_course_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id'], 'student_subject_year_unique');
        });

        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissions = [
            ['name' => 'View Timetable', 'slug' => 'timetable.view', 'group_name' => 'timetable', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Timetable', 'slug' => 'timetable.manage', 'group_name' => 'timetable', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Staff Leaves', 'slug' => 'staff-leaves.manage', 'group_name' => 'timetable', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Substitutes', 'slug' => 'substitutes.manage', 'group_name' => 'timetable', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage School Calendar', 'slug' => 'calendar.manage', 'group_name' => 'timetable', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $teacherRoleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        $studentRoleId = DB::table('roles')->where('slug', 'student')->value('id');
        $parentRoleId = DB::table('roles')->where('slug', 'parent')->value('id');
        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        $assignToAdmin = ['timetable.view', 'timetable.manage', 'staff-leaves.manage', 'substitutes.manage', 'calendar.manage'];
        $assignToTeacher = ['timetable.view', 'staff-leaves.manage', 'substitutes.manage', 'calendar.manage'];
        $assignToStudentAndParent = ['timetable.view'];

        foreach ($assignToAdmin as $slug) {
            if ($adminRoleId && isset($permissionIds[$slug])) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'permission_id' => $permissionIds[$slug]],
                    ['role_id' => $adminRoleId, 'permission_id' => $permissionIds[$slug]]
                );
            }
        }

        foreach ($assignToTeacher as $slug) {
            if ($teacherRoleId && isset($permissionIds[$slug])) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $teacherRoleId, 'permission_id' => $permissionIds[$slug]],
                    ['role_id' => $teacherRoleId, 'permission_id' => $permissionIds[$slug]]
                );
            }
        }

        foreach ($assignToStudentAndParent as $slug) {
            if ($studentRoleId && isset($permissionIds[$slug])) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $studentRoleId, 'permission_id' => $permissionIds[$slug]],
                    ['role_id' => $studentRoleId, 'permission_id' => $permissionIds[$slug]]
                );
            }
            if ($parentRoleId && isset($permissionIds[$slug])) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $parentRoleId, 'permission_id' => $permissionIds[$slug]],
                    ['role_id' => $parentRoleId, 'permission_id' => $permissionIds[$slug]]
                );
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'timetable.view',
            'timetable.manage',
            'staff-leaves.manage',
            'substitutes.manage',
            'calendar.manage',
        ];

        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
            if ($permissionIds->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }

        Schema::dropIfExists('student_course_selections');
        Schema::dropIfExists('school_events');
        Schema::dropIfExists('substitute_assignments');
        Schema::dropIfExists('faculty_cover_preferences');
        Schema::dropIfExists('staff_leave_records');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_slots');
    }
};
