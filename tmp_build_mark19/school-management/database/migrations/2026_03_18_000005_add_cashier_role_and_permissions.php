<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'cashier', 'parent', 'student') NOT NULL DEFAULT 'admin'");
        }

        $now = now();

        $cashierRoleId = DB::table('roles')->where('slug', 'cashier')->value('id');
        if (!$cashierRoleId) {
            $cashierRoleId = DB::table('roles')->insertGetId([
                'name' => 'Cashier / Accountant',
                'slug' => 'cashier',
                'description' => 'Fee collection and payment tracking access',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['dashboard.view', 'fees.payments.manage', 'notices.view', 'exports.manage'])
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('permission_role')
                ->where('role_id', $cashierRoleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                DB::table('permission_role')->insert([
                    'role_id' => $cashierRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $cashierUsers = DB::table('users')->where('role', 'cashier')->pluck('id');
        foreach ($cashierUsers as $userId) {
            $exists = DB::table('role_user')
                ->where('role_id', $cashierRoleId)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                DB::table('role_user')->insert([
                    'role_id' => $cashierRoleId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $cashierRoleId = DB::table('roles')->where('slug', 'cashier')->value('id');
        if ($cashierRoleId) {
            DB::table('role_user')->where('role_id', $cashierRoleId)->delete();
            DB::table('permission_role')->where('role_id', $cashierRoleId)->delete();
            DB::table('roles')->where('id', $cashierRoleId)->delete();
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'parent', 'student') NOT NULL DEFAULT 'admin'");
        }
    }
};
