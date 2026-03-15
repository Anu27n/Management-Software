@extends('layouts.app')
@section('title', 'User Accounts')
@section('page-title', 'Staff & User Accounts')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card table-card">
            <div class="card-body">
                <div class="text-muted small">Admins</div>
                <div class="fs-4 fw-bold">{{ $counts['admins'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card table-card">
            <div class="card-body">
                <div class="text-muted small">Teachers</div>
                <div class="fs-4 fw-bold">{{ $counts['teachers'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card table-card">
            <div class="card-body">
                <div class="text-muted small">Parents</div>
                <div class="fs-4 fw-bold">{{ $counts['parents'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card table-card">
            <div class="card-body">
                <div class="text-muted small">Students</div>
                <div class="fs-4 fw-bold">{{ $counts['students'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-plus me-1"></i>Create Login Account</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('settings.users.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                @if($customRoles->isNotEmpty())
                <div class="col-12">
                    <label class="form-label">Additional Custom Roles</label>
                    <div class="row g-2">
                        @foreach($customRoles as $customRole)
                            <div class="col-md-3 col-lg-2">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $customRole->id }}" id="createRole{{ $customRole->id }}">
                                    <label class="form-check-label small" for="createRole{{ $customRole->id }}">{{ $customRole->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">The selected system role is always assigned automatically.</small>
                </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Create Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-header bg-white">
        <form class="row g-2" method="GET" action="{{ route('settings.users') }}">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, email, phone" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                    <option value="parent" {{ request('role') === 'parent' ? 'selected' : '' }}>Parent</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-light text-dark text-uppercase">{{ $user->role }}</span>
                            @foreach($user->roles->where('is_system', false) as $extraRole)
                                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $extraRole->name }}</span>
                            @endforeach
                        </td>
                        <td>{{ $user->phone ?: '-' }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('settings.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this account?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('settings.users.update', $user) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Account - {{ $user->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Role</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="teacher" {{ $user->role === 'teacher' ? 'selected' : '' }}>Teacher</option>
                                                    <option value="parent" {{ $user->role === 'parent' ? 'selected' : '' }}>Parent</option>
                                                    <option value="student" {{ $user->role === 'student' ? 'selected' : '' }}>Student</option>
                                                </select>
                                            </div>
                                            @if($customRoles->isNotEmpty())
                                            <div class="col-12">
                                                @php
                                                    $assignedCustomRoleIds = $user->roles->where('is_system', false)->pluck('id')->all();
                                                @endphp
                                                <label class="form-label">Additional Custom Roles</label>
                                                <div class="row g-2">
                                                    @foreach($customRoles as $customRole)
                                                        <div class="col-md-3">
                                                            <div class="form-check border rounded p-2">
                                                                <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $customRole->id }}" id="editUser{{ $user->id }}Role{{ $customRole->id }}" {{ in_array($customRole->id, $assignedCustomRoleIds, true) ? 'checked' : '' }}>
                                                                <label class="form-check-label small" for="editUser{{ $user->id }}Role{{ $customRole->id }}">{{ $customRole->name }}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                            <div class="col-md-6">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $user->phone }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Status</label>
                                                <select name="is_active" class="form-select">
                                                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                                                    <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">New Password (optional)</label>
                                                <input type="password" name="password" class="form-control" minlength="6">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Confirm Password</label>
                                                <input type="password" name="password_confirmation" class="form-control" minlength="6">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="2">{{ $user->address }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">
        {{ $users->links() }}
    </div>
</div>
@endsection
