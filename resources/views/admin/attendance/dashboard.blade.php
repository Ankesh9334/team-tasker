<x-admin>
    <x-slot:title>
        Attendance Dashboard | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Attendance Dashboard</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Company-wide attendance metrics, employee shifts, and live daily status.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(!$myAttendance || !$myAttendance->check_in)
                <button type="button" class="btn-premium btn-premium-primary px-3 py-2 shadow-sm d-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#globalClockInModal">
                    <i class="fas fa-sign-in-alt"></i> Clock In
                </button>
            @elseif(!$myAttendance->check_out)
                <button type="button" class="btn btn-danger px-3 py-2 shadow-sm d-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#globalClockOutModal">
                    <i class="fas fa-sign-out-alt"></i> Clock Out
                </button>
            @else
                <button type="button" class="btn-premium btn-premium-secondary px-3 py-2 shadow-sm d-flex align-items-center gap-2" disabled
                    title="You have already completed your shift today">
                    <i class="fas fa-check text-success"></i> Shift Done
                </button>
            @endif

            <a href="{{ route('admin.attendance.daily') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-file-invoice"></i> Daily Reports
            </a>
            <a href="{{ route('admin.attendance.monthly') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-calendar-days"></i> Monthly
            </a>
            <a href="{{ route('admin.attendance.calendar') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
            <a href="{{ route('admin.attendance.requests') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-envelope-open-text"></i> Leave Requests
            </a>
        </div>
    </div>

    <style>
        .stat-filter-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none !important;
        }
        .stat-filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
            border-color: rgba(var(--primary-rgb, 14, 165, 233), 0.5) !important;
        }
        .stat-filter-card.active-filter {
            border-color: var(--primary) !important;
            background: rgba(var(--primary-rgb, 14, 165, 233), 0.08) !important;
            box-shadow: 0 0 0 1px var(--primary), 0 4px 14px rgba(var(--primary-rgb, 14, 165, 233), 0.15) !important;
        }
    </style>

    <!-- Daily Statistics Overview Cards (Interactive Filters) -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        <!-- 1. Total Users -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: All Employees">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ !request('approval_status') ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Total Employees</span>
                        @if(!request('approval_status'))
                            <i class="fas fa-check-circle text-primary" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-high">{{ $dailyStats['total_users'] }}</h4>
                </div>
            </a>
        </div>

        <!-- 2. Submitted -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => request('approval_status') === 'submitted' ? null : 'submitted'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Submitted Reports">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'submitted' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Submitted</span>
                        @if(request('approval_status') === 'submitted')
                            <i class="fas fa-check-circle text-primary" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-primary">{{ $dailyStats['submitted'] }}</h4>
                </div>
            </a>
        </div>

        <!-- 3. Pending Approval -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => request('approval_status') === 'pending' ? null : 'pending'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Pending Approval">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'pending' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Pending Approval</span>
                        @if(request('approval_status') === 'pending')
                            <i class="fas fa-check-circle text-warning" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-warning">{{ $dailyStats['pending'] }}</h4>
                </div>
            </a>
        </div>

        <!-- 4. Approved -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => request('approval_status') === 'approved' ? null : 'approved'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Approved">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'approved' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Approved</span>
                        @if(request('approval_status') === 'approved')
                            <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-success">{{ $dailyStats['approved'] }}</h4>
                </div>
            </a>
        </div>

        <!-- 5. Rejected -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => request('approval_status') === 'rejected' ? null : 'rejected'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Rejected">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'rejected' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Rejected</span>
                        @if(request('approval_status') === 'rejected')
                            <i class="fas fa-check-circle text-danger" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-danger">{{ $dailyStats['rejected'] }}</h4>
                </div>
            </a>
        </div>

        <!-- 6. Not Submitted -->
        <div class="col">
            <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => request('approval_status') === 'not_submitted' ? null : 'not_submitted'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Not Submitted">
                <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'not_submitted' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Not Submitted</span>
                        @if(request('approval_status') === 'not_submitted')
                            <i class="fas fa-check-circle text-secondary" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 text-low">{{ $dailyStats['not_submitted'] }}</h4>
                </div>
            </a>
        </div>
    </div>

    @if($myAttendance && $myAttendance->check_in)
        <!-- Personal Shift Status Snapshot -->
        <div class="glass-card p-3 mb-4 border-main">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-premium bg-primary-subtle text-primary fw-bold" style="width: 40px; height: 40px;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <span class="text-low extra-small d-block text-uppercase fw-semibold">Your Today's Shift Status</span>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span class="text-high small">
                                In: <strong>{{ \Carbon\Carbon::parse($myAttendance->check_in)->format('h:i A') }}</strong>
                            </span>
                            <span class="text-high small">
                                Out: <strong>{{ $myAttendance->check_out ? \Carbon\Carbon::parse($myAttendance->check_out)->format('h:i A') : '--:--' }}</strong>
                            </span>
                            <span class="text-high small">
                                Hours: <strong class="text-primary">{{ $myAttendance->formatted_working_hours }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-premium bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">
                        {{ ucfirst(str_replace('_', ' ', $myAttendance->attendance_status)) }}
                    </span>
                    <span class="badge-premium bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1">
                        {{ ucfirst($myAttendance->approval_status ?? 'Approved') }}
                    </span>
                    <a href="{{ route('admin.attendance.myDaily') }}" class="btn-premium btn-premium-secondary btn-sm ms-2">
                        View Details
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Standard Project Attendance Data Grid -->
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" form="dashFilterForm" value="{{ request('search') }}" 
                       placeholder="Search employee name or email..." 
                       onchange="document.getElementById('dashFilterForm').submit()">
                @if(request('search'))
                    <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['search', 'page']))) }}" 
                       class="text-low ms-2 text-decoration-none small" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            <form id="dashFilterForm" action="{{ route('admin.attendance.dashboard') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap m-0">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div style="min-width: 170px;">
                    <x-select name="approval_status" placeholder="All Reports" :selected="request('approval_status')" onchange="document.getElementById('dashFilterForm').submit()">
                        <option value="">All Reports</option>
                        <option value="submitted" {{ request('approval_status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="not_submitted" {{ request('approval_status') === 'not_submitted' ? 'selected' : '' }}>Not Submitted</option>
                    </x-select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" class="form-premium-control form-control-sm" style="width: 155px; padding: 6px 10px;" onchange="document.getElementById('dashFilterForm').submit()">
                    
                    @php
                        $todayStr = now()->format('Y-m-d');
                        $yesterdayStr = now()->subDay()->format('Y-m-d');
                    @endphp
                    <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['date', 'page']), ['date' => $todayStr])) }}" 
                       class="btn-premium btn-premium-secondary btn-sm {{ $date === $todayStr ? 'active' : '' }}" 
                       title="View Today">
                        Today
                    </a>
                    <a href="{{ route('admin.attendance.dashboard', array_merge(request()->except(['date', 'page']), ['date' => $yesterdayStr])) }}" 
                       class="btn-premium btn-premium-secondary btn-sm {{ $date === $yesterdayStr ? 'active' : '' }}" 
                       title="View Yesterday">
                        Yesterday
                    </a>
                </div>

                @if(request('search') || request('approval_status') || ($date !== now()->format('Y-m-d') && request()->has('date')))
                    <a href="{{ route('admin.attendance.dashboard') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1" title="Reset all filters">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                @endif
            </form>

            <div class="data-grid-results">{{ $users->total() }} Employees</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                {{ $users->links('components.pagination.premium') }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>EMPLOYEE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>CHECK IN</th>
                        <th>CHECK OUT</th>
                        <th>WORKING HOURS</th>
                        <th class="text-center">ATTENDANCE STATUS</th>
                        <th class="text-center">APPROVAL STATUS</th>
                        <th>TASKS / TIMERS</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        @php
                            $att = $attendances->get($u->id);
                            $timeLog = $timeLogCounts->get($u->id);
                            
                            $attStatus = $att ? $att->attendance_status : 'absent';
                            $statusColor = match($attStatus) {
                                'present' => 'success',
                                'half_day' => 'warning',
                                'absent' => 'danger',
                                default => 'secondary',
                            };

                            $appStatus = $att ? ($att->approval_status ?? 'draft') : 'not_submitted';
                            $appBadge = match($appStatus) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                'submitted' => 'primary',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-premium" style="width: 34px; height: 34px;">
                                        @if ($u->profile_image)
                                            <img src="{{ asset('storage/' . $u->profile_image) }}" alt="{{ $u->name }}">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                                {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-high">{{ $u->name }}</div>
                                        <div class="text-low extra-small">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-high">
                                {{ $att && $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-high">
                                {{ $att && $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-low">
                                <span class="badge-premium bg-subtle text-high border-subtle">
                                    {{ $att ? $att->formatted_working_hours : '0h 0m' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $attStatus)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $appBadge }}-subtle text-{{ $appBadge }} border border-{{ $appBadge }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $appStatus)) }}
                                </span>
                            </td>
                            <td class="text-low small">
                                @if($timeLog && $timeLog->count > 0)
                                    <span class="text-high fw-semibold">{{ $timeLog->count }} tasks</span>
                                    <span class="text-low">({{ gmdate('H\h i\m', $timeLog->total_duration) }})</span>
                                @else
                                    <span class="text-low opacity-50">No timer tasks</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('admin.attendance.daily', ['date' => $date, 'user_id' => $u->id]) }}" 
                                       class="action-link border-0 bg-transparent text-decoration-none" 
                                       title="View Daily Report">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.attendance.monthly', ['user_id' => $u->id]) }}" 
                                       class="action-link border-0 bg-transparent text-decoration-none" 
                                       title="View Monthly Record">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-low">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-semibold text-high mb-1">No employees found</h6>
                                    <p class="small text-low mb-0">No attendance records matched your filter criteria for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        @if (Auth::user()->hasPermission('attendance.dashboard_overview') || Auth::user()->hasRole('super-admin'))
            <div class="col-lg-7">
                <div class="glass-card p-4 border-main h-100">
                    <h5 class="text-high fw-semibold mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-chart-column text-primary"></i> Team Attendance Trend (Last 7 Days)
                    </h5>
                    <canvas id="attendanceTrendChart" height="110"></canvas>
                </div>
            </div>
        @endif

        <div class="col-lg-{{ (Auth::user()->hasPermission('attendance.dashboard_overview') || Auth::user()->hasRole('super-admin')) ? '5' : '12' }}">
            <div class="glass-card p-4 border-main h-100">
                <h5 class="text-high fw-semibold mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-info"></i> Your Work Hours (Last 7 Days)
                </h5>
                <canvas id="personalTrendChart" height="110"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Render Personal Chart
                if (document.getElementById('personalTrendChart')) {
                    const ctx = document.getElementById('personalTrendChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($chartLabels) !!},
                            datasets: [{
                                label: 'Work Hours',
                                data: {!! json_encode($personalWorkHours) !!},
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { color: document.documentElement.getAttribute('data-theme') === 'light' ? '#6b7280' : '#9ca3af' },
                                    grid: { color: document.documentElement.getAttribute('data-theme') === 'light' ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)' }
                                },
                                x: {
                                    ticks: { color: document.documentElement.getAttribute('data-theme') === 'light' ? '#6b7280' : '#9ca3af' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                // Render Team Chart (Admin Only)
                if (document.getElementById('attendanceTrendChart')) {
                    const ctxTeam = document.getElementById('attendanceTrendChart').getContext('2d');
                    new Chart(ctxTeam, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($chartLabels) !!},
                            datasets: [
                                {
                                    label: 'Present & Half-Day',
                                    data: {!! json_encode($chartPresent) !!},
                                    backgroundColor: '#10b981',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Late',
                                    data: {!! json_encode($chartLate) !!},
                                    backgroundColor: '#f59e0b',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Leave',
                                    data: {!! json_encode($chartLeave) !!},
                                    backgroundColor: '#0ea5e9',
                                    borderRadius: 4
                                },
                                {
                                    label: 'Absent',
                                    data: {!! json_encode($chartAbsent) !!},
                                    backgroundColor: '#ef4444',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { 
                                    position: 'top', 
                                    labels: { color: document.documentElement.getAttribute('data-theme') === 'light' ? '#4b5563' : '#e5e7eb' } 
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    stacked: true,
                                    ticks: { precision: 0, color: document.documentElement.getAttribute('data-theme') === 'light' ? '#6b7280' : '#9ca3af' },
                                    grid: { color: document.documentElement.getAttribute('data-theme') === 'light' ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)' }
                                },
                                x: {
                                    stacked: true,
                                    ticks: { color: document.documentElement.getAttribute('data-theme') === 'light' ? '#6b7280' : '#9ca3af' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</x-admin>