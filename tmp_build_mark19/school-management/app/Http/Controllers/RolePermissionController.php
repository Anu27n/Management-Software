<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderByDesc('is_system')->orderBy('name')->get();
        $permissions = Permission::orderBy('group_name')->orderBy('name')->get()->groupBy('group_name');

        return view('settings.roles-permissions', compact('roles', 'permissions'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|alpha_dash|unique:roles,slug',
            'description' => 'nullable|string',
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['name']);
        if (Role::where('slug', $slug)->exists()) {
            return back()->with('error', 'Role slug already exists. Choose a different role name or slug.');
        }

        Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        return redirect()->route('settings.roles-permissions')->with('success', 'Custom role created successfully.');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permission_ids'] ?? []);

        return redirect()->route('settings.roles-permissions')->with('success', 'Role permissions updated.');
    }

    public function destroyRole(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return redirect()->route('settings.roles-permissions')->with('success', 'Custom role deleted.');
    }
}
