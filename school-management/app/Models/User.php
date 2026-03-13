<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'address', 'profile_photo', 'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isParent(): bool
    {
        return $this->hasRole('parent');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        if ($this->role === $role) {
            return true;
        }

        if (!$this->rbacTablesExist()) {
            return false;
        }

        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->rbacTablesExist()) {
            $roles = $this->relationLoaded('roles')
                ? $this->roles
                : $this->roles()->with('permissions')->get();

            foreach ($roles as $role) {
                if (!$role->relationLoaded('permissions')) {
                    $role->load('permissions');
                }

                if ($role->permissions->contains('slug', $permission)) {
                    return true;
                }
            }
        }

        return in_array($permission, $this->legacyPermissions(), true);
    }

    private function legacyPermissions(): array
    {
        return match ($this->role) {
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
            'parent', 'student' => [
                'dashboard.view',
                'homework.view',
                'notices.view',
                'reportcards.view',
                'leaves.apply',
            ],
            default => [],
        };
    }

    private function rbacTablesExist(): bool
    {
        static $exists;

        if ($exists === null) {
            $exists = Schema::hasTable('roles')
                && Schema::hasTable('permissions')
                && Schema::hasTable('permission_role')
                && Schema::hasTable('role_user');
        }

        return $exists;
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_user_id');
    }
}
