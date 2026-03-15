<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

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
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'admins' => User::where('role', 'admin')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'parents' => User::where('role', 'parent')->count(),
            'students' => User::where('role', 'student')->count(),
        ];

        $customRoles = Schema::hasTable('roles')
            ? Role::where('is_system', false)->orderBy('name')->get()
            : collect();

        return view('settings.users', compact('users', 'counts', 'customRoles'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(['teacher', 'parent', 'student'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'nullable|boolean',
        ];

        if (Schema::hasTable('roles')) {
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncAssignedRoles($user, $validated['role_ids'] ?? []);

        return redirect()->route('settings.users')->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'teacher', 'parent', 'student'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
            'is_active' => 'nullable|boolean',
        ];

        if (Schema::hasTable('roles')) {
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($user->id === auth()->id() && !$request->boolean('is_active', true)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $this->syncAssignedRoles($user, $validated['role_ids'] ?? []);

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

        if ($systemRoleId) {
            $roleIds->push((int) $systemRoleId);
        }

        $user->roles()->sync($roleIds->unique()->all());
    }
}
