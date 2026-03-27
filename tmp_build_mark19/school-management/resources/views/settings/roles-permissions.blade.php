@extends('layouts.app')
@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-plus me-1"></i>Create Custom Role</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.roles-permissions.store-role') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Accounts Executive">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Slug (optional)</label>
                        <input type="text" name="slug" class="form-control" placeholder="e.g. accounts-executive">
                        <small class="text-muted">If left blank, it is auto-generated from role name.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="What this role is allowed to do."></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-1"></i>How To Use</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Create a custom role.</li>
                    <li>Open the role card below and select permissions.</li>
                    <li>Save permissions for that role.</li>
                    <li>Assign the role from User Accounts.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0">
                    System roles (Admin, Teacher, Parent, Student) cannot be deleted.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="accordion" id="rolesAccordion">
    @forelse($roles as $role)
        <div class="accordion-item mb-2 border-0 shadow-sm rounded overflow-hidden">
            <h2 class="accordion-header" id="headingRole{{ $role->id }}">
                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRole{{ $role->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapseRole{{ $role->id }}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold">{{ $role->name }}</span>
                        <span class="badge {{ $role->is_system ? 'bg-secondary' : 'bg-primary' }}">{{ $role->is_system ? 'System' : 'Custom' }}</span>
                        <span class="badge bg-light text-dark">{{ $role->permissions->count() }} permissions</span>
                    </div>
                </button>
            </h2>
            <div id="collapseRole{{ $role->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="headingRole{{ $role->id }}" data-bs-parent="#rolesAccordion">
                <div class="accordion-body bg-light-subtle">
                    <form method="POST" action="{{ route('settings.roles-permissions.update-role-permissions', $role) }}">
                        @csrf
                        <div class="row g-3">
                            @foreach($permissions as $group => $groupPermissions)
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded p-3 bg-white h-100">
                                        <div class="fw-semibold text-capitalize mb-2">{{ str_replace('_', ' ', $group ?: 'general') }}</div>
                                        @foreach($groupPermissions as $permission)
                                            <div class="form-check mb-1">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="permission_ids[]"
                                                    value="{{ $permission->id }}"
                                                    id="role{{ $role->id }}perm{{ $permission->id }}"
                                                    {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label small" for="role{{ $role->id }}perm{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-check-lg me-1"></i>Save Permissions
                            </button>
                            @if(!$role->is_system)
                                <button
                                    type="submit"
                                    form="deleteRole{{ $role->id }}"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Delete this custom role?');"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete Role
                                </button>
                            @endif
                        </div>
                    </form>

                    @if(!$role->is_system)
                        <form id="deleteRole{{ $role->id }}" method="POST" action="{{ route('settings.roles-permissions.destroy-role', $role) }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card table-card">
            <div class="card-body text-center text-muted py-4">No roles found.</div>
        </div>
    @endforelse
</div>
@endsection
