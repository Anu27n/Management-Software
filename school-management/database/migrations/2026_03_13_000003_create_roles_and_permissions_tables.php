<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group_name')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        $now = now();

        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group_name' => 'dashboard', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Students', 'slug' => 'students.manage', 'group_name' => 'students', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Attendance', 'slug' => 'attendance.manage', 'group_name' => 'attendance', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'View Homework', 'slug' => 'homework.view', 'group_name' => 'homework', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Homework', 'slug' => 'homework.manage', 'group_name' => 'homework', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'View Notices', 'slug' => 'notices.view', 'group_name' => 'notices', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Notices', 'slug' => 'notices.manage', 'group_name' => 'notices', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'View Report Cards', 'slug' => 'reportcards.view', 'group_name' => 'reportcards', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Report Cards', 'slug' => 'reportcards.manage', 'group_name' => 'reportcards', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Apply Leave', 'slug' => 'leaves.apply', 'group_name' => 'leaves', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Approve or Reject Leave', 'slug' => 'leaves.approve', 'group_name' => 'leaves', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Fee Categories and Structures', 'slug' => 'fees.manage', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Fee Payments', 'slug' => 'fees.payments.manage', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group_name' => 'settings', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage User Accounts', 'slug' => 'users.manage', 'group_name' => 'settings', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Roles and Permissions', 'slug' => 'roles.manage', 'group_name' => 'settings', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Notification Settings', 'slug' => 'notifications.manage', 'group_name' => 'settings', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Export Reports', 'slug' => 'exports.manage', 'group_name' => 'exports', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('permissions')->insert($permissions);

        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full access to the system', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Teacher', 'slug' => 'teacher', 'description' => 'Academic management access', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Parent', 'slug' => 'parent', 'description' => 'Parent portal access', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Student', 'slug' => 'student', 'description' => 'Student portal access', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('roles')->insert($roles);

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        $rolePermissionMap = [
            'admin' => $permissionIds->keys()->all(),
            'teacher' => [
                'dashboard.view',
                'students.manage',
                'attendance.manage',
                'homework.view',
                'homework.manage',
                'notices.view',
                'notices.manage',
                'reportcards.view',
                'reportcards.manage',
                'leaves.apply',
                'leaves.approve',
                'fees.payments.manage',
                'exports.manage',
            ],
            'parent' => [
                'dashboard.view',
                'homework.view',
                'notices.view',
                'reportcards.view',
                'leaves.apply',
            ],
            'student' => [
                'dashboard.view',
                'homework.view',
                'notices.view',
                'reportcards.view',
                'leaves.apply',
            ],
        ];

        $pivotRows = [];
        foreach ($rolePermissionMap as $roleSlug => $permissionSlugs) {
            $roleId = $roleIds[$roleSlug] ?? null;
            if (!$roleId) {
                continue;
            }

            foreach ($permissionSlugs as $permissionSlug) {
                $permissionId = $permissionIds[$permissionSlug] ?? null;
                if ($permissionId) {
                    $pivotRows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }
        }

        if (!empty($pivotRows)) {
            DB::table('permission_role')->insert($pivotRows);
        }

        $users = DB::table('users')->select('id', 'role')->get();
        $userRoleRows = [];

        foreach ($users as $user) {
            $roleId = $roleIds[$user->role] ?? null;
            if ($roleId) {
                $userRoleRows[] = [
                    'role_id' => $roleId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($userRoleRows)) {
            DB::table('role_user')->insert($userRoleRows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
