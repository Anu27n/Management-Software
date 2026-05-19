<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\UserCredentialSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'username', 'role', 'phone', 'is_active', 'created_at'])
            ->with('roles:id,name,is_system');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->when(Schema::hasColumn('users', 'username'), fn ($query) => $query->orWhere('username', 'like', "%{$search}%"))
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        $roleCounts = Cache::remember('users:role-counts', now()->addMinutes(5), function () {
            return User::query()
                ->selectRaw('role, COUNT(*) as total')
                ->whereIn('role', ['admin', 'teacher', 'cashier', 'parent', 'student'])
                ->groupBy('role')
                ->pluck('total', 'role');
        });

        $counts = [
            'admins' => (int) ($roleCounts['admin'] ?? 0),
            'teachers' => (int) ($roleCounts['teacher'] ?? 0),
            'cashiers' => (int) ($roleCounts['cashier'] ?? 0),
            'parents' => (int) ($roleCounts['parent'] ?? 0),
            'students' => (int) ($roleCounts['student'] ?? 0),
        ];

        $customRoles = Schema::hasTable('roles')
            ? Cache::remember('users:custom-roles', now()->addMinutes(5), fn () => Role::query()->select(['id', 'name'])->where('is_system', false)->orderBy('name')->get())
            : collect();

        $studentProfiles = collect();
        $linkedStudentByUserId = [];

        if (Schema::hasTable('students')) {
            $studentColumns = ['id', 'first_name', 'last_name', 'admission_no', 'class_id', 'section_id', 'email', 'phone'];
            if (Schema::hasColumn('students', 'student_user_id')) {
                $studentColumns[] = 'student_user_id';
            }

            $studentProfiles = Student::query()
                ->with(['schoolClass:id,name', 'section:id,name'])
                ->select($studentColumns)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            if (Schema::hasColumn('students', 'student_user_id')) {
                $linkedStudentByUserId = Student::query()
                    ->whereNotNull('student_user_id')
                    ->pluck('id', 'student_user_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        return view('settings.users', compact('users', 'counts', 'customRoles', 'studentProfiles', 'linkedStudentByUserId'));
    }

    public function store(Request $request)
    {
        Cache::forget('users:role-counts');
        $hasUsernameColumn = Schema::hasColumn('users', 'username');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(['teacher', 'cashier', 'parent', 'student'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'nullable|boolean',
            'linked_student_id' => 'nullable|exists:students,id',
        ];

        if ($hasUsernameColumn) {
            $rules['username'] = 'nullable|string|max:255|unique:users,username';
        }

        if (Schema::hasTable('roles')) {
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        if ($validated['role'] === 'student' && empty($validated['linked_student_id'])) {
            throw ValidationException::withMessages([
                'linked_student_id' => 'Select a student profile to link this student login account.',
            ]);
        }

        $linkedStudent = null;
        if ($validated['role'] === 'student' && !empty($validated['linked_student_id'])) {
            $linkedStudent = Student::find((int) $validated['linked_student_id']);
        }

        $userData = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($hasUsernameColumn) {
            $usernameBase = $validated['username'] ?? $validated['email'];

            if ($validated['role'] === 'student' && $linkedStudent && blank($validated['username'] ?? null)) {
                $usernameBase = $linkedStudent->admission_no;
            }

            $userData['username'] = UserCredentialSupport::generateUniqueUsername($usernameBase);
        }

        $user = DB::transaction(function () use ($userData, $validated) {
            $createdUser = User::create($userData);
            $this->syncAssignedRoles($createdUser, $validated['role_ids'] ?? []);

            $this->syncLinkedStudent(
                $createdUser,
                $validated['role'] === 'student' ? (int) ($validated['linked_student_id'] ?? 0) : null
            );

            return $createdUser;
        });

        return redirect()->route('settings.users')->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        Cache::forget('users:role-counts');
        $hasUsernameColumn = Schema::hasColumn('users', 'username');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'teacher', 'cashier', 'parent', 'student'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
            'is_active' => 'nullable|boolean',
            'linked_student_id' => 'nullable|exists:students,id',
        ];

        if ($hasUsernameColumn) {
            $rules['username'] = ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)];
        }

        if (Schema::hasTable('roles')) {
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        if ($validated['role'] === 'student' && empty($validated['linked_student_id'])) {
            throw ValidationException::withMessages([
                'linked_student_id' => 'Select a student profile to link this student login account.',
            ]);
        }

        $linkedStudent = null;
        if ($validated['role'] === 'student' && !empty($validated['linked_student_id'])) {
            $linkedStudent = Student::find((int) $validated['linked_student_id']);
        }

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($user->id === auth()->id() && !$request->boolean('is_active', true)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($hasUsernameColumn) {
            $usernameBase = $validated['username'] ?? $validated['email'];

            if ($validated['role'] === 'student' && $linkedStudent && blank($validated['username'] ?? null)) {
                $usernameBase = $linkedStudent->admission_no;
            }

            $updateData['username'] = UserCredentialSupport::generateUniqueUsername($usernameBase, $user->id);
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($user, $updateData, $validated) {
            $user->update($updateData);
            $this->syncAssignedRoles($user, $validated['role_ids'] ?? []);

            $this->syncLinkedStudent(
                $user,
                $validated['role'] === 'student' ? (int) ($validated['linked_student_id'] ?? 0) : null
            );
        });

        return redirect()->route('settings.users')->with('success', 'User account updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'At least one admin account must remain.');
        }

        Cache::forget('users:role-counts');
        $user->delete();

        return redirect()->route('settings.users')->with('success', 'User account deleted successfully.');
    }

    private function syncAssignedRoles(User $user, array $customRoleIds): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $systemRoleId = Role::where('slug', $user->role)->value('id');

        $roleIds = collect($customRoleIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($roleIds->isEmpty() && $systemRoleId) {
            $roleIds->push((int) $systemRoleId);
        }

        $user->roles()->sync($roleIds->unique()->all());
    }

    private function syncLinkedStudent(User $user, ?int $studentId): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'student_user_id')) {
            return;
        }

        Student::query()
            ->where('student_user_id', $user->id)
            ->when($studentId, fn ($query) => $query->where('id', '!=', $studentId))
            ->update(['student_user_id' => null]);

        if (!$studentId) {
            return;
        }

        $student = Student::find($studentId);
        if (!$student) {
            return;
        }

        $isLinkedToAnotherUser = Student::query()
            ->where('id', $studentId)
            ->whereNotNull('student_user_id')
            ->where('student_user_id', '!=', $user->id)
            ->exists();

        if ($isLinkedToAnotherUser) {
            throw ValidationException::withMessages([
                'linked_student_id' => 'This student profile is already linked to another login account.',
            ]);
        }

        $student->student_user_id = $user->id;
        $student->email = strtolower((string) $user->email);

        if (blank($student->phone) && filled($user->phone)) {
            $student->phone = $user->phone;
        }

        $student->save();
    }
}
