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
                <div class="text-muted small">Cashiers</div>
                <div class="fs-4 fw-bold">{{ $counts['cashiers'] }}</div>
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
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Auto">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select id="createRole" name="role" class="form-select" required>
                        <option value="teacher">Teacher</option>
                        <option value="cashier">Cashier / Accountant</option>
                        <option value="parent">Parent</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div id="createLinkedStudentWrap" class="col-md-6 d-none">
                    <label class="form-label">Linked Student (ID / Class / Section) <span class="text-danger">*</span></label>
                    <select id="createLinkedStudent" name="linked_student_id" class="form-select">
                        <option value="">Select Student Profile</option>
                        @if(auth()->user()?->hasPermission('students.manage'))
                            <option value="__create_new__">+ Create New Student...</option>
                        @endif
                        @foreach(($studentProfiles ?? collect()) as $studentProfile)
                            <option
                                value="{{ $studentProfile->id }}"
                                data-name="{{ $studentProfile->full_name }}"
                                data-email="{{ $studentProfile->email }}"
                                data-phone="{{ $studentProfile->phone }}"
                                data-admission="{{ $studentProfile->admission_no }}"
                            >
                                #{{ $studentProfile->id }} - {{ $studentProfile->full_name }} ({{ $studentProfile->admission_no }}) - {{ $studentProfile->schoolClass->name ?? '-' }}/{{ $studentProfile->section->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Required for role Student. This maps login to the selected student profile.</small>
                    @if(auth()->user()?->hasPermission('students.manage'))
                        <div class="mt-2">
                            <a href="{{ route('students.create') }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                <i class="bi bi-person-plus me-1"></i>Create New Student
                            </a>
                        </div>
                    @endif
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
                    <small class="text-muted">If you select any custom roles here, only those roles will control access for this user.</small>
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
                <input type="text" name="search" class="form-control" placeholder="Search name, email, username, phone" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                    <option value="cashier" {{ request('role') === 'cashier' ? 'selected' : '' }}>Cashier</option>
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
                    <th>Username</th>
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
                        <td>{{ $user->username ?: '-' }}</td>
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
                                                <label class="form-label">Username</label>
                                                <input type="text" name="username" class="form-control" value="{{ $user->username }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Role</label>
                                                <select name="role" class="form-select edit-role-select" data-target="#editUser{{ $user->id }}LinkedStudentWrap" required>
                                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="teacher" {{ $user->role === 'teacher' ? 'selected' : '' }}>Teacher</option>
                                                    <option value="cashier" {{ $user->role === 'cashier' ? 'selected' : '' }}>Cashier / Accountant</option>
                                                    <option value="parent" {{ $user->role === 'parent' ? 'selected' : '' }}>Parent</option>
                                                    <option value="student" {{ $user->role === 'student' ? 'selected' : '' }}>Student</option>
                                                </select>
                                            </div>
                                            @php
                                                $linkedStudentId = ($linkedStudentByUserId[$user->id] ?? null);
                                            @endphp
                                            <div id="editUser{{ $user->id }}LinkedStudentWrap" class="col-md-8 {{ $user->role === 'student' ? '' : 'd-none' }}">
                                                <label class="form-label">Linked Student (ID / Class / Section) <span class="text-danger">*</span></label>
                                                <select name="linked_student_id" class="form-select edit-linked-student" {{ $user->role === 'student' ? 'required' : '' }}>
                                                    <option value="">Select Student Profile</option>
                                                    @if(auth()->user()?->hasPermission('students.manage'))
                                                        <option value="__create_new__">+ Create New Student...</option>
                                                    @endif
                                                    @foreach(($studentProfiles ?? collect()) as $studentProfile)
                                                        <option
                                                            value="{{ $studentProfile->id }}"
                                                            data-name="{{ $studentProfile->full_name }}"
                                                            data-email="{{ $studentProfile->email }}"
                                                            data-phone="{{ $studentProfile->phone }}"
                                                            data-admission="{{ $studentProfile->admission_no }}"
                                                            {{ (int) $linkedStudentId === (int) $studentProfile->id ? 'selected' : '' }}
                                                        >
                                                            #{{ $studentProfile->id }} - {{ $studentProfile->full_name }} ({{ $studentProfile->admission_no }}) - {{ $studentProfile->schoolClass->name ?? '-' }}/{{ $studentProfile->section->name ?? '-' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if(auth()->user()?->hasPermission('students.manage'))
                                                    <div class="mt-2">
                                                        <a href="{{ route('students.create') }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                                            <i class="bi bi-person-plus me-1"></i>Create New Student
                                                        </a>
                                                    </div>
                                                @endif
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
                        <td colspan="8" class="text-center text-muted py-4">No accounts found.</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const createStudentUrl = @json(auth()->user()?->hasPermission('students.manage') ? route('students.create') : null);
    const createRole = document.getElementById('createRole');
    const createLinkedWrap = document.getElementById('createLinkedStudentWrap');
    const createLinkedSelect = document.getElementById('createLinkedStudent');
    const createName = document.querySelector('input[name="name"]');
    const createEmail = document.querySelector('input[name="email"]');
    const createPhone = document.querySelector('input[name="phone"]');
    const createUsername = document.querySelector('input[name="username"]');

    function toggleCreateLinkedStudent() {
        if (!createRole || !createLinkedWrap || !createLinkedSelect) {
            return;
        }

        const isStudentRole = createRole.value === 'student';
        createLinkedWrap.classList.toggle('d-none', !isStudentRole);
        createLinkedSelect.required = isStudentRole;

        if (!isStudentRole) {
            createLinkedSelect.value = '';
        }
    }

    function hydrateCreateFieldsFromStudent() {
        if (!createLinkedSelect) {
            return;
        }

        const opt = createLinkedSelect.options[createLinkedSelect.selectedIndex];
        if (!opt || !opt.value) {
            return;
        }

        if (opt.value === '__create_new__') {
            if (createStudentUrl) {
                window.open(createStudentUrl, '_blank', 'noopener');
            }
            createLinkedSelect.value = '';
            return;
        }

        if (createName && !createName.value.trim()) {
            createName.value = opt.dataset.name || '';
        }

        if (createEmail && !createEmail.value.trim() && (opt.dataset.email || '').trim() !== '') {
            createEmail.value = opt.dataset.email;
        }

        if (createPhone && !createPhone.value.trim() && (opt.dataset.phone || '').trim() !== '') {
            createPhone.value = opt.dataset.phone;
        }

        if (createUsername && !createUsername.value.trim() && (opt.dataset.admission || '').trim() !== '') {
            createUsername.value = opt.dataset.admission;
        }
    }

    if (createRole) {
        createRole.addEventListener('change', toggleCreateLinkedStudent);
        toggleCreateLinkedStudent();
    }

    if (createLinkedSelect) {
        createLinkedSelect.addEventListener('change', hydrateCreateFieldsFromStudent);
    }

    document.querySelectorAll('.edit-role-select').forEach(function (roleSelect) {
        const wrapSelector = roleSelect.getAttribute('data-target');
        const linkedWrap = wrapSelector ? document.querySelector(wrapSelector) : null;
        const linkedSelect = linkedWrap ? linkedWrap.querySelector('select[name="linked_student_id"]') : null;

        if (linkedSelect) {
            linkedSelect.addEventListener('change', function () {
                if (linkedSelect.value === '__create_new__') {
                    if (createStudentUrl) {
                        window.open(createStudentUrl, '_blank', 'noopener');
                    }
                    linkedSelect.value = '';
                }
            });
        }

        const toggle = function () {
            if (!linkedWrap || !linkedSelect) {
                return;
            }

            const isStudentRole = roleSelect.value === 'student';
            linkedWrap.classList.toggle('d-none', !isStudentRole);
            linkedSelect.required = isStudentRole;

            if (!isStudentRole) {
                linkedSelect.value = '';
            }
        };

        roleSelect.addEventListener('change', toggle);
        toggle();
    });
});
</script>
@endpush
