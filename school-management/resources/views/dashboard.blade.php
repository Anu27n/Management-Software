@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php($dashboardType = $dashboardType ?? 'academic')

@if(in_array($dashboardType, ['parent', 'student'], true))
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ $dashboardType === 'parent' ? 'Linked Students' : 'My Profile' }}</div>
                    <div class="fs-4 fw-bold">{{ $students->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Today Attendance</div>
                    <div class="fs-4 fw-bold">{{ $todayAttendance }}/{{ $totalPresent ?: $students->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Pending Leaves</div>
                    <div class="fs-4 fw-bold">{{ $pendingLeaves }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">Academic Year</div>
                    <div class="fs-5 fw-bold">{{ $academicYear?->name ?? 'Not Set' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Recent Notices</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($recentNotices as $notice)
                        <a href="{{ route('notices.show', $notice) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold">{{ $notice->title }}</div>
                            <small class="text-muted">{{ $notice->publish_date?->format('d M Y') }}</small>
                        </a>
                    @empty
                        <div class="text-center py-4 text-muted">No notices yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Recent Academic Activity</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($recentHomework as $item)
                        <div class="list-group-item border-0 border-bottom">
                            <div class="fw-semibold">{{ $item->title ?? 'Homework update' }}</div>
                            <small class="text-muted">{{ $item->created_at?->format('d M Y h:i A') }}</small>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No recent activity.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@else
    <div class="dashboard-hero card table-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
                <div>
                    <div class="dashboard-kicker">Academic Overview</div>
                    <h4 class="mb-1 fw-semibold">Dashboard</h4>
                    <div class="text-muted small">
                        {{ $academicYear?->name ?? 'All Academic Years' }}
                        @if(!empty($filters['date_from']) && !empty($filters['date_to']))
                            <span class="mx-1">|</span>
                            {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}
                        @endif
                    </div>
                </div>
                <div class="dashboard-pills">
                    <span class="dashboard-pill">Students: {{ $totalStudents }}</span>
                    <span class="dashboard-pill">Attendance: {{ number_format((float) $rangeAttendancePercentage, 2) }}%</span>
                    <span class="dashboard-pill">Classes: {{ collect($classDistributionLabels)->count() }}</span>
                </div>
            </div>

            <form method="GET" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">Academic Year</label>
                    <select name="academic_year_id" class="form-select dashboard-filter">
                        <option value="">All Years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) ($filters['academic_year_id'] ?? '') === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold">Class</label>
                    <select name="class_id" class="form-select dashboard-filter">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) ($filters['class_id'] ?? '') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold">Section</label>
                    <select name="section_id" class="form-select dashboard-filter">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ (string) ($filters['section_id'] ?? '') === (string) $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control dashboard-filter" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control dashboard-filter" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 d-flex justify-content-lg-end">
                    <button class="btn btn-primary dashboard-action-btn dashboard-action-compact">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card dashboard-stat-card h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="text-muted small">Total Students</div>
                        <div class="fs-4 fw-bold">{{ $totalStudents }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card dashboard-stat-card h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="text-muted small">Active Students</div>
                        <div class="fs-4 fw-bold">{{ $activeStudents }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card dashboard-stat-card h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="text-muted small">New Admissions</div>
                        <div class="fs-4 fw-bold">{{ $newAdmissions }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card dashboard-stat-card h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-box-arrow-right"></i></div>
                    <div>
                        <div class="text-muted small">Withdrawals This Month</div>
                        <div class="fs-4 fw-bold">{{ $withdrawalsThisPeriod }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Student Demographics</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Gender Ratio</div>
                                <div class="chart-shell chart-shell-md"><canvas id="genderRatioChart"></canvas></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Class-wise Distribution</div>
                                <div class="chart-shell chart-shell-md"><canvas id="classDistributionChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Section-wise Student Strength</h6>
                </div>
                <div class="card-body p-0">
                    @if(collect($sectionStrength)->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Section</th>
                                        <th class="text-end">Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sectionStrength as $item)
                                        <tr>
                                            <td>{{ $item['label'] }}</td>
                                            <td class="text-end fw-semibold">{{ $item['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">No section data available.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Attendance Analytics</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Today's Attendance</div>
                                <div class="fs-4 fw-bold">{{ number_format((float) $todayAttendancePercentage, 2) }}%</div>
                                <div class="small text-muted mt-1">Present + late entries counted for today.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">Attendance in Selected Range</div>
                                <div class="fs-4 fw-bold">{{ number_format((float) $rangeAttendancePercentage, 2) }}%</div>
                                <div class="small text-muted mt-1">Updates automatically based on the active filter range.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Class-wise Attendance</div>
                                <div class="chart-shell chart-shell-md"><canvas id="classAttendanceChart"></canvas></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Monthly Attendance Trend</div>
                                <div class="chart-shell chart-shell-md"><canvas id="monthlyAttendanceChart"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Today's Birthdays</h6>
                </div>
                <div class="card-body p-0">
                    @if($todayBirthdays->count())
                        <div class="list-group list-group-flush">
                            @foreach($todayBirthdays as $student)
                                <div class="list-group-item">
                                    <div class="fw-semibold">{{ $student->full_name }}</div>
                                    <small class="text-muted">{{ $student->schoolClass?->name }} {{ $student->section?->name ? '- ' . $student->section->name : '' }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">No birthdays today.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Academic Performance</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="border rounded-3 p-3">
                                <div class="fw-semibold mb-2">Average Marks per Class</div>
                                @if(collect($classPerformanceLabels)->count())
                                    <div class="chart-shell chart-shell-sm"><canvas id="classPerformanceChart"></canvas></div>
                                @else
                                    <div class="text-muted small">Performance data is not available for the current filter.</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Top Performing Students</div>
                                @if(collect($topPerformers)->count())
                                    <div class="list-group list-group-flush">
                                        @foreach($topPerformers as $student)
                                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">{{ $student['name'] ?: 'N/A' }}</div>
                                                    <small class="text-muted">{{ $student['admission_no'] ?: '-' }}</small>
                                                </div>
                                                <span class="badge bg-success-subtle text-success-emphasis">{{ number_format((float) $student['average_percentage'], 2) }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted small">Performance data not available.</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">Low Performing Students</div>
                                @if(collect($lowPerformers)->count())
                                    <div class="list-group list-group-flush">
                                        @foreach($lowPerformers as $student)
                                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">{{ $student['name'] ?: 'N/A' }}</div>
                                                    <small class="text-muted">{{ $student['admission_no'] ?: '-' }}</small>
                                                </div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis">{{ number_format((float) $student['average_percentage'], 2) }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted small">Performance data not available.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card table-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Announcements / Notices</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($announcements as $notice)
                        <a href="{{ route('notices.show', $notice) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold">{{ $notice->title }}</div>
                            <small class="text-muted">{{ $notice->publish_date?->format('d M Y') }}</small>
                        </a>
                    @empty
                        <div class="text-center py-4 text-muted">No announcements available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@endif
@endsection

@if($dashboardType === 'academic')
    @push('styles')
    <style>
        .dashboard-hero {
            border: 0;
            background:
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.12), transparent 32%),
                linear-gradient(135deg, #f8fbff 0%, #ffffff 46%, #f5f7fb 100%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }
        .dashboard-kicker {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #0f766e;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .dashboard-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .dashboard-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.25);
            color: #334155;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }
        .dashboard-filter {
            min-height: 44px;
            border-radius: 0.85rem;
            border-color: rgba(148, 163, 184, 0.35);
            box-shadow: none;
        }
        .dashboard-filter:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 0.18rem rgba(14, 165, 233, 0.12);
        }
        .dashboard-action-btn {
            min-height: 44px;
            border-radius: 0.85rem;
            font-weight: 600;
        }
        .dashboard-action-compact {
            min-width: 140px;
        }
        .dashboard-stat-card {
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
            border-radius: 1rem;
        }
        .chart-shell {
            position: relative;
            width: 100%;
            overflow: hidden;
        }
        .chart-shell-md {
            height: 240px;
            max-height: 240px;
        }
        .chart-shell-sm {
            height: 220px;
            max-height: 220px;
        }
        .chart-shell canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        .table-card .list-group-item {
            border-left: 0;
            border-right: 0;
        }
        .table-card {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
        }
        .table-card .card-header {
            border-bottom-color: rgba(148, 163, 184, 0.18);
        }
        .table-card .table thead th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        @media (max-width: 991.98px) {
            .dashboard-hero .card-body {
                padding: 1.25rem !important;
            }
        }
    </style>
    @endpush
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            const chartDefaults = {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                        }
                    }
                }
            };

            const createChart = (id, config) => {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, config);
            };

            createChart('genderRatioChart', {
                type: 'pie',
                data: {
                    labels: @json($genderLabels ?? []),
                    datasets: [{
                        data: @json($genderValues ?? []),
                        backgroundColor: ['#2563eb', '#ec4899', '#f59e0b'],
                        borderWidth: 0,
                    }]
                },
                options: chartDefaults
            });

            createChart('classDistributionChart', {
                type: 'bar',
                data: {
                    labels: @json($classDistributionLabels ?? []),
                    datasets: [{
                        label: 'Students',
                        data: @json($classDistributionValues ?? []),
                        backgroundColor: '#0ea5e9',
                        borderRadius: 8,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });

            createChart('classAttendanceChart', {
                type: 'bar',
                data: {
                    labels: @json($classAttendanceLabels ?? []),
                    datasets: [{
                        label: 'Attendance %',
                        data: @json($classAttendanceValues ?? []),
                        backgroundColor: '#14b8a6',
                        borderRadius: 8,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });

            createChart('monthlyAttendanceChart', {
                type: 'line',
                data: {
                    labels: @json($monthlyAttendanceLabels ?? []),
                    datasets: [{
                        label: 'Attendance %',
                        data: @json($monthlyAttendanceValues ?? []),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.12)',
                        fill: true,
                        tension: 0.35,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });

            createChart('classPerformanceChart', {
                type: 'bar',
                data: {
                    labels: @json($classPerformanceLabels ?? []),
                    datasets: [{
                        label: 'Average Marks %',
                        data: @json($classPerformanceValues ?? []),
                        backgroundColor: '#f97316',
                        borderRadius: 8,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });
        })();
    </script>
    @endpush
@endif
