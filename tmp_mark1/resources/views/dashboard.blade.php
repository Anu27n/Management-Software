@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="text-muted small">Total Students</div>
                    <div class="fs-4 fw-bold">{{ $totalStudents }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-workspace"></i></div>
                <div>
                    <div class="text-muted small">Teachers</div>
                    <div class="fs-4 fw-bold">{{ $totalTeachers }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="text-muted small">Pending Fees</div>
                    <div class="fs-4 fw-bold">{{ $pendingFees }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Today Attendance</div>
                    <div class="fs-4 fw-bold">{{ $todayAttendance }}/{{ $totalPresent ?: $totalStudents }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Recent Notices</h6>
                <a href="{{ route('notices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentNotices->count())
                <div class="list-group list-group-flush">
                    @foreach($recentNotices as $notice)
                    <a href="{{ route('notices.show', $notice) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">{{ $notice->title }}</h6>
                                <small class="text-muted">{{ Str::limit(strip_tags($notice->content), 100) }}</small>
                            </div>
                            <small class="text-muted">{{ $notice->publish_date->format('M d') }}</small>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">No notices yet</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Quick Stats</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Pending Leaves</span>
                    <span class="badge bg-warning">{{ $pendingLeaves }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Parents Registered</span>
                    <span class="fw-semibold">{{ $totalParents }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Academic Year</span>
                    <span class="fw-semibold">{{ $academicYear?->name ?? 'Not Set' }}</span>
                </div>
            </div>
        </div>

        <div class="card table-card mt-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Recent Payments</h6>
                @if(auth()->user()->hasPermission('fees.payments.manage'))
                    <a href="{{ route('fees.payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($recentPayments->count())
                <div class="list-group list-group-flush">
                    @foreach($recentPayments as $payment)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <small class="fw-semibold">{{ $payment->student->full_name ?? 'N/A' }}</small>
                            <small class="text-success fw-bold">₹{{ number_format($payment->amount_paid) }}</small>
                        </div>
                        <small class="text-muted">{{ $payment->payment_date->format('M d, Y') }}</small>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">No payments yet</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
