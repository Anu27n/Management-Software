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
            ['name' => 'Edit Fee Payment Records', 'slug' => 'fees.payments.edit', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Delete Fee Payment Records', 'slug' => 'fees.payments.delete', 'group_name' => 'fees', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['fees.payments.edit', 'fees.payments.delete'])
            ->pluck('id', 'slug');

        if ($adminRoleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'permission_id' => $permissionId],
                    ['role_id' => $adminRoleId, 'permission_id' => $permissionId]
                );
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['fees.payments.edit', 'fees.payments.delete'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
