<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        if (blank($admin->username) && Schema::hasColumn('users', 'username')) {
            $admin->forceFill(['username' => 'admin'])->save();
        }

        $this->syncRole($admin);

        AcademicYear::query()->firstOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_active' => true,
            ]
        );

        $this->call(SchoolFeeStructureSeeder::class);

        $targetUserCount = 100;
        $remainingUsers = max(0, $targetUserCount - User::query()->count());

        if ($remainingUsers === 0) {
            return;
        }

        $roles = Collection::make(['teacher', 'parent', 'student', 'cashier']);
        $seededUsers = User::factory()
            ->count($remainingUsers)
            ->sequence(fn ($sequence) => [
                'role' => $roles[$sequence->index % $roles->count()],
            ])
            ->create();

        $seededUsers->each(fn (User $user) => $this->syncRole($user));
    }

    private function syncRole(User $user): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('role_user')) {
            return;
        }

        $roleId = Role::query()
            ->where('slug', $user->role)
            ->value('id');

        if ($roleId === null) {
            return;
        }

        $user->roles()->syncWithoutDetaching([$roleId]);
    }
}
