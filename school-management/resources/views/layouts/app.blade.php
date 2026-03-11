<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'School Management System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #3b82f6;
            --topbar-height: 60px;
        }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width); background: var(--sidebar-bg);
            z-index: 1040; overflow-y: auto; transition: transform 0.3s;
        }
        .sidebar .brand {
            padding: 18px 20px; font-size: 1.15rem; font-weight: 700;
            color: #fff; border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar .nav-link {
            color: var(--sidebar-text); padding: 10px 20px; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px; border-radius: 0;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(59,130,246,0.15); color: #fff;
        }
        .sidebar .nav-link.active { border-left: 3px solid var(--sidebar-active); }
        .sidebar .nav-link i { font-size: 1.1rem; width: 22px; text-align: center; }
        .sidebar .nav-section {
            padding: 15px 20px 5px; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 1px; color: #64748b; font-weight: 600;
        }

        /* Main content */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }

        /* Top bar */
        .topbar {
            height: var(--topbar-height); background: #fff;
            border-bottom: 1px solid #e2e8f0; display: flex;
            align-items: center; padding: 0 24px; position: sticky; top: 0; z-index: 1030;
        }
        .topbar .btn-toggle-sidebar { display: none; }

        .page-content { padding: 24px; }

        /* Cards */
        .stat-card {
            border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }

        /* Table */
        .table-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 1035;
        }

        /* Mobile responsive */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .topbar .btn-toggle-sidebar { display: inline-flex; }
            .page-content { padding: 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Sidebar Overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-mortarboard-fill"></i>
            <span>SchoolMS</span>
        </div>

        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="nav-section">Academic</div>
        <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Students
        </a>
        <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check-fill"></i> Attendance
        </a>
        <a href="{{ route('homework.index') }}" class="nav-link {{ request()->routeIs('homework.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Homework
        </a>
        <a href="{{ route('reportcards.exams') }}" class="nav-link {{ request()->routeIs('reportcards.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph-fill"></i> Report Cards
        </a>

        <div class="nav-section">Management</div>
        <a href="{{ route('fees.categories') }}" class="nav-link {{ request()->routeIs('fees.*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Fee Management
        </a>
        <a href="{{ route('notices.index') }}" class="nav-link {{ request()->routeIs('notices.*') ? 'active' : '' }}">
            <i class="bi bi-megaphone-fill"></i> Notices
        </a>
        <a href="{{ route('leaves.index') }}" class="nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
            <i class="bi bi-envelope-paper-fill"></i> Leave Applications
        </a>

        @if(auth()->user()->isAdmin())
        <div class="nav-section">Settings</div>
        <a href="{{ route('settings.classes') }}" class="nav-link {{ request()->routeIs('settings.classes') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Classes
        </a>
        <a href="{{ route('settings.sections') }}" class="nav-link {{ request()->routeIs('settings.sections') ? 'active' : '' }}">
            <i class="bi bi-diagram-3-fill"></i> Sections
        </a>
        <a href="{{ route('settings.subjects') }}" class="nav-link {{ request()->routeIs('settings.subjects') ? 'active' : '' }}">
            <i class="bi bi-book-fill"></i> Subjects
        </a>
        <a href="{{ route('settings.academic-years') }}" class="nav-link {{ request()->routeIs('settings.academic-years') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> Academic Years
        </a>
        @endif
    </nav>

    {{-- Main Content --}}
    <div class="main-content">
        {{-- Top Bar --}}
        <div class="topbar">
            <button class="btn btn-light btn-sm btn-toggle-sidebar me-3" onclick="toggleSidebar()">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h6 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h6>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name }}</span>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown" style="width:36px;height:36px;">
                        <i class="bi bi-person-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text fw-semibold">{{ auth()->user()->name }}</span></li>
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->role }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Page Content --}}
        <div class="page-content">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);
    </script>
    @stack('scripts')
</body>
</html>
