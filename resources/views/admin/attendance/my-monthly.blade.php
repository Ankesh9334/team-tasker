<x-admin>
    <x-slot:title>
        My Monthly Attendance | Team Tasker
    </x-slot:title>

    <div class="monthly-attendance-page">

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

        /* Monthly Attendance — polished, responsive page styling */
        .monthly-attendance-page {
            --ma-border: var(--border-color, rgba(148, 163, 184, .22));
            --ma-surface: var(--card-bg, var(--bs-body-bg, #fff));
            --ma-muted: var(--text-muted, #64748b);
            color: var(--text-high, var(--bs-body-color, #1e293b));
        }

        .monthly-attendance-page .top-bar-premium {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
            padding: 1.4rem 1.5rem;
            margin-bottom: 1.35rem;
            border: 1px solid var(--ma-border);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(59,130,246,.075), rgba(139,92,246,.035));
            box-shadow: 0 8px 26px rgba(15, 23, 42, .045);
        }
        .monthly-attendance-page .top-bar-premium h1 {
            letter-spacing: -.035em;
            line-height: 1.2;
        }
        .monthly-attendance-page .top-bar-premium .d-flex {
            row-gap: .6rem !important;
        }
        .monthly-attendance-page .btn-premium {
            min-height: 40px;
            padding: .62rem .95rem;
            border-radius: 10px;
            font-size: .84rem;
            font-weight: 650;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .monthly-attendance-page .btn-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 16px rgba(15, 23, 42, .10);
        }

        .monthly-attendance-page .stat-filter-card,
        .monthly-attendance-page .glass-card {
            border-radius: 15px;
            border: 1px solid var(--ma-border);
            box-shadow: 0 5px 18px rgba(15, 23, 42, .035);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .monthly-attendance-page .stat-filter-card {
            min-height: 82px;
            padding: .9rem !important;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .monthly-attendance-page .stat-filter-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(15, 23, 42, .09) !important;
            border-color: rgba(59, 130, 246, .45) !important;
        }
        .monthly-attendance-page .stat-filter-card.active-filter {
            border-color: var(--primary, #3b82f6) !important;
            box-shadow: 0 0 0 2px rgba(59,130,246,.12), 0 8px 20px rgba(59,130,246,.08) !important;
        }
        .monthly-attendance-page .stat-filter-card span {
            font-size: .75rem !important;
            font-weight: 600;
            letter-spacing: .015em;
        }
        .monthly-attendance-page .stat-filter-card h5 {
            margin-top: .2rem;
            font-size: 1.35rem;
            line-height: 1.25;
            letter-spacing: -.025em;
        }

        .monthly-attendance-page .glass-card.p-4 {
            padding: 1.35rem !important;
        }
        .monthly-attendance-page .glass-card h5 {
            letter-spacing: -.02em;
        }
        .monthly-attendance-page .glass-card .rounded.text-center {
            min-height: 77px;
            border-radius: 12px !important;
            background-color: rgba(148, 163, 184, .045);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .monthly-attendance-page .glass-card .rounded.text-center:hover {
            transform: translateY(-2px);
            border-color: rgba(59,130,246,.4) !important;
            box-shadow: 0 5px 14px rgba(15,23,42,.06);
        }
        .monthly-attendance-page .glass-card .rounded.text-center .badge {
            border-radius: 6px;
            font-weight: 700;
        }

        .monthly-attendance-page .data-grid-wrapper {
            overflow: hidden;
            border: 1px solid var(--ma-border);
            border-radius: 16px;
            background: var(--ma-surface);
            box-shadow: 0 7px 24px rgba(15, 23, 42, .045);
        }
        .monthly-attendance-page .data-grid-top {
            display: flex;
            align-items: center;
            gap: .85rem;
            flex-wrap: wrap;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--ma-border);
            background: rgba(148,163,184,.035);
        }
        .monthly-attendance-page .data-grid-search {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex: 1 1 280px;
            min-width: 200px;
            padding: .15rem .8rem;
            border: 1px solid var(--ma-border);
            border-radius: 10px;
            background: var(--ma-surface);
        }
        .monthly-attendance-page .data-grid-search > i {
            color: var(--ma-muted);
            font-size: .85rem;
        }
        .monthly-attendance-page .data-grid-search input {
            width: 100%;
            min-width: 0;
            padding: .55rem 0;
            color: inherit;
            border: 0;
            outline: 0;
            background: transparent;
            font-size: .86rem;
        }
        .monthly-attendance-page .data-grid-search:focus-within {
            border-color: rgba(59,130,246,.7);
            box-shadow: 0 0 0 3px rgba(59,130,246,.10);
        }
        .monthly-attendance-page .data-grid-results {
            color: var(--ma-muted);
            white-space: nowrap;
            font-size: .82rem;
            font-weight: 650;
        }
        .monthly-attendance-page .data-grid-table {
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
        }
        .monthly-attendance-page .data-grid-table thead th {
            padding: .9rem 1rem;
            color: var(--ma-muted);
            background: rgba(148,163,184,.065);
            border-bottom: 1px solid var(--ma-border);
            font-size: .69rem;
            font-weight: 750;
            letter-spacing: .065em;
            white-space: nowrap;
        }
        .monthly-attendance-page .data-grid-table tbody td {
            padding: .95rem 1rem;
            border-bottom: 1px solid var(--ma-border);
            vertical-align: middle;
            font-size: .84rem;
        }
        .monthly-attendance-page .data-grid-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .monthly-attendance-page .data-grid-table tbody tr {
            transition: background-color .15s ease;
        }
        .monthly-attendance-page .data-grid-table tbody tr:hover {
            background: rgba(59,130,246,.035);
        }
        .monthly-attendance-page .badge-premium {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            font-size: .76rem;
            font-weight: 650;
            white-space: nowrap;
        }
        .monthly-attendance-page .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 9px;
            color: var(--primary, #2563eb);
            background: rgba(59,130,246,.08) !important;
            transition: background .18s ease, transform .18s ease;
        }
        .monthly-attendance-page .action-link:hover {
            background: rgba(59,130,246,.16) !important;
            transform: translateY(-1px);
        }

        .monthly-attendance-page .filter-slideover {
            backdrop-filter: blur(8px);
        }
        .monthly-attendance-page .filter-slideover-header,
        .monthly-attendance-page .filter-slideover-footer {
            padding: 1.1rem 1.25rem;
        }
        .monthly-attendance-page .filter-slideover-body {
            padding: 1.25rem;
        }
        .monthly-attendance-page .form-premium-control {
            min-height: 43px;
            border-radius: 10px;
            padding: .65rem .8rem;
        }

        @media (min-width: 1400px) {
            .monthly-attendance-page .row-cols-xl-8 > .col {
                flex: 0 0 auto;
                width: 12.5%;
            }
        }
        @media (max-width: 991.98px) {
            .monthly-attendance-page .top-bar-premium {
                padding: 1.15rem;
            }
            .monthly-attendance-page .data-grid-top {
                align-items: stretch;
            }
            .monthly-attendance-page .data-grid-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
        @media (max-width: 575.98px) {
            .monthly-attendance-page .top-bar-premium {
                align-items: flex-start;
                border-radius: 14px;
                padding: 1rem;
            }
            .monthly-attendance-page .top-bar-premium h1 {
                font-size: 1.35rem;
            }
            .monthly-attendance-page .top-bar-premium > .d-flex {
                width: 100%;
                align-items: stretch !important;
            }
            .monthly-attendance-page .top-bar-premium .btn-premium {
                flex: 1 1 100%;
                justify-content: center;
            }
            .monthly-attendance-page .stat-filter-card {
                min-height: 76px;
                padding: .75rem !important;
            }
            .monthly-attendance-page .stat-filter-card h5 {
                font-size: 1.2rem;
            }
            .monthly-attendance-page .glass-card.p-4 {
                padding: 1rem !important;
            }
            .monthly-attendance-page .data-grid-wrapper {
                border-radius: 12px;
            }
            .monthly-attendance-page .data-grid-top {
                padding: .8rem;
            }
            .monthly-attendance-page .data-grid-search {
                flex-basis: 100%;
            }
            .monthly-attendance-page .data-grid-actions {
                flex-wrap: wrap;
            }
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

     <div class="data-grid-results">{{ $records->total() }} Records</div>

     <div class="data-grid-actions d-flex align-items-center gap-2">
        @if(request('search') || request('status') || ($month !== now()->format('Y-m') && request()->has('month')))
        <a href="{{ route('admin.attendance.myMonthly') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1 text-decoration-none" title="Reset all filters">
            <i class="fas fa-redo"></i> Reset
        </a>
        @endif
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

<!-- Advanced Filter Slideover for Monthly Attendance -->
<div class="filter-slideover" id="filterSlideoverMonthly">
    <form action="{{ route('admin.attendance.myMonthly') }}" method="GET" class="h-100 d-flex flex-column">
        @if(request('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
        @endif

        <div class="filter-slideover-header">
            <h4><i class="fas fa-sliders-h text-low me-2"></i> Filter Monthly Records</h4>
            <div class="filter-slideover-close" onclick="document.getElementById('filterSlideoverMonthly').classList.remove('show')">
                <i class="fas fa-times"></i>
            </div>
        </div>

        <div class="filter-slideover-body">
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">ATTENDANCE STATUS</label>
                <x-select name="status" placeholder="All Records" :selected="request('status')">
                    <option value="present">Present</option>
                    <option value="half_day">Half Day</option>
                    <option value="absent">Absent</option>
                    <option value="pending">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </x-select>
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">MONTH</label>
                <input type="month" name="month" value="{{ $month }}" class="form-premium-control bg-white text-dark border-main w-100">
                <div class="d-flex align-items-center gap-2 mt-2">
                    @php
                    $currentMonthStr = now()->format('Y-m');
                    $prevMonthStr = now()->subMonth()->format('Y-m');
                    @endphp
                    <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['month', 'page']), ['month' => $currentMonthStr])) }}"
                     class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month === $currentMonthStr ? 'active' : '' }}">
                     This Month
                 </a>
                 <a href="{{ route('admin.attendance.myMonthly', array_merge(request()->except(['month', 'page']), ['month' => $prevMonthStr])) }}"
                     class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month === $prevMonthStr ? 'active' : '' }}">
                     Previous Month
                 </a>
             </div>
         </div>
     </div>

     <div class="filter-slideover-footer d-flex gap-2">
        <a href="{{ route('admin.attendance.myMonthly') }}" class="btn-premium btn-premium-secondary flex-fill text-center text-decoration-none bg-white text-dark border-main">
            Reset
        </a>
        <button type="submit" class="btn-premium btn-premium-primary flex-fill justify-content-center">
            Apply Filters
        </button>
    </div>
</form>
</div>

@push('scripts')
<script>
    document.addEventListener('openFilterModal', () => {
        const slideover = document.getElementById('filterSlideoverMonthly');
        if (slideover) slideover.classList.add('show');
    });
</script>
@endpush
</div>
</x-admin>
