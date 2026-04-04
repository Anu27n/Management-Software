<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ $siteSettings->logo_url ?? '/icons/icon-192.png' }}">
    <link rel="icon" type="image/png" href="{{ $siteSettings->favicon_url ?? '/favicon.ico' }}">
    <title>@yield('title', $siteSettings->school_name ?: 'School Management System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #3b82f6;
            --topbar-height: 56px;
            --bottom-nav-height: 64px;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --surface: #ffffff;
            --background: #f1f5f9;
            --on-surface: #1e293b;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        * { -webkit-tap-highlight-color: transparent; }

        body {
            background: var(--background);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overscroll-behavior: none;
            -webkit-overflow-scrolling: touch;
        }

        /* ===== DESKTOP SIDEBAR ===== */
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

        /* Main content - desktop */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }

        /* Sidebar overlay */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 1035;
        }

        /* ===== TOP APP BAR ===== */
        .topbar {
            height: var(--topbar-height); background: var(--primary);
            display: flex; align-items: center; padding: 0 16px;
            position: sticky; top: 0; z-index: 1030;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .topbar .btn-toggle-sidebar {
            display: none;
            background: none; border: none; color: #fff;
            width: 40px; height: 40px; border-radius: 50%;
            align-items: center; justify-content: center;
        }
        .topbar .btn-toggle-sidebar:active { background: rgba(255,255,255,0.2); }
        .topbar .page-title {
            font-size: 1.1rem; font-weight: 600; margin: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .quick-search-wrap {
            margin-left: 16px;
            flex: 1;
            max-width: 560px;
            position: relative;
        }
        .quick-search-input {
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 8px 14px 8px 36px;
            font-size: 0.9rem;
            outline: none;
            background: rgba(255,255,255,0.18);
            color: #fff;
        }
        .quick-search-input::placeholder {
            color: rgba(255,255,255,0.75);
        }
        .quick-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            pointer-events: none;
        }
        .quick-search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
            max-height: 62vh;
            overflow-y: auto;
            z-index: 1060;
            display: none;
        }
        .quick-search-results.show {
            display: block;
        }
        .quick-search-group-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 10px 14px 4px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .quick-search-group-label:first-child {
            border-top: none;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .quick-search-item {
            display: block;
            padding: 10px 14px;
            text-decoration: none;
            border-top: 1px solid #f1f5f9;
            color: #0f172a;
        }
        .quick-search-item:hover {
            background: #eff6ff;
            color: #0f172a;
        }
        .quick-search-title {
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.25;
        }
        .quick-search-subtitle,
        .quick-search-meta {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.25;
            margin-top: 2px;
        }
        .quick-search-status {
            font-size: 0.74rem;
            color: #64748b;
            padding: 10px 14px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
        }
        .topbar .user-section { color: rgba(255,255,255,0.9); }
        .topbar .user-section .btn {
            background: rgba(255,255,255,0.15); border: none; color: #fff;
        }
        .topbar .user-section .btn:hover { background: rgba(255,255,255,0.25); }
        .topbar .user-section .text-muted { color: rgba(255,255,255,0.7) !important; }

        /* Desktop topbar override */
        @media (min-width: 992px) {
            .topbar {
                background: #fff; color: var(--on-surface);
                border-bottom: 1px solid #e2e8f0; box-shadow: none;
            }
            .quick-search-input {
                background: #f1f5f9;
                color: #0f172a;
            }
            .quick-search-input::placeholder {
                color: #64748b;
            }
            .quick-search-icon {
                color: #64748b;
            }
            .topbar .user-section { color: var(--on-surface); }
            .topbar .user-section .btn {
                background: #f1f5f9; color: var(--on-surface);
            }
            .topbar .user-section .text-muted { color: #64748b !important; }
        }

        .page-content { padding: 24px; }

        /* Cards */
        .stat-card {
            border: none; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .table-card {
            border: none; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        /* ===== BOTTOM NAVIGATION (mobile only) ===== */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            height: calc(var(--bottom-nav-height) + var(--safe-bottom));
            padding-bottom: var(--safe-bottom);
            background: var(--surface);
            border-top: 1px solid #e2e8f0;
            z-index: 1050;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.08);
        }
        .bottom-nav-inner {
            display: flex; height: var(--bottom-nav-height);
            align-items: stretch; justify-content: space-around;
        }
        .bottom-nav-item {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-decoration: none; color: #94a3b8;
            font-size: 0.65rem; font-weight: 500;
            position: relative; transition: color 0.2s;
            padding: 4px 0;
        }
        .bottom-nav-item i { font-size: 1.35rem; margin-bottom: 2px; transition: transform 0.2s; }
        .bottom-nav-item.active { color: var(--primary); }
        .bottom-nav-item.active i { transform: scale(1.1); }
        .bottom-nav-item:active { background: rgba(59,130,246,0.06); }

        /* More menu (overflow for extra nav items) */
        .more-menu {
            display: none; position: fixed; bottom: calc(var(--bottom-nav-height) + var(--safe-bottom));
            left: 0; right: 0; background: var(--surface);
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.12);
            z-index: 1049; padding: 8px 0;
            border-radius: 16px 16px 0 0;
            max-height: 60vh; overflow-y: auto;
        }
        .more-menu.show { display: block; animation: slideUp 0.25s ease-out; }
        .more-menu-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.3); z-index: 1048;
        }
        .more-menu-overlay.show { display: block; }
        .more-menu-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 20px; color: var(--on-surface);
            text-decoration: none; font-size: 0.95rem;
            transition: background 0.2s;
        }
        .more-menu-item:hover, .more-menu-item:active {
            background: rgba(59,130,246,0.06); color: var(--on-surface);
        }
        .more-menu-item.active { color: var(--primary); font-weight: 600; }
        .more-menu-item i { font-size: 1.2rem; width: 24px; text-align: center; color: #64748b; }
        .more-menu-item.active i { color: var(--primary); }
        .more-menu-divider { height: 1px; background: #e2e8f0; margin: 4px 16px; }
        .more-menu-section {
            padding: 10px 20px 4px; font-size: 0.7rem;
            text-transform: uppercase; letter-spacing: 1px;
            color: #94a3b8; font-weight: 600;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ===== FAB ===== */
        .mobile-fab {
            display: none; position: fixed;
            bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px);
            right: 16px; z-index: 1045;
            width: 56px; height: 56px; border-radius: 16px;
            background: var(--primary); color: #fff; border: none;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
            font-size: 1.5rem; text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .mobile-fab:active { transform: scale(0.92); box-shadow: 0 2px 8px rgba(59,130,246,0.3); color: #fff; }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; padding-bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px); }
            .bottom-nav { display: block; }
            .mobile-fab { display: flex; align-items: center; justify-content: center; }
            .topbar .btn-toggle-sidebar { display: none; }
            .quick-search-wrap {
                margin-left: 10px;
                max-width: none;
            }
            .quick-search-input {
                font-size: 0.85rem;
                padding: 8px 12px 8px 32px;
            }
            .quick-search-results {
                left: -56px;
                right: -12px;
                max-height: 56vh;
            }
            .page-content { padding: 16px 12px; }

            /* Material-style cards */
            .card, .stat-card, .table-card {
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                border: none;
            }

            /* Bigger touch targets */
            .form-control, .form-select {
                padding: 12px 14px; font-size: 1rem;
                border-radius: 8px; min-height: 48px;
            }
            .btn { padding: 10px 20px; border-radius: 8px; font-size: 0.95rem; min-height: 44px; }
            .btn-sm { min-height: 36px; padding: 6px 14px; }

            /* Tables mobile friendly */
            .table th, .table td { padding: 10px 8px; font-size: 0.85rem; }
            .d-mobile-none { display: none !important; }

            /* Page transition */
            .page-content {
                animation: fadeSlideIn 0.25s ease-out;
            }
            @keyframes fadeSlideIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Material styling */
            .alert { border-radius: 12px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .modal-dialog { margin: 8px; }
            .modal-content { border-radius: 16px; }
            .dropdown-menu { border-radius: 12px; border: none; box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        }

        /* PWA standalone mode */
        .status-bar-spacer { display: none; }
        @media (display-mode: standalone) {
            .status-bar-spacer { display: block; height: env(safe-area-inset-top, 0px); background: var(--primary); }
        }

        /* Pagination fallback so links stay usable even if CDN styles are delayed. */
        .pagination {
            --bs-pagination-font-size: 0.95rem;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        .pagination .page-link {
            min-width: 2.5rem;
            min-height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            line-height: 1.2;
            border-radius: 0.65rem;
        }
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            font-size: 1rem;
            font-weight: 600;
        }
        .pagination svg {
            width: 1rem;
            height: 1rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Status bar spacer for PWA mode --}}
    <div class="status-bar-spacer"></div>

    {{-- Sidebar Overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    @php
        $authUser = auth()->user();
        $canManageStudents = $authUser->hasPermission('students.manage');
        $canManageStudentWithdrawals = $authUser->hasPermission('students.withdraw.manage') || $canManageStudents;
        $canManageAttendance = $authUser->hasPermission('attendance.manage');
        $canViewHomework = $authUser->hasPermission('homework.view');
        $canManageHomework = $authUser->hasPermission('homework.manage');
        $canManageReportCards = $authUser->hasPermission('reportcards.manage');
        $canViewReportCards = $authUser->hasPermission('reportcards.view');
        $canViewNotices = $authUser->hasPermission('notices.view');
        $canManageNotices = $authUser->hasPermission('notices.manage');
        $canApplyLeaves = $authUser->hasPermission('leaves.apply');
        $canManageFeeStructures = $authUser->hasPermission('fees.manage') || $authUser->hasPermission('fees.setup.manage');
        $canManageFeeGateway = $authUser->hasPermission('fees.manage') || $authUser->hasPermission('fees.gateway.manage');
        $canManageFeePayments = $authUser->hasPermission('fees.payments.manage') || $authUser->hasPermission('fees.quick-entry.manage');
        $canViewOwnFees = $authUser->isParent() || $authUser->isStudent();
        $canManageSettings = $authUser->hasPermission('settings.manage');
        $canManageUsers = $authUser->hasPermission('users.manage');
        $canManageNotifications = $authUser->hasPermission('notifications.manage');
        $canManageRoles = $authUser->hasPermission('roles.manage');
        $canUseQuickSearch = !$authUser->isParent()
            && !$authUser->isStudent()
            && (
                $authUser->hasAnyRole(['admin', 'teacher', 'cashier'])
                || $canManageStudents
                || $canManageFeePayments
                || $canManageUsers
            );

        $canManageAcademic = $canManageStudents || $canManageAttendance || $canManageReportCards;
        $canManageFees = $canManageFeeStructures || $canManageFeePayments;
        $showSettingsSection = $canManageSettings || $canManageUsers || $canManageNotifications || $canManageRoles;
    @endphp

    {{-- Desktop Sidebar --}}
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-mortarboard-fill"></i>
            <span>{{ $siteSettings->school_name ?: 'SchoolMS' }}</span>
        </div>

        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="nav-section">Academic</div>
        @if($canManageStudents)
            <a href="{{ route('students.create') }}" class="nav-link {{ request()->routeIs('students.create') ? 'active' : '' }}">
                <i class="bi bi-lightning-charge-fill"></i> Quick Admission
            </a>
            <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.index','students.show','students.edit','students.bulk-upload','students.bulk-upload.store','students.bulk-upload.template','students.promote','students.promote.store') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Students
            </a>
        @endif
        @if($canManageStudentWithdrawals)
            <a href="{{ route('students.withdrawals.index') }}" class="nav-link {{ request()->routeIs('students.withdrawals.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Withdrawals
            </a>
        @endif
        @if($canManageAttendance)
            <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> Attendance
            </a>
        @endif
        @if($canViewHomework)
        <a href="{{ route('homework.index') }}" class="nav-link {{ request()->routeIs('homework.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Homework
        </a>
        @endif
        @if($canViewReportCards)
        <a href="{{ $canManageReportCards ? route('reportcards.enter-marks') : route('reportcards.view') }}" class="nav-link {{ request()->routeIs('reportcards.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph-fill"></i> Report Cards
        </a>
        @endif

        <div class="nav-section">Management</div>
        @if($canManageFeeStructures)
            <a href="{{ route('fees.categories') }}" class="nav-link {{ request()->routeIs('fees.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Fee Setup
            </a>
        @endif
        @if($canManageFeeGateway)
            <a href="{{ route('settings.payment-gateway') }}" class="nav-link {{ request()->routeIs('settings.payment-gateway') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front"></i> Payment Gateway
            </a>
        @endif
        @if($canManageFeePayments)
            <a href="{{ route('fees.payments.create') }}" class="nav-link {{ request()->routeIs('fees.payments.create') ? 'active' : '' }}">
                <i class="bi bi-lightning-charge-fill"></i> Quick Fee Record
            </a>
            <a href="{{ route('fees.payments') }}" class="nav-link {{ request()->routeIs('fees.payments*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Fee Payments
            </a>
            <a href="{{ route('fees.discounts') }}" class="nav-link {{ request()->routeIs('fees.discounts') ? 'active' : '' }}">
                <i class="bi bi-percent"></i> Discount Records
            </a>
            <a href="{{ route('fees.due') }}" class="nav-link {{ request()->routeIs('fees.due') ? 'active' : '' }}">
                <i class="bi bi-exclamation-circle"></i> Due Fees
            </a>
        @endif
        @if($canViewOwnFees)
        <a href="{{ route('fees.my-fees') }}" class="nav-link {{ request()->routeIs('fees.my-fees') ? 'active' : '' }}">
            <i class="bi bi-wallet2"></i> My Fees
        </a>
        <a href="{{ route('attendance.my-attendance') }}" class="nav-link {{ request()->routeIs('attendance.my-attendance') ? 'active' : '' }}">
            <i class="bi bi-calendar-week"></i> My Attendance
        </a>
        @endif
        @if($canViewNotices)
        <a href="{{ route('notices.index') }}" class="nav-link {{ request()->routeIs('notices.*') ? 'active' : '' }}">
            <i class="bi bi-megaphone-fill"></i> Notices
        </a>
        @endif
        @if($canApplyLeaves)
        <a href="{{ route('leaves.index') }}" class="nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
            <i class="bi bi-envelope-paper-fill"></i> Leave Applications
        </a>
        @endif

        @if($showSettingsSection)
        <div class="nav-section">Settings</div>
        @if($canManageSettings)
        <a href="{{ route('settings.site') }}" class="nav-link {{ request()->routeIs('settings.site') ? 'active' : '' }}">
            <i class="bi bi-globe-central-south-asia"></i> Site Settings
        </a>
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
        @if($canManageUsers)
        <a href="{{ route('settings.users') }}" class="nav-link {{ request()->routeIs('settings.users*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> User Accounts
        </a>
        @endif
        @if($canManageRoles)
        <a href="{{ route('settings.roles-permissions') }}" class="nav-link {{ request()->routeIs('settings.roles-permissions*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> Roles & Permissions
        </a>
        @endif
        @if($canManageNotifications)
        <a href="{{ route('settings.notifications') }}" class="nav-link {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notifications
        </a>
        @endif
        @endif
    </nav>

    {{-- Main Content --}}
    <div class="main-content">
        {{-- Top App Bar --}}
        <div class="topbar">
            <button class="btn-toggle-sidebar me-2" onclick="toggleSidebar()">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h6 class="page-title">@yield('page-title', 'Dashboard')</h6>
            @if($canUseQuickSearch)
            <div class="quick-search-wrap">
                <i class="bi bi-search quick-search-icon"></i>
                <input
                    type="search"
                    id="quickSearchInput"
                    class="quick-search-input"
                    placeholder="Quick Search: students, fees, parents, staff"
                    autocomplete="off"
                >
                <div id="quickSearchResults" class="quick-search-results" role="listbox" aria-label="Quick search results"></div>
            </div>
            @endif
            <div class="ms-auto d-flex align-items-center gap-2 user-section">
                <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name }}</span>
                <div class="dropdown">
                    <button class="btn btn-sm rounded-circle" data-bs-toggle="dropdown" style="width:36px;height:36px;">
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

    {{-- Bottom Navigation (mobile only) --}}
    <nav class="bottom-nav" id="bottomNav">
        <div class="bottom-nav-inner">
            <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi {{ request()->routeIs('dashboard') ? 'bi-grid-1x2-fill' : 'bi-grid-1x2' }}"></i>
                <span>Home</span>
            </a>

            @if($canManageStudents)
                <a href="{{ route('students.create') }}" class="bottom-nav-item {{ request()->routeIs('students.create') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('students.create') ? 'bi-lightning-charge-fill' : 'bi-lightning-charge' }}"></i>
                    <span>Admit</span>
                </a>
            @elseif($canManageFeePayments)
                <a href="{{ route('fees.payments.create') }}" class="bottom-nav-item {{ request()->routeIs('fees.payments.create') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('fees.payments.create') ? 'bi-lightning-charge-fill' : 'bi-lightning-charge' }}"></i>
                    <span>Quick Fee</span>
                </a>
            @elseif($canViewOwnFees)
                <a href="{{ route('fees.my-fees') }}" class="bottom-nav-item {{ request()->routeIs('fees.my-fees') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('fees.my-fees') ? 'bi-wallet2' : 'bi-wallet' }}"></i>
                    <span>Fees</span>
                </a>
            @elseif($canViewHomework)
                <a href="{{ route('homework.index') }}" class="bottom-nav-item {{ request()->routeIs('homework.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('homework.*') ? 'bi-journal-text' : 'bi-journal' }}"></i>
                    <span>Homework</span>
                </a>
            @elseif($canViewNotices)
                <a href="{{ route('notices.index') }}" class="bottom-nav-item {{ request()->routeIs('notices.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('notices.*') ? 'bi-megaphone-fill' : 'bi-megaphone' }}"></i>
                    <span>Notices</span>
                </a>
            @endif

            @if($canManageStudents)
                <a href="{{ route('students.index') }}" class="bottom-nav-item {{ request()->routeIs('students.index','students.show','students.edit','students.bulk-upload','students.bulk-upload.store','students.bulk-upload.template','students.promote','students.promote.store') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('students.index','students.show','students.edit','students.bulk-upload','students.bulk-upload.store','students.bulk-upload.template','students.promote','students.promote.store') ? 'bi-people-fill' : 'bi-people' }}"></i>
                    <span>Students</span>
                </a>
            @endif

            @if($canManageFeePayments)
                <a href="{{ route('fees.payments') }}" class="bottom-nav-item {{ request()->routeIs('fees.payments*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('fees.payments*') ? 'bi-receipt-cutoff' : 'bi-receipt' }}"></i>
                    <span>Payments</span>
                </a>
            @elseif($canViewOwnFees)
                <a href="{{ route('reportcards.view') }}" class="bottom-nav-item {{ request()->routeIs('reportcards.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('reportcards.*') ? 'bi-file-earmark-bar-graph-fill' : 'bi-file-earmark-bar-graph' }}"></i>
                    <span>Reports</span>
                </a>
            @elseif($canManageFeeStructures)
                <a href="{{ route('fees.categories') }}" class="bottom-nav-item {{ request()->routeIs('fees.categories','fees.structures') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('fees.categories','fees.structures') ? 'bi-cash-stack' : 'bi-cash' }}"></i>
                    <span>Fees</span>
                </a>
            @elseif($canManageAttendance)
                <a href="{{ route('attendance.index') }}" class="bottom-nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('attendance.*') ? 'bi-calendar-check-fill' : 'bi-calendar-check' }}"></i>
                    <span>Attend.</span>
                </a>
            @elseif($canViewOwnFees && $canViewHomework)
                <a href="{{ route('homework.index') }}" class="bottom-nav-item {{ request()->routeIs('homework.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('homework.*') ? 'bi-journal-text' : 'bi-journal' }}"></i>
                    <span>Homework</span>
                </a>
            @elseif($canViewOwnFees && $canViewNotices)
                <a href="{{ route('notices.index') }}" class="bottom-nav-item {{ request()->routeIs('notices.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('notices.*') ? 'bi-megaphone-fill' : 'bi-megaphone' }}"></i>
                    <span>Notices</span>
                </a>
            @elseif($canViewReportCards)
                <a href="{{ $canManageReportCards ? route('reportcards.enter-marks') : route('reportcards.view') }}" class="bottom-nav-item {{ request()->routeIs('reportcards.*') ? 'active' : '' }}">
                    <i class="bi {{ request()->routeIs('reportcards.*') ? 'bi-file-earmark-bar-graph-fill' : 'bi-file-earmark-bar-graph' }}"></i>
                    <span>Reports</span>
                </a>
            @endif

            <a href="javascript:void(0)" class="bottom-nav-item {{ request()->routeIs('homework.*','notices.*','reportcards.*','leaves.*','settings.*','attendance.my-attendance') ? 'active' : '' }}" onclick="toggleMoreMenu()">
                <i class="bi bi-three-dots-vertical"></i>
                <span>More</span>
            </a>
        </div>
    </nav>

    {{-- More Menu Overlay --}}
    <div class="more-menu-overlay" id="moreMenuOverlay" onclick="toggleMoreMenu()"></div>

    {{-- More Menu --}}
    <div class="more-menu" id="moreMenu">
        <div class="more-menu-section">Academic</div>
        @if($canManageStudents)
        <a href="{{ route('students.create') }}" class="more-menu-item {{ request()->routeIs('students.create') ? 'active' : '' }}">
            <i class="bi bi-lightning-charge-fill"></i> Quick Admission
        </a>
        <a href="{{ route('students.index') }}" class="more-menu-item {{ request()->routeIs('students.index','students.show','students.edit','students.bulk-upload','students.bulk-upload.store','students.bulk-upload.template','students.promote','students.promote.store') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Students
        </a>
        @endif
        @if($canManageStudentWithdrawals)
        <a href="{{ route('students.withdrawals.index') }}" class="more-menu-item {{ request()->routeIs('students.withdrawals.*') ? 'active' : '' }}">
            <i class="bi bi-box-arrow-right"></i> Withdrawals
        </a>
        @endif
        @if($canManageAttendance)
        <a href="{{ route('attendance.index') }}" class="more-menu-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check-fill"></i> Attendance
        </a>
        @endif
        @if($canViewHomework)
        <a href="{{ route('homework.index') }}" class="more-menu-item {{ request()->routeIs('homework.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Homework
        </a>
        @endif
        @if($canViewReportCards)
        <a href="{{ $canManageReportCards ? route('reportcards.enter-marks') : route('reportcards.view') }}" class="more-menu-item {{ request()->routeIs('reportcards.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph-fill"></i> Report Cards
        </a>
        @endif

        <div class="more-menu-divider"></div>
        <div class="more-menu-section">Management</div>
        @if($canManageFeeStructures)
        <a href="{{ route('fees.categories') }}" class="more-menu-item {{ request()->routeIs('fees.categories','fees.structures') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Fee Setup
        </a>
        @endif
        @if($canManageFeeGateway)
        <a href="{{ route('settings.payment-gateway') }}" class="more-menu-item {{ request()->routeIs('settings.payment-gateway') ? 'active' : '' }}">
            <i class="bi bi-credit-card-2-front"></i> Payment Gateway
        </a>
        @endif
        @if($canManageFeePayments)
        <a href="{{ route('fees.payments.create') }}" class="more-menu-item {{ request()->routeIs('fees.payments.create') ? 'active' : '' }}">
            <i class="bi bi-lightning-charge-fill"></i> Quick Fee Record
        </a>
        <a href="{{ route('fees.payments') }}" class="more-menu-item {{ request()->routeIs('fees.payments*') ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i> Fee Payments
        </a>
        <a href="{{ route('fees.discounts') }}" class="more-menu-item {{ request()->routeIs('fees.discounts') ? 'active' : '' }}">
            <i class="bi bi-percent"></i> Discount Records
        </a>
        <a href="{{ route('fees.due') }}" class="more-menu-item {{ request()->routeIs('fees.due') ? 'active' : '' }}">
            <i class="bi bi-exclamation-circle"></i> Due Fees
        </a>
        @endif
        @if($canViewOwnFees)
        <a href="{{ route('fees.my-fees') }}" class="more-menu-item {{ request()->routeIs('fees.my-fees') ? 'active' : '' }}">
            <i class="bi bi-wallet2"></i> My Fees
        </a>
        <a href="{{ route('attendance.my-attendance') }}" class="more-menu-item {{ request()->routeIs('attendance.my-attendance') ? 'active' : '' }}">
            <i class="bi bi-calendar-week"></i> My Attendance
        </a>
        @endif
        @if($canViewNotices)
        <a href="{{ route('notices.index') }}" class="more-menu-item {{ request()->routeIs('notices.*') ? 'active' : '' }}">
            <i class="bi bi-megaphone-fill"></i> Notices
        </a>
        @endif
        @if($canApplyLeaves)
        <a href="{{ route('leaves.index') }}" class="more-menu-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
            <i class="bi bi-envelope-paper-fill"></i> Leave Applications
        </a>
        @endif

        @if($showSettingsSection)
        <div class="more-menu-divider"></div>
        <div class="more-menu-section">Settings</div>
        @if($canManageSettings)
        <a href="{{ route('settings.site') }}" class="more-menu-item {{ request()->routeIs('settings.site') ? 'active' : '' }}">
            <i class="bi bi-globe-central-south-asia"></i> Site Settings
        </a>
        <a href="{{ route('settings.classes') }}" class="more-menu-item {{ request()->routeIs('settings.classes') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Classes
        </a>
        <a href="{{ route('settings.sections') }}" class="more-menu-item {{ request()->routeIs('settings.sections') ? 'active' : '' }}">
            <i class="bi bi-diagram-3-fill"></i> Sections
        </a>
        <a href="{{ route('settings.subjects') }}" class="more-menu-item {{ request()->routeIs('settings.subjects') ? 'active' : '' }}">
            <i class="bi bi-book-fill"></i> Subjects
        </a>
        <a href="{{ route('settings.academic-years') }}" class="more-menu-item {{ request()->routeIs('settings.academic-years') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> Academic Years
        </a>
        @endif
        @if($canManageUsers)
        <a href="{{ route('settings.users') }}" class="more-menu-item {{ request()->routeIs('settings.users*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> User Accounts
        </a>
        @endif
        @if($canManageRoles)
        <a href="{{ route('settings.roles-permissions') }}" class="more-menu-item {{ request()->routeIs('settings.roles-permissions*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> Roles & Permissions
        </a>
        @endif
        @if($canManageNotifications)
        <a href="{{ route('settings.notifications') }}" class="more-menu-item {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notifications
        </a>
        @endif
        @endif

        <div class="more-menu-divider"></div>
        <a href="javascript:void(0)" class="more-menu-item text-danger" onclick="document.getElementById('logoutFormMobile').submit()">
            <i class="bi bi-box-arrow-right text-danger"></i> Logout
        </a>
        <form id="logoutFormMobile" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
    </div>

    {{-- FAB (context-sensitive) --}}
    @if($canManageStudents && request()->routeIs('students.index'))
        <a href="{{ route('students.create') }}" class="mobile-fab"><i class="bi bi-lightning-charge"></i></a>
    @elseif($canManageHomework && request()->routeIs('homework.index'))
        <a href="{{ route('homework.create') }}" class="mobile-fab"><i class="bi bi-plus-lg"></i></a>
    @elseif($canManageNotices && request()->routeIs('notices.index'))
        <a href="{{ route('notices.create') }}" class="mobile-fab"><i class="bi bi-plus-lg"></i></a>
    @elseif($canApplyLeaves && request()->routeIs('leaves.index'))
        <a href="{{ route('leaves.create') }}" class="mobile-fab"><i class="bi bi-plus-lg"></i></a>
    @elseif($canManageFeePayments && request()->routeIs('fees.payments'))
        <a href="{{ route('fees.payments.create') }}" class="mobile-fab"><i class="bi bi-plus-lg"></i></a>
    @elseif($canManageFeePayments && request()->routeIs('dashboard'))
        <a href="{{ route('fees.payments.create') }}" class="mobile-fab"><i class="bi bi-lightning-charge"></i></a>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        function toggleMoreMenu() {
            document.getElementById('moreMenu').classList.toggle('show');
            document.getElementById('moreMenuOverlay').classList.toggle('show');
        }

        window.addEventListener('popstate', function() {
            var menu = document.getElementById('moreMenu');
            if (menu.classList.contains('show')) { toggleMoreMenu(); }
        });

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(function(){});
        }

        document.querySelectorAll('.bottom-nav-item, .more-menu-item, .btn').forEach(function(el) {
            el.addEventListener('touchstart', function() { this.style.opacity = '0.7'; }, {passive: true});
            el.addEventListener('touchend', function() { this.style.opacity = '1'; }, {passive: true});
        });

        (function initQuickSearch() {
            var searchInput = document.getElementById('quickSearchInput');
            var searchResults = document.getElementById('quickSearchResults');
            if (!searchInput || !searchResults) {
                return;
            }

            var activeController = null;
            var debounceTimer = null;

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function hideResults() {
                searchResults.classList.remove('show');
            }

            function showStatus(message) {
                searchResults.innerHTML = '<div class="quick-search-status">' + escapeHtml(message) + '</div>';
                searchResults.classList.add('show');
            }

            function renderGroups(payload) {
                var groups = payload && payload.groups ? payload.groups : {};
                var labels = {
                    students: 'Students',
                    parents: 'Parents',
                    fee_records: 'Fee Records',
                    staff: 'Staff'
                };

                var html = '';
                var total = 0;

                Object.keys(labels).forEach(function(groupKey) {
                    var items = Array.isArray(groups[groupKey]) ? groups[groupKey] : [];
                    if (!items.length) {
                        return;
                    }

                    total += items.length;
                    html += '<div class="quick-search-group-label">' + labels[groupKey] + '</div>';

                    items.forEach(function(item) {
                        html += '<a class="quick-search-item" href="' + escapeHtml(item.url || '#') + '">';
                        html += '<div class="quick-search-title">' + escapeHtml(item.title || '-') + '</div>';
                        if (item.subtitle) {
                            html += '<div class="quick-search-subtitle">' + escapeHtml(item.subtitle) + '</div>';
                        }
                        if (item.meta) {
                            html += '<div class="quick-search-meta">' + escapeHtml(item.meta) + '</div>';
                        }
                        html += '</a>';
                    });
                });

                if (!total) {
                    showStatus('No matching records found.');
                    return;
                }

                html += '<div class="quick-search-status">' + total + ' result(s) in ' + (payload.took_ms || 0) + ' ms</div>';
                searchResults.innerHTML = html;
                searchResults.classList.add('show');
            }

            function performSearch(term) {
                if (activeController) {
                    activeController.abort();
                }

                activeController = new AbortController();
                showStatus('Searching...');

                fetch('{{ route('quick-search.index') }}?q=' + encodeURIComponent(term), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: activeController.signal
                })
                    .then(function(response) { return response.ok ? response.json() : Promise.reject(new Error('Search failed')); })
                    .then(renderGroups)
                    .catch(function(error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        showStatus('Unable to fetch search results right now.');
                    });
            }

            searchInput.addEventListener('input', function() {
                var term = searchInput.value.trim();

                clearTimeout(debounceTimer);

                if (term.length < 2) {
                    hideResults();
                    return;
                }

                debounceTimer = setTimeout(function() {
                    performSearch(term);
                }, 250);
            });

            searchInput.addEventListener('focus', function() {
                if (searchResults.innerHTML.trim() !== '') {
                    searchResults.classList.add('show');
                }
            });

            document.addEventListener('click', function(event) {
                if (!searchResults.contains(event.target) && event.target !== searchInput) {
                    hideResults();
                }
            });

            searchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    hideResults();
                    searchInput.blur();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
