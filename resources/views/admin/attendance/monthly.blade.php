<x-admin>
    <x-slot:title>
        Monthly Attendance Report | Team Tasker
    </x-slot:title>
    @php
    $rgba = fn (string $hex, $alpha) => sprintf(
    'rgba(%d,%d,%d,%s)',
    hexdec(substr($hex, 1, 2)),
    hexdec(substr($hex, 3, 2)),
    hexdec(substr($hex, 5, 2)),
    $alpha
    );
    // Same color identity as the Daily Reports page.
    $tone = [
    'total'    => '#0ea5e9',
    'present'  => '#0d9488',
    'half_day' => '#7c3aed',
    'absent'   => '#db2777',
    'pending'  => '#d97706',
    'rejected' => '#dc2626',
    'approved' => '#16a34a',
    'hours'    => '#4f46e5',
    'none'     => '#94a3b8',
    ];
    // Attendance % color: good / average / low.
    $rateColor = fn ($pct) => $pct >= 85 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');
    // Calendar badge letter => color.
    $dayTone = [
    'P' => $tone['present'],
    'H' => $tone['half_day'],
    'A' => $tone['absent'],
    ];
    $statCards = [
    [
    'label'  => 'Total Employees',
    'key'    => 'total_users',
    'status' => null,
    'link'   => true,
    'tone'   => $tone['total'],
    'icon'   => 'fa-users',
    ],
    [
    'label'  => 'Total Present',
    'key'    => 'present',
    'status' => 'present',
    'link'   => true,
    'tone'   => $tone['present'],
    'icon'   => 'fa-user-check',
    ],
    [
    'label'  => 'Half Days',
    'key'    => 'half_days',
    'status' => 'half_day',
    'link'   => true,
    'tone'   => $tone['half_day'],
    'icon'   => 'fa-user-clock',
    ],
    [
    'label'  => 'Absences',
    'key'    => 'absent',
    'status' => 'absent',
    'link'   => true,
    'tone'   => $tone['absent'],
    'icon'   => 'fa-user-times',
    ],
    [
    'label'  => 'Pending Approvals',
    'key'    => 'pending',
    'status' => 'pending',
    'link'   => true,
    'tone'   => $tone['pending'],
    'icon'   => 'fa-clock',
    ],
    [
    'label'  => 'Approved Hours',
    'key'    => 'formatted_hours',
    'status' => null,
    'link'   => false,
    'tone'   => $tone['hours'],
    'icon'   => 'fa-hourglass-half',
    ],
    ];
    // Selected employee metric cards: [label, stats key, tone key].
    $metrics = [
    ['Present', 'present_days', 'present'],
    ['Half Day', 'half_days', 'half_day'],
    ['Absent', 'absent_days', 'absent'],
    ['Pending', 'pending_days', 'pending'],
    ['Rejected', 'rejected_days', 'rejected'],
    ['Approved', 'approved_days', 'approved'],
    ['Total Hours', 'formatted_working_hours', 'hours'],
    ];
    // Team table columns: [heading, stats key, tone key].
    $columns = [
    ['PRESENT', 'present_days', 'present'],
    ['HALF DAY', 'half_days', 'half_day'],
    ['ABSENT', 'absent_days', 'absent'],
    ['PENDING', 'pending_days', 'pending'],
    ['REJECTED', 'rejected_days', 'rejected'],
    ['APPROVED', 'approved_days', 'approved'],
    ];
    @endphp
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Monthly Attendance Report</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Aggregated monthly summary, working hours, attendance rates, and employee drilldowns.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a
            href="{{ route('admin.attendance.daily') }}"
            class="btn-premium btn-premium-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2"
            >
            <i class="fas fa-calendar-day me-1"></i> Daily Reports
        </a>
        <a
        href="{{ route('admin.attendance.reports', array_merge(request()->except('page'), ['export' => 'monthly', 'month' => $month])) }}"
        class="btn-premium btn-premium-secondary px-4 py-2 shadow-sm d-flex align-items-center gap-2"
        >
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
</div>
</div>
<style>
    .stat-filter-card {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        text-decoration: none !important;
        border-top: 3px solid var(--st) !important;
    }
    .stat-filter-card.no-link {
        cursor: default;
    }
    .stat-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
        border-color: var(--st) !important;
    }
    .stat-filter-card.active-filter {
        border-color: var(--st) !important;
        background: var(--st-bg) !important;
        box-shadow: 0 0 0 1px var(--st), 0 4px 14px var(--st-bg) !important;
    }
    .stat-filter-card .stat-value {
        color: var(--st);
    }
    .stat-filter-card .stat-icon {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--st-bg);
        color: var(--st);
        font-size: 0.75rem;
    }
    .metric-card {
        padding: 0.5rem;
        border-radius: 8px;
        background: var(--st-bg);
        border: 1px solid var(--st-bd);
        border-top: 3px solid var(--st);
    }
    .metric-card small {
        display: block;
        color: var(--st);
        font-weight: 600;
    }
    .metric-card h5 {
        margin: 0;
        font-weight: 700;
        color: var(--st);
    }
    .cal-day {
        min-width: 48px;
        flex: 1 0 45px;
        padding: 0.5rem;
        border-radius: 8px;
        text-align: center;
        background: var(--st-bg);
        border: 1px solid var(--st-bd);
        border-top: 3px solid var(--st);
    }
    .cal-day.is-weekend {
        opacity: 0.6;
    }
    .cal-day .cal-dow {
        font-size: 0.65rem;
        opacity: 0.75;
    }
    .cal-day .cal-num {
        font-weight: 700;
        font-size: 0.85rem;
    }
    .cal-badge {
        display: block;
        margin-top: 4px;
        padding: 3px 0;
        border-radius: 4px;
        background: var(--st);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .legend-badge {
        display: inline-block;
        margin-right: 4px;
        padding: 2px 7px;
        border-radius: 4px;
        background: var(--st);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .head-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin-right: 6px;
        border-radius: 50%;
        background: var(--st);
    }
    .num-chip {
        display: inline-block;
        min-width: 34px;
        padding: 2px 10px;
        border-radius: 999px;
        background: var(--st-bg);
        color: var(--st);
        font-weight: 700;
    }
    .num-chip.is-zero {
        background: transparent;
        color: inherit;
        font-weight: 500;
        opacity: 0.4;
    }
    .pct-bar {
        flex-grow: 1;
        height: 6px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(128, 128, 128, 0.2);
    }
    .pct-bar span {
        display: block;
        height: 100%;
        border-radius: 999px;
    }
    .data-grid-table tbody tr.row-selected > td:first-child {
        box-shadow: inset 3px 0 0 var(--primary);
    }
    .data-grid-table .action-link.act-cal {
        color: #0ea5e9;
    }
    .data-grid-table .action-link.act-cal:hover {
        background: rgba(14, 165, 233, 0.14);
    }
</style>
{{-- Monthly statistics overview cards (interactive filters) --}}
@if (isset($teamStats))
<div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
    @foreach ($statCards as $card)
    @php
    $isActive = $card['status'] === null
    ? !request('status')
    : request('status') === $card['status'];
    $params = array_merge(request()->except(['status', 'page']), ['month' => $month]);
    if ($card['status']) {
        $params['status'] = $card['status'];
    }
    $cardStyle = '--st: ' . $card['tone'] . '; --st-bg: ' . $rgba($card['tone'], 0.10) . ';';
    @endphp
    <div class="col">
        @if ($card['link'])
        <a
        href="{{ route('admin.attendance.monthly', $params) }}"
        class="d-block h-100 text-decoration-none"
        title="Filter: {{ $card['label'] }}"
        >
        @endif
        <div
        class="glass-card p-3 h-100 border-main stat-filter-card {{ $card['link'] ? '' : 'no-link' }} {{ $card['link'] && $isActive ? 'active-filter' : '' }}"
        style="{{ $cardStyle }}"
        >
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-low d-block" style="font-size: 0.75rem;">{{ $card['label'] }}</span>
            <span class="stat-icon"><i class="fas {{ $card['icon'] }}"></i></span>
        </div>
        <h4 class="fw-bold mb-0 stat-value">{{ $teamStats[$card['key']] ?? 0 }}</h4>
    </div>
    @if ($card['link'])
</a>
@endif
</div>
@endforeach
</div>
@endif
@if ($selectedUser && $selectedUserStats)
{{-- Selected employee detail drilldown --}}
<div class="glass-card p-4 mb-4 border-main border-primary border-opacity-50">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-premium" style="width: 48px; height: 48px; font-size: 1.2rem;">
                @if ($selectedUser->profile_image)
                <img src="{{ asset('storage/' . $selectedUser->profile_image) }}" alt="{{ $selectedUser->name }}">
                @else
                <div
                class="d-flex align-items-center justify-content-center w-100 h-100 text-white"
                style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600;"
                >
                {{ strtoupper(mb_substr($selectedUser->name ?? 'U', 0, 1)) }}
            </div>
            @endif
        </div>
        <div>
            <h4 class="fw-bold mb-0 text-high">{{ $selectedUser->name }}</h4>
            <div class="text-low small">
                {{ $selectedUser->email }} &bull; Showing Month:
                <strong>{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</strong>
            </div>
        </div>
    </div>
    <a
    href="{{ route('admin.attendance.monthly', array_merge(request()->except(['user_id', 'page']), ['month' => $month])) }}"
    class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1"
    >
    <i class="fas fa-times me-1"></i> Close Details
</a>
</div>
{{-- Employee metric cards --}}
<div class="row row-cols-2 row-cols-md-4 row-cols-xl-8 g-2 mb-4 text-center">
    @foreach ($metrics as [$mLabel, $mKey, $mTone])
    <div class="col">
        <div
        class="metric-card"
        style="--st: {{ $tone[$mTone] }}; --st-bg: {{ $rgba($tone[$mTone], 0.10) }}; --st-bd: {{ $rgba($tone[$mTone], 0.35) }};"
        >
        <small>{{ $mLabel }}</small>
        <h5>{{ $selectedUserStats[$mKey] }}</h5>
    </div>
</div>
@endforeach
@php
$selPct = $selectedUserStats['attendance_percentage'];
$selColor = $rateColor($selPct);
@endphp
<div class="col">
    <div
    class="metric-card"
    style="--st: {{ $selColor }}; --st-bg: {{ $rgba($selColor, 0.10) }}; --st-bd: {{ $rgba($selColor, 0.35) }};"
    >
    <small>Attendance %</small>
    <h5>{{ $selPct }}%</h5>
</div>
</div>
</div>
{{-- Monthly calendar matrix --}}
<h6 class="fw-bold text-high mb-2">
    Monthly Attendance Calendar ({{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }})
</h6>
<div class="d-flex flex-wrap gap-2 mb-2">
    @foreach ($selectedUserCalendar as $cDay)
    @php
    $dc = $dayTone[$cDay['badge']] ?? $tone['none'];
    @endphp
    <div
    class="cal-day {{ $cDay['is_weekend'] ? 'is-weekend' : '' }}"
    style="--st: {{ $dc }}; --st-bg: {{ $rgba($dc, 0.10) }}; --st-bd: {{ $rgba($dc, 0.35) }};"
    title="{{ $cDay['date'] }}: {{ $cDay['label'] }}"
    >
    <div class="cal-dow">{{ $cDay['day_name'] }}</div>
    <div class="cal-num">{{ $cDay['day'] }}</div>
    <span class="cal-badge">{{ $cDay['badge'] }}</span>
</div>
@endforeach
</div>
<div class="d-flex align-items-center gap-3 text-low small mt-2">
    <span><span class="legend-badge" style="--st: {{ $tone['present'] }};">P</span> Present</span>
    <span><span class="legend-badge" style="--st: {{ $tone['half_day'] }};">H</span> Half Day</span>
    <span><span class="legend-badge" style="--st: {{ $tone['absent'] }};">A</span> Absent</span>
    <span><span class="legend-badge" style="--st: {{ $tone['none'] }};">-</span> No Record</span>
</div>
</div>
@endif
{{-- Team summary table --}}
<div class="data-grid-wrapper mb-5">
    <div class="data-grid-top">
        <div class="data-grid-search" style="min-width: 200px;">
            <i
            class="fas fa-search"
            onclick="document.getElementById('monthlyFilterForm').submit()"
            style="cursor: pointer;"
            ></i>
            <input
            type="text"
            name="search"
            form="monthlyFilterForm"
            placeholder="Search employee..."
            value="{{ request('search') }}"
            onkeydown="if (event.key === 'Enter') { event.preventDefault(); document.getElementById('monthlyFilterForm').submit(); }"
            >
            @if (request('search'))
            <a
            href="{{ route('admin.attendance.monthly', array_merge(request()->except(['search', 'page']))) }}"
            class="text-low ms-2 text-decoration-none small"
            title="Clear search"
            >
            <i class="fas fa-times-circle"></i>
        </a>
        @endif
    </div>
    {{-- Hidden form for search submission --}}
    <form id="monthlyFilterForm" action="{{ route('admin.attendance.monthly') }}" method="GET" class="d-none">
        @if (request('user_id'))
        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        @endif
        @if (request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        @if (request('month'))
        <input type="hidden" name="month" value="{{ request('month') }}">
        @endif
    </form>
    <div class="data-grid-results">{{ $users->total() }} Employees</div>
    <div class="data-grid-actions d-flex align-items-center gap-2">
        @if (request('search') || request('user_id') || request('status') || ($month !== date('Y-m') && request()->has('month')))
        <a
        href="{{ route('admin.attendance.monthly') }}"
        class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1 text-decoration-none"
        title="Reset all filters"
        >
        <i class="fas fa-redo"></i> Reset
    </a>
    @endif
    {{ $users->links('components.pagination.premium') }}
</div>
</div>
<div class="table-responsive">
    <table class="table data-grid-table align-middle mb-0">
        <thead>
            <tr>
                <th>EMPLOYEE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                @foreach ($columns as [$cHead, $cKey, $cTone])
                <th class="text-center">
                    <span class="head-dot" style="--st: {{ $tone[$cTone] }};"></span>{{ $cHead }}
                </th>
                @endforeach
                <th class="text-center">TOTAL WORKING HOURS</th>
                <th class="text-center">ATTENDANCE %</th>
                <th class="text-end pe-4">ACTION</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
            @php
            $stats = $u->monthly_stats ?? null;
            $isSelected = $selectedUser && $selectedUser->id === $u->id;
            $pct = $stats['attendance_percentage'] ?? 0;
            $pctColor = $rateColor($pct);
            @endphp
            <tr class="{{ $isSelected ? 'table-primary bg-opacity-25 row-selected' : '' }}">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-premium" style="width: 32px; height: 32px;">
                            @if ($u->profile_image)
                            <img src="{{ asset('storage/' . $u->profile_image) }}" alt="{{ $u->name }}">
                            @else
                            <div
                            class="d-flex align-items-center justify-content-center w-100 h-100 text-white"
                            style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;"
                            >
                            {{ strtoupper(mb_substr($u->name ?? 'U', 0, 1)) }}
                        </div>
                        @endif
                    </div>
                    <div>
                        <div class="fw-bold text-high">{{ $u->name }}</div>
                        <div class="text-low extra-small">{{ $u->email }}</div>
                    </div>
                </div>
            </td>
            @foreach ($columns as [$cHead, $cKey, $cTone])
            @php
            $val = $stats[$cKey] ?? 0;
            @endphp
            <td class="text-center">
                <span
                class="num-chip {{ (float) $val == 0 ? 'is-zero' : '' }}"
                style="--st: {{ $tone[$cTone] }}; --st-bg: {{ $rgba($tone[$cTone], 0.13) }};"
                >{{ $val }}</span>
            </td>
            @endforeach
            <td class="text-center text-high fw-medium">
                <span
                class="badge-premium"
                style="background: {{ $rgba($tone['hours'], 0.12) }}; color: {{ $tone['hours'] }}; border: 1px solid {{ $rgba($tone['hours'], 0.35) }};"
                >
                {{ $stats['formatted_working_hours'] ?? '0h 0m' }}
            </span>
        </td>
        <td class="text-center">
            <div class="d-inline-flex align-items-center gap-2" style="min-width: 100px;">
                <span class="fw-bold small" style="color: {{ $pctColor }};">{{ $pct }}%</span>
                <div class="pct-bar">
                    <span style="width: {{ $pct }}%; background: {{ $pctColor }};"></span>
                </div>
            </div>
        </td>
        <td class="text-end pe-4">
            <div class="d-flex justify-content-end gap-2">
                <a
                href="{{ route('admin.attendance.monthly', array_merge(request()->except('page'), ['month' => $month, 'user_id' => $u->id])) }}"
                class="action-link act-cal border-0 bg-transparent"
                title="View Details & Calendar"
                >
                <i class="fas fa-calendar-alt"></i>
            </a>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="10" class="text-center py-5">
        <div class="text-low">
            <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
            <p class="mb-0">No employee monthly records found for this month.</p>
        </div>
    </td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
{{-- Advanced filter slideover for monthly attendance --}}
<div class="filter-slideover" id="filterSlideoverMonthlyAdmin">
    <form action="{{ route('admin.attendance.monthly') }}" method="GET" class="h-100 d-flex flex-column">
        @if (request('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
        <div class="filter-slideover-header">
            <h4><i class="fas fa-sliders-h text-low me-2"></i> Filter Monthly Overview</h4>
            <div
            class="filter-slideover-close"
            onclick="document.getElementById('filterSlideoverMonthlyAdmin').classList.remove('show')"
            >
            <i class="fas fa-times"></i>
        </div>
    </div>
    <div class="filter-slideover-body">
        <div class="mb-4">
            <label class="heading-label d-block mb-2 text-low">EMPLOYEE</label>
            <x-select name="user_id" placeholder="All Employees" :selected="request('user_id')">
                @foreach ($allUsers as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mb-4">
            <label class="heading-label d-block mb-2 text-low">ATTENDANCE STATUS</label>
            <x-select name="status" placeholder="All Records" :selected="request('status')">
                <option value="pending">Pending Approvals</option>
                <option value="absent">Has Absences</option>
                <option value="half_day">Has Half Days</option>
                <option value="present">Has Present Days</option>
            </x-select>
        </div>
        <div class="mb-4">
            <label class="heading-label d-block mb-2 text-low">MONTH</label>
            <input
            type="month"
            name="month"
            value="{{ $month }}"
            class="form-premium-control bg-white text-dark border-main w-100"
            >
            <div class="d-flex align-items-center gap-2 mt-2">
                <a
                href="{{ route('admin.attendance.monthly', array_merge(request()->except(['month', 'page']), ['month' => date('Y-m')])) }}"
                class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month == date('Y-m') ? 'active' : '' }}"
                >
                This Month
            </a>
            <a
            href="{{ route('admin.attendance.monthly', array_merge(request()->except(['month', 'page']), ['month' => date('Y-m', strtotime('-1 month'))])) }}"
            class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month == date('Y-m', strtotime('-1 month')) ? 'active' : '' }}"
            >
            Previous Month
        </a>
    </div>
</div>
</div>
<div class="filter-slideover-footer d-flex gap-2">
    <a
    href="{{ route('admin.attendance.monthly') }}"
    class="btn-premium btn-premium-secondary flex-fill text-center text-decoration-none bg-white text-dark border-main"
    >
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
        const slideover = document.getElementById('filterSlideoverMonthlyAdmin');
        if (slideover) {
            slideover.classList.add('show');
        }
    });
</script>
@endpush
</x-admin>