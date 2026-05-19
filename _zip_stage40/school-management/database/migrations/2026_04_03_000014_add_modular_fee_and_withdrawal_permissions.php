<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        $permissions = [
            ['name' => 'Manage Fee Setup', 'slug' => 'fees.setup.manage', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Payment Gateway', 'slug' => 'fees.gateway.manage', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Quick Fee Entry', 'slug' => 'fees.quick-entry.manage', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manage Student Withdrawals', 'slug' => 'students.withdraw.manage', 'group_name' => 'students', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $cashierRoleId = DB::table('roles')->where('slug', 'cashier')->value('id');
        $teacherRoleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        $pivotRows = [];

        foreach (['fees.setup.manage', 'fees.gateway.manage', 'fees.quick-entry.manage', 'students.withdraw.manage'] as $slug) {
            if ($adminRoleId && isset($permissionIds[$slug])) {
                $pivotRows[] = ['role_id' => $adminRoleId, 'permission_id' => $permissionIds[$slug]];
            }
        }

        foreach (['fees.quick-entry.manage'] as $slug) {
            if ($cashierRoleId && isset($permissionIds[$slug])) {
                $pivotRows[] = ['role_id' => $cashierRoleId, 'permission_id' => $permissionIds[$slug]];
            }
        }

        foreach (['students.withdraw.manage'] as $slug) {
            if ($teacherRoleId && isset($permissionIds[$slug])) {
                $pivotRows[] = ['role_id' => $teacherRoleId, 'permission_id' => $permissionIds[$slug]];
            }
        }

        foreach ($pivotRows as $row) {
            DB::table('permission_role')->updateOrInsert($row, $row);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $slugs = [
            'fees.setup.manage',
            'fees.gateway.manage',
            'fees.quick-entry.manage',
            'students.withdraw.manage',
        ];

        $permissionIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
