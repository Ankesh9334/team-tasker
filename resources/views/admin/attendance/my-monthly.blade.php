<x-admin>
    <x-slot:title>
        My Monthly Attendance | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">My Monthly Attendance</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Review your attendance history, monthly working hours, and calendar breakdown.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.attendance.myDaily') }}" class="btn-premium btn-premium-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-clock"></i> Today's Attendance
            </a>
            <a href="{{ route('admin.attendance.calendar') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-calendar-alt"></i> Calendar View
            </a>
            <a href="{{ route('admin.attendance.reports', ['export' => 'monthly', 'month' => $month, 'user_id' => $user->id]) }}" 
               class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-file-csv"></i> Export CSV
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

    <!-- Monthly Statistics Overview Cards (Interactive Filters) -->
    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-8 g-2 mb-4 text-center">
        <!-- 1. Total Present -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'present' ? null : 'present'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Present">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'present' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Present</span>
                        @if(request('status') === 'present')
                            <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-success mb-0">{{ $stats['present_days'] }}</h5>
                </div>
            </a>
        </div>

        <!-- 2. Half Day -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'half_day' ? null : 'half_day'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Half Day">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'half_day' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Half Day</span>
                        @if(request('status') === 'half_day')
                            <i class="fas fa-check-circle text-warning" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-warning mb-0">{{ $stats['half_days'] }}</h5>
                </div>
            </a>
        </div>

        <!-- 3. Absent -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'absent' ? null : 'absent'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Absent">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'absent' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Absent</span>
                        @if(request('status') === 'absent')
                            <i class="fas fa-check-circle text-danger" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-danger mb-0">{{ $stats['absent_days'] }}</h5>
                </div>
            </a>
        </div>

        <!-- 4. Pending Approval -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'pending' ? null : 'pending'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Pending Approval">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'pending' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Pending</span>
                        @if(request('status') === 'pending')
                            <i class="fas fa-check-circle text-warning" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-warning mb-0">{{ $stats['pending_days'] }}</h5>
                </div>
            </a>
        </div>

        <!-- 5. Rejected -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'rejected' ? null : 'rejected'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Rejected">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'rejected' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Rejected</span>
                        @if(request('status') === 'rejected')
                            <i class="fas fa-check-circle text-danger" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-danger mb-0">{{ $stats['rejected_days'] }}</h5>
                </div>
            </a>
        </div>

        <!-- 6. Approved -->
        <div class="col">
            <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => request('status') === 'approved' ? null : 'approved'])) }}" 
               class="d-block h-100 text-decoration-none" title="Filter: Approved">
                <div class="glass-card p-2 h-100 border-main stat-filter-card {{ request('status') === 'approved' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.7rem;">Approved</span>
                        @if(request('status') === 'approved')
                            <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h5 class="fw-bold text-success mb-0">{{ $stats['approved_days'] ?? 0 }}</h5>
                </div>
            </a>
        </div>

        <!-- 7. Total Working Hours -->
        <div class="col">
            <div class="glass-card p-2 h-100 border-main">
                <span class="text-low d-block mb-1" style="font-size: 0.7rem;">Total Hours</span>
                <h5 class="fw-bold text-primary mb-0">{{ $stats['formatted_working_hours'] }}</h5>
            </div>
        </div>

        <!-- 8. Attendance % -->
        <div class="col">
            <div class="glass-card p-2 h-100 border-main">
                <span class="text-low d-block mb-1" style="font-size: 0.7rem;">Attendance</span>
                <h5 class="fw-bold text-info mb-0">{{ $stats['attendance_percentage'] }}%</h5>
            </div>
        </div>
    </div>

    <!-- Monthly Calendar Matrix -->
    <div class="glass-card p-4 mb-4 border-main">
        <h5 class="fw-bold text-high mb-3 d-flex align-items-center gap-2">
            <i class="fas fa-calendar-alt text-primary"></i> {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }} Calendar Matrix
        </h5>

        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($calendar as $cDay)
                <div class="p-2 rounded text-center border {{ $cDay['is_weekend'] ? 'bg-subtle border-subtle' : 'border-main' }}" 
                     style="min-width: 48px; flex: 1 0 45px;"
                     title="{{ $cDay['date'] }}: {{ $cDay['label'] }}">
                    <div class="text-low" style="font-size: 0.65rem;">{{ $cDay['day_name'] }}</div>
                    <div class="fw-bold small">{{ $cDay['day'] }}</div>
                    <span class="badge bg-{{ $cDay['class'] }} d-block mt-1 py-1" style="font-size: 0.7rem;">
                        {{ $cDay['badge'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="d-flex align-items-center gap-3 text-low small flex-wrap">
            <span><span class="badge bg-success me-1">P</span> Present</span>
            <span><span class="badge bg-warning me-1">H</span> Half Day</span>
            <span><span class="badge bg-danger me-1">A</span> Absent</span>
            <span><span class="badge bg-secondary me-1">-</span> No Record</span>
        </div>
    </div>

    <!-- Daily Records Breakdown Data Grid -->
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" form="monthlyFilterForm" value="{{ request('search') }}" 
                       placeholder="Search remarks or date (e.g. 2026-09)..." 
                       onchange="document.getElementById('monthlyFilterForm').submit()">
                @if(request('search'))
                    <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['search', 'page']))) }}" 
                       class="text-low ms-2 text-decoration-none small" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            <form id="monthlyFilterForm" action="{{ route('admin.attendance.myMonthly') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap m-0">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div style="min-width: 160px;">
                    <x-select name="status" placeholder="All Records" :selected="request('status')" onchange="document.getElementById('monthlyFilterForm').submit()">
                        <option value="">All Records</option>
                        <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="half_day" {{ request('status') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                        <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </x-select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <input type="month" name="month" value="{{ $month }}" class="form-premium-control form-control-sm" style="width: 155px; padding: 6px 10px;" onchange="document.getElementById('monthlyFilterForm').submit()">
                    
                    @php
                        $currentMonthStr = now()->format('Y-m');
                        $prevMonthStr = now()->subMonth()->format('Y-m');
                    @endphp
                    <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['month', 'page']), ['month' => $currentMonthStr])) }}" 
                       class="btn-premium btn-premium-secondary btn-sm {{ $month === $currentMonthStr ? 'active' : '' }}" 
                       title="View Current Month">
                        This Month
                    </a>
                    <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['month', 'page']), ['month' => $prevMonthStr])) }}" 
                       class="btn-premium btn-premium-secondary btn-sm {{ $month === $prevMonthStr ? 'active' : '' }}" 
                       title="View Previous Month">
                        Previous Month
                    </a>
                </div>

                @if(request('search') || request('status') || ($month !== now()->format('Y-m') && request()->has('month')))
                    <a href="{{ route('admin.attendance.myMonthly') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1" title="Reset all filters">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                @endif
            </form>

            <div class="data-grid-results">{{ $records->total() }} Records</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                {{ $records->links('components.pagination.premium') }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>DATE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>CHECK IN</th>
                        <th>CHECK OUT</th>
                        <th>WORKING HOURS</th>
                        <th class="text-center">ATTENDANCE STATUS</th>
                        <th class="text-center">APPROVAL STATUS</th>
                        <th>REMARKS / NOTES</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $att)
                        @php
                            $attStatus = $att->attendance_status;
                            $statusColor = match($attStatus) {
                                'present' => 'success',
                                'half_day' => 'warning',
                                'absent' => 'danger',
                                default => 'secondary',
                            };
                            $appBadge = match($att->approval_status) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'info',
                            };
                            $dateFormatted = $att->attendance_date 
                                ? $att->attendance_date->format('d M Y (D)') 
                                : \Carbon\Carbon::parse($att->date)->format('d M Y (D)');
                            $dateParam = $att->attendance_date 
                                ? $att->attendance_date->format('Y-m-d') 
                                : \Carbon\Carbon::parse($att->date)->format('Y-m-d');
                        @endphp
                        <tr>
                            <td class="text-high fw-semibold">
                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                {{ $dateFormatted }}
                            </td>
                            <td class="text-high">
                                {{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-high">
                                {{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-low">
                                <span class="badge-premium bg-subtle text-high border-subtle">
                                    {{ $att->formatted_working_hours }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $att->attendance_status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $appBadge }}-subtle text-{{ $appBadge }} border border-{{ $appBadge }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst($att->approval_status ?? 'Approved') }}
                                </span>
                                @if($att->isRejected() && $att->rejection_reason)
                                    <div class="text-danger extra-small mt-1 text-truncate" style="max-width: 160px;" title="{{ $att->rejection_reason }}">
                                        {{ $att->rejection_reason }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-low small text-truncate" style="max-width: 220px;" title="{{ $att->remarks }}">
                                {{ $att->remarks ?: '-' }}
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.attendance.myDaily', ['date' => $dateParam]) }}" 
                                   class="action-link border-0 bg-transparent text-decoration-none" 
                                   title="View Daily Details & Timers">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-low">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-semibold text-high mb-1">No attendance records found</h6>
                                    <p class="small text-low mb-0">No records matched your search or status filter for {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin>
