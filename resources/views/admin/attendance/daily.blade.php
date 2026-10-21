<x-admin>
    <x-slot:title>
        Daily Work Reports & Attendance Approval | Team Tasker
    </x-slot:title>
    @php
    $fmtDuration = function ($seconds) {
        $seconds = (int) $seconds;
        return sprintf('%dh %02dm', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    };
    // "#16a34a" + 0.13 => rgba(22,163,74,0.13)
    $rgba = fn (string $hex, $alpha) => sprintf(
    'rgba(%d,%d,%d,%s)',
    hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)), $alpha
    );
    // One color identity per status, used everywhere on this page.
    $palette = [
    'approved'      => '#16a34a', // green
    'pending'       => '#d97706', // amber
    'rejected'      => '#dc2626', // red
    'draft'         => '#7c3aed', // violet
    'not_submitted' => '#64748b', // slate
    ];
    $icons = [
    'approved'      => 'fa-check-circle',
    'pending'       => 'fa-clock',
    'rejected'      => 'fa-times-circle',
    'draft'         => 'fa-file-alt',
    'not_submitted' => 'fa-minus-circle',
    ];
    $statusStyles = collect($palette)->map(fn ($c, $k) => [
    'color'  => $c,
    'bg'     => $rgba($c, 0.13),
    'border' => $rgba($c, 0.40),
    'icon'   => $icons[$k],
    ])->all();
    // Stat cards: each one has its own tone and icon.
    $statCards = [
    ['label' => 'Total Users',      'key' => 'total_users',   'filter' => null,            'tone' => '#0ea5e9',                  'icon' => 'fa-users'],
    ['label' => 'Submitted',        'key' => 'submitted',     'filter' => 'submitted',     'tone' => '#4f46e5',                  'icon' => 'fa-paper-plane'],
    ['label' => 'Pending Approval', 'key' => 'pending',       'filter' => 'pending',       'tone' => $palette['pending'],        'icon' => $icons['pending']],
    ['label' => 'Approved',         'key' => 'approved',      'filter' => 'approved',      'tone' => $palette['approved'],       'icon' => $icons['approved']],
    ['label' => 'Rejected',         'key' => 'rejected',      'filter' => 'rejected',      'tone' => $palette['rejected'],       'icon' => $icons['rejected']],
    ['label' => 'Not Submitted',    'key' => 'not_submitted', 'filter' => 'not_submitted', 'tone' => $palette['not_submitted'],  'icon' => $icons['not_submitted']],
    ];
    $todayStr = date('Y-m-d');
    $isSuperAdmin = $isSuperAdmin ?? false;
    $approvalLabels = ['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    @endphp
    {{-- ───────────── Top Bar ───────────── --}}
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Daily Work Reports</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Review daily employee work submissions, inspect detailed task activities, and manage report approvals.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn-premium btn-premium-secondary px-4 py-2 shadow-sm" onclick="openDailyFilters()">
                <i class="fas fa-sliders-h me-1"></i> Advanced Filters
            </button>
            <button type="button" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm"
            data-bs-toggle="modal" data-bs-target="#globalSubmitReportModal">
            <i class="fas fa-plus-circle me-1"></i> Submit Report
        </button>
        <a href="{{ route('admin.attendance.reports', array_merge(request()->query(), ['export' => 'daily', 'date' => $date])) }}"
         class="btn-premium btn-premium-secondary px-4 py-2 shadow-sm">
         <i class="fas fa-file-csv me-1"></i> Export CSV
     </a>
 </div>
</div>
<style>
    /* ── Stat cards: every status has its own color ── */
    .stat-filter-card {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        text-decoration: none !important;
        border-top: 3px solid var(--st) !important;
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
        width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--st-bg); color: var(--st); font-size: 0.75rem;
    }
    /* ── Status badge (read-only users) ── */
    .status-badge {
        display: inline-flex; align-items: center; gap: .35rem; padding: 4px 11px; border-radius: 999px;
        font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .2px;
        background: var(--st-bg); color: var(--st); border: 1px solid var(--st-bd);
    }
    /* ── Approval dropdown ── */
    .approval-select {
        cursor: pointer; width: 100%; font-weight: 700; font-size: 0.72rem; text-transform: uppercase;
        border-radius: 6px; padding: 5px 8px;
    }
    .approval-select option {
        color: #0f172a;
        background: #fff;
        text-transform: none;
        font-weight: 500;
    }
    .approval-select:disabled {
        opacity: .6;
        cursor: wait;
    }
    /* ── Row accent: colored edge by status ── */
    .data-grid-table tbody tr.row-st > td:first-child {
        box-shadow: inset 3px 0 0 var(--st);
    }
    /* ── Bulk action buttons ── */
    #bulkActionBar .btn-bulk-outline.bulk-approve {
        color: #16a34a;
        border-color: rgba(22,163,74,.5);
    }
    #bulkActionBar .btn-bulk-outline.bulk-approve:hover {
        background: rgba(22,163,74,.14);
    }
    #bulkActionBar .btn-bulk-outline.bulk-present {
        color: #0d9488;
        border-color: rgba(13,148,136,.5);
    }
    #bulkActionBar .btn-bulk-outline.bulk-present:hover {
        background: rgba(13,148,136,.14);
    }
    #bulkActionBar .btn-bulk-outline.bulk-absent {
        color: #db2777;
        border-color: rgba(219,39,119,.5);
    }
    #bulkActionBar .btn-bulk-outline.bulk-absent:hover {
        background: rgba(219,39,119,.14);
    }
    /* ── Row action icons ── */
    .data-grid-table .action-link.act-view {
        color: #0ea5e9;
    }
    .data-grid-table .action-link.act-edit {
        color: #d97706;
    }
    .data-grid-table .action-link.act-history {
        color: #7c3aed;
    }
    .data-grid-table .action-link.act-view:hover {
        background: rgba(14,165,233,.14);
    }
    .data-grid-table .action-link.act-edit:hover {
        background: rgba(217,119,6,.14);
    }
    .data-grid-table .action-link.act-history:hover {
        background: rgba(124,58,237,.14);
    }
    .filter-backdrop {
        position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4);
        opacity: 0; visibility: hidden; transition: opacity .2s; z-index: 1040;
    }
    .filter-backdrop.show {
        opacity: 1;
        visibility: visible;
    }
    .filter-slideover {
        z-index: 1050;
    }
</style>
{{-- ───────────── Stat Cards (clickable filters) ───────────── --}}
<div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
    @foreach ($statCards as $card)
    @php
    $isActive = $card['filter'] === null
    ? !request('approval_status')
    : request('approval_status') === $card['filter'];
    $params = array_merge(request()->except(['approval_status', 'page']), ['date' => $date]);
    if ($card['filter']) {
        $params['approval_status'] = $card['filter'];
    }
    @endphp
    <div class="col">
        <a href="{{ route('admin.attendance.daily', $params) }}" class="d-block h-100 text-decoration-none" title="Filter: {{ $card['label'] }}">
            <div class="glass-card p-3 h-100 border-main stat-filter-card {{ $isActive ? 'active-filter' : '' }}"
            style="--st: {{ $card['tone'] }}; --st-bg: {{ $rgba($card['tone'], 0.10) }};">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-low d-block" style="font-size: 0.75rem;">{{ $card['label'] }}</span>
                <span class="stat-icon"><i class="fas {{ $card['icon'] }}"></i></span>
            </div>
            <h4 class="fw-bold mb-0 stat-value">{{ $dailyStats[$card['key']] ?? 0 }}</h4>
        </div>
    </a>
</div>
@endforeach
</div>
{{-- ───────────── Bulk Action Form ───────────── --}}
<form id="bulkActionForm" action="{{ route('admin.attendance.bulkAction') }}" method="POST">
    @csrf
    <input type="hidden" name="action" id="bulkActionType">
    <input type="hidden" name="date" value="{{ $date }}">
    <input type="hidden" name="rejection_reason" id="bulkRejectionReason">
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" id="gridSearchInput" placeholder="Search name or email..." value="{{ request('search') }}">
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="date" id="gridDateInput" value="{{ $date }}"
                class="form-premium-control form-control-sm" style="width: 140px; padding: 6px 10px;">
                <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['date', 'page']), ['date' => $todayStr])) }}"
                 class="btn-premium btn-premium-secondary btn-sm {{ $date == $todayStr ? 'active' : '' }}">Today</a>
             </div>
             <div class="data-grid-results">{{ $users->total() }} Employees</div>
             <div class="data-grid-actions d-flex align-items-center gap-2">
                {{ $users->withQueryString()->links('components.pagination.premium') }}
            </div>
        </div>
        @if ($isSuperAdmin)
        <div class="data-grid-bulk-actions" id="bulkActionBar">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span id="selectedCount">0</span> Items Selected</span>
                <button type="button" class="btn-bulk-outline bulk-approve" onclick="submitBulkAction('approve')">
                    <i class="fas fa-check-circle"></i> Approve Selected
                </button>
                <button type="button" class="btn-bulk-outline bulk-present" onclick="submitBulkAction('mark_present')">
                    <i class="fas fa-user-check"></i> Mark Present
                </button>
                <button type="button" class="btn-bulk-outline bulk-absent" onclick="submitBulkAction('mark_absent')">
                    <i class="fas fa-user-times"></i> Mark Absent
                </button>
                <button type="button" class="btn-bulk-danger" onclick="submitBulkAction('reject')">
                    <i class="fas fa-times-circle"></i> Reject Selected
                </button>
            </div>
            <button type="button" class="btn-deselect-all" onclick="deselectAll()">Deselect All</button>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll"></th>
                        <th>EMPLOYEE</th>
                        <th>DATE</th>
                        <th>SHIFT & HOURS</th>
                        <th>DAILY WORK REPORT</th>
                        <th>TRACKED ACTIVITY</th>
                        <th class="text-center">APPROVAL STATUS</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    @php
                    $attendance = $attendances->get($user->id);
                    $isPending  = $attendance && $attendance->approval_status === 'pending';
                    $tLogs      = $timeLogCounts->get($user->id);
                    $status = $attendance?->approval_status;
                    $stKey  = $status ?: 'not_submitted';
                    $style  = $statusStyles[$stKey] ?? $statusStyles['not_submitted'];
                    $label  = $status ? strtoupper($status) : 'NOT SUBMITTED';
                    $editData = [
                    'userId'    => $user->id,
                    'userName'  => $user->name,
                    'date'      => $date,
                    'attStatus' => $attendance?->attendance_status ?? 'present',
                    'appStatus' => $attendance?->approval_status ?: 'draft',
                    'checkIn'   => $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '',
                    'checkOut'  => $attendance?->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '',
                    'remarks'   => $attendance?->remarks ?? '',
                    ];
                    @endphp
                    <tr class="row-st" style="--st: {{ $style['color'] }};">
                        <td>
                            <input type="checkbox" name="ids[]" value="{{ $user->id }}" class="data-grid-checkbox item-checkbox">
                        </td>
                        {{-- Employee --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-premium" style="width: 32px; height: 32px;">
                                    @if ($user->profile_image)
                                    <img alt="{{ $user->name }}" src="{{ asset('storage/' . $user->profile_image) }}">
                                    @else
                                    <div class="d-flex align-items-center justify-content-center w-100 h-100"
                                    style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                </div>
                                @endif
                            </div>
                            <div>
                                <div class="fw-bold text-high">{{ $user->name }}</div>
                                <div class="text-low extra-small">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    {{-- Date --}}
                    <td class="text-low small">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                    {{-- Shift --}}
                    <td>
                        <div class="text-high small fw-medium">
                            {{ $attendance && $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '--:--' }}
                            -
                            {{ $attendance && $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '--:--' }}
                        </div>
                        <div class="text-low" style="font-size: 0.75rem;">
                            Total: <strong class="text-high">{{ $attendance ? $attendance->formatted_working_hours : '0h 0m' }}</strong>
                        </div>
                    </td>
                    {{-- Report --}}
                    <td style="max-width: 240px;">
                        @if ($attendance && $attendance->remarks)
                        <div class="text-high small text-truncate" title="{{ $attendance->remarks }}" style="cursor: pointer;"
                           onclick="openReportDetailModal({{ $user->id }}, @js($date))">
                           <i class="fas fa-file-alt text-primary me-1"></i> {{ $attendance->remarks }}
                       </div>
                       @if ($attendance->submitted_at)
                       <div class="text-low" style="font-size: 0.75rem;">
                        Submitted {{ \Carbon\Carbon::parse($attendance->submitted_at)->format('h:i A') }}
                    </div>
                    @endif
                    @else
                    <span class="text-low small fst-italic">No report submitted</span>
                    @endif
                </td>
                {{-- Tracked --}}
                <td>
                    @if ($tLogs && $tLogs->count > 0)
                    <span class="badge-premium bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1"
                    style="cursor: pointer;"
                    onclick="openReportDetailModal({{ $user->id }}, @js($date))"
                    title="Click to view detailed tasks">
                    <i class="fas fa-stopwatch me-1"></i>
                    {{ $fmtDuration($tLogs->total_duration) }}
                    ({{ $tLogs->count }} {{ Str::plural('task', $tLogs->count) }})
                </span>
                @else
                <span class="badge-premium bg-secondary-subtle text-low px-2 py-1" style="font-size: 0.75rem;">
                    0 tasks logged
                </span>
                @endif
            </td>
            {{-- Approval status (dropdown + details below) --}}
            <td class="text-center" style="min-width: 190px;">
                @if ($isSuperAdmin)
                <select class="form-premium-control approval-select"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                data-date="{{ $date }}"
                data-current="{{ $status ?? '' }}"
                title="Change approval status"
                style="background-color: {{ $style['bg'] }}; color: {{ $style['color'] }}; border: 1px solid {{ $style['border'] }}; border-left: 4px solid {{ $style['color'] }};">
                @unless ($attendance)
                <option value="" selected disabled>Not Submitted</option>
                @endunless
                @foreach ($approvalLabels as $val => $text)
                <option value="{{ $val }}" {{ ($status ?? null) === $val ? 'selected' : '' }}>{{ $text }}</option>
                @endforeach
            </select>
            @else
            <span class="status-badge" style="--st: {{ $style['color'] }}; --st-bg: {{ $style['bg'] }}; --st-bd: {{ $style['border'] }};">
                <i class="fas {{ $style['icon'] }}"></i> {{ $label }}
            </span>
            @endif
            <div class="text-low mt-1" style="font-size: 0.72rem; line-height: 1.4;">
                @if (!$attendance)
                <div>No record for this date</div>
                @else
                @if ($attendance->submitted_at)
                <div>Submitted {{ \Carbon\Carbon::parse($attendance->submitted_at)->format('d M, h:i A') }}</div>
                @endif
                @if ($status === 'approved' && $attendance->approver_name)
                <div style="color: {{ $palette['approved'] }};">
                    <i class="fas fa-check-circle me-1"></i>Approved by <strong>{{ $attendance->approver_name }}</strong>
                    @if ($attendance->approved_at)
                    · {{ \Carbon\Carbon::parse($attendance->approved_at)->format('d M, h:i A') }}
                    @endif
                </div>
                @endif
                @if ($status === 'rejected' && $attendance->rejection_reason)
                <div style="color: {{ $palette['rejected'] }};" title="{{ $attendance->rejection_reason }}">
                    <i class="fas fa-times-circle me-1"></i>Reason: {{ \Illuminate\Support\Str::limit($attendance->rejection_reason, 60) }}
                </div>
                @endif
                @if ($status === 'pending')
                <div style="color: {{ $palette['pending'] }};">
                    <i class="fas fa-clock me-1"></i>Waiting for Super Admin approval
                </div>
                @endif
                @if ($status === 'draft' || !$status)
                <div style="color: {{ $palette['draft'] }};">
                    <i class="fas fa-file-alt me-1"></i>Draft, not yet submitted for approval
                </div>
                @endif
                @endif
            </div>
        </td>
        {{-- Actions --}}
        <td class="text-end pe-4">
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="action-link act-view border-0 bg-transparent"
                title="View Full Report & Activity Details"
                onclick="openReportDetailModal({{ $user->id }}, @js($date))">
                <i class="fas fa-eye"></i>
            </button>
            <button type="button" class="action-link act-edit border-0 bg-transparent"
            title="Manual Correction" onclick="openEditModal(@js($editData))">
            <i class="fas fa-pencil-alt"></i>
        </button>
        @if ($attendance)
        <button type="button" class="action-link act-history border-0 bg-transparent"
        title="Audit History" onclick="openHistoryModal({{ $attendance->id }}, @js($user->name))">
        <i class="fas fa-history"></i>
    </button>
    @endif
</div>
</td>
</tr>
@empty
<tr>
    <td colspan="8" class="text-center py-5">
        <div class="text-low">
            <i class="fas fa-calendar-times fa-3x mb-3 text-secondary opacity-50"></i>
            <p class="mb-0">No employee attendance records found for this date or filter.</p>
        </div>
    </td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
</form>
{{-- Hidden form for single approve --}}
<form id="singleApproveForm" method="POST" style="display: none;">
    @csrf
</form>
@if ($isSuperAdmin)
{{-- Hidden form for dropdown approval-status change (Super Admin only) --}}
<form id="approvalStatusForm" action="{{ url('admin/attendance/approval-status') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="user_id" id="as_user_id">
    <input type="hidden" name="date" id="as_date">
    <input type="hidden" name="approval_status" id="as_status">
    <input type="hidden" name="rejection_reason" id="as_reason">
</form>
@endif
{{-- ───────────── Report Detail Modal ───────────── --}}
<div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="modalUserName" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-main shadow-lg">
            <div class="modal-header border-subtle">
                <div class="d-flex align-items-center gap-3">
                    <div id="modalUserAvatar" class="avatar-premium" style="width: 42px; height: 42px; font-size: 1.1rem;">U</div>
                    <div>
                        <h5 class="modal-title fw-bold text-high mb-0" id="modalUserName">Daily Work Report</h5>
                        <div class="text-low small" id="modalReportDate">Date details</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-low mt-2 small">Loading report & task activity...</p>
                </div>
                <div id="modalError" class="alert alert-danger" style="display: none;"></div>
                <div id="modalReportContent" style="display: none;">
                    <div class="glass-card p-3 mb-4 border-main d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <span class="text-low small d-block">Approval Status</span>
                                <span id="modalApprovalBadge" class="badge-premium px-2 py-1">Pending</span>
                            </div>
                            <div class="border-start border-subtle ps-3">
                                <span class="text-low small d-block">Attendance</span>
                                <span id="modalAttendanceBadge" class="badge-premium px-2 py-1">Present</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <span class="text-low small d-block">Check In / Out</span>
                                <span id="modalShiftTime" class="text-high fw-semibold">--:-- - --:--</span>
                            </div>
                            <div>
                                <span class="text-low small d-block">Shift Hours</span>
                                <span id="modalWorkingHours" class="text-high fw-bold">0h 0m</span>
                            </div>
                            <div>
                                <span class="text-low small d-block">Time Tracked</span>
                                <span id="modalTrackedHours" class="text-primary fw-bold">0h 0m</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-file-alt text-primary"></i> Daily Work Report / Remarks
                        </h6>
                        <div class="glass-card p-3 border-main" style="background: rgba(var(--primary-rgb), 0.04);">
                            <div id="modalReportText" class="text-high" style="white-space: pre-wrap; font-size: 0.92rem; line-height: 1.5;">No remarks submitted.</div>
                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-subtle">
                                <span class="text-low small" id="modalSubmittedAt">Not submitted</span>
                                <span class="text-low small" id="modalApproverInfo"></span>
                            </div>
                        </div>
                        <div id="modalRejectionAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 border-0"
                        style="display: none; background: rgba(220, 38, 38, 0.15); color: #ef4444;">
                        <strong>Rejection Reason:</strong> <span id="modalRejectionReason"></span>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-0">
                            <i class="fas fa-tasks text-success"></i> Tracked Task Activities (<span id="modalTasksCount">0</span>)
                        </h6>
                        <span class="text-low small">Total Tracked:
                            <strong id="modalTotalTrackedSummary" class="text-primary">0h 0m</strong></span>
                        </div>
                        <div class="table-responsive glass-card p-0 border-main">
                            <table class="table data-grid-table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Time Period</th>
                                        <th>Duration</th>
                                        <th>Mode</th>
                                        <th>Description / Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTimeLogsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="modalTaskLogsSection" class="mb-2" style="display: none;">
                        <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-clipboard-list text-warning"></i> Additional Activity Notes & Updates
                        </h6>
                        <ul class="list-unstyled mb-0" id="modalTaskLogsList"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-subtle d-flex justify-content-between">
                <div id="modalActionButtons" class="d-flex gap-2"></div>
                <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
{{-- ───────────── Reject Modal ───────────── --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-main shadow-lg">
            <div class="modal-header border-subtle">
                <h5 class="modal-title fw-bold text-high" id="rejectModalLabel">Reject Attendance Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-medium mb-3">
                        You are rejecting the daily report for <strong id="rejectUserName" class="text-high"></strong>.
                        Please specify the reason below (mandatory).
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-high fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason_input" class="form-premium-control w-100" rows="4" required
                        placeholder="e.g. Check-out time appears incorrect. Please correct and resubmit."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-subtle">
                    <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-premium btn btn-danger">
                        <i class="fas fa-times me-1"></i> Reject Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ───────────── Edit / Correction Modal ───────────── --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-main shadow-lg">
            <div class="modal-header border-subtle">
                <h5 class="modal-title fw-bold text-high" id="editModalLabel">Correct Attendance Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.attendance.daily.update') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="date" id="edit_date">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-low small mb-1">Employee</label>
                        <input type="text" id="edit_user_name" class="form-premium-control w-100 bg-subtle" disabled>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-high fw-semibold">Attendance Status</label>
                            <select name="attendance_status" id="edit_attendance_status" class="form-premium-control w-100">
                                <option value="present">Present</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        @if ($isSuperAdmin)
                        <div class="col-md-6">
                            <label class="form-label text-high fw-semibold">Approval Status</label>
                            <select name="approval_status" id="edit_approval_status" class="form-premium-control w-100">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        @endif
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-high fw-semibold">Check In</label>
                            <input type="time" name="check_in" id="edit_check_in" class="form-premium-control w-100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-high fw-semibold">Check Out</label>
                            <input type="time" name="check_out" id="edit_check_out" class="form-premium-control w-100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-high fw-semibold">Remarks</label>
                        <textarea name="remarks" id="edit_remarks" class="form-premium-control w-100" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-high fw-semibold">Correction Reason (Logged to Audit Trail)</label>
                        <input type="text" name="correction_reason" id="edit_correction_reason" class="form-premium-control w-100"
                        placeholder="e.g. Employee forgot to punch out; confirmed with team lead.">
                    </div>
                </div>
                <div class="modal-footer border-subtle">
                    <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-premium btn-premium-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ───────────── Audit History Modal ───────────── --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-main shadow-lg">
            <div class="modal-header border-subtle">
                <h5 class="modal-title fw-bold text-high" id="historyModalLabel">Attendance Audit Trail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-low mt-2 small">Loading audit records...</p>
                </div>
                <div id="historyContent" style="display: none;">
                    <ul class="timeline-list list-unstyled position-relative ps-4" id="historyTimeline"></ul>
                </div>
            </div>
            <div class="modal-footer border-subtle">
                <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
{{-- ───────────── Advanced Filter Slideover (inside <x-admin>) ───────────── --}}
    <div class="filter-backdrop" id="filterBackdropDaily" onclick="closeDailyFilters()"></div>
    <div class="filter-slideover" id="filterSlideoverDaily">
        <form action="{{ route('admin.attendance.daily') }}" method="GET" class="h-100 d-flex flex-column" id="dailyAdvancedFilterForm">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <button type="button" class="filter-slideover-close border-0 bg-transparent" onclick="closeDailyFilters()" aria-label="Close filters">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="filter-slideover-body">
                @php
                $selectedUserIds = request()->input('user_id', []);
                if (!is_array($selectedUserIds)) {
                    $selectedUserIds = [$selectedUserIds];
                }
                $selectedUserIds = array_map('strval', array_filter($selectedUserIds, fn ($v) => $v !== null && $v !== ''));
                @endphp
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">EMPLOYEE / USER</label>
                    <x-multiselect id="filter_user_id" name="user_id[]" placeholder="Select employees..." :selected="$selectedUserIds" class="w-100">
                        @foreach ($allUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </x-multiselect>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">DATE / DAY</label>
                    <input type="date" name="date" class="form-premium-control bg-white text-dark border-main w-100" value="{{ $date }}">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">APPROVAL STATUS</label>
                    <x-select id="filter_approval_status" name="approval_status" placeholder="All Approval Statuses"
                    :selected="request('approval_status')" class="w-100">
                    <option value="">All Approval Statuses</option>
                    <option value="pending">Pending Approval</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="draft">Draft</option>
                    <option value="not_submitted">Not Submitted</option>
                </x-select>
            </div>
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">ATTENDANCE STATUS</label>
                <x-select id="filter_attendance_status" name="attendance_status" placeholder="All Attendance Statuses"
                :selected="request('attendance_status')" class="w-100">
                <option value="">All Attendance Statuses</option>
                <option value="present">Present</option>
                <option value="half_day">Half Day</option>
                <option value="absent">Absent</option>
            </x-select>
        </div>
        <div class="mb-4">
            <label class="heading-label d-block mb-2 text-low">SEARCH KEYWORD</label>
            <input type="text" name="search" id="filter_daily_search" class="form-premium-control bg-white text-dark border-main w-100"
            placeholder="Search by name, email, or remarks..." value="{{ request('search') }}">
        </div>
    </div>
    <div class="filter-slideover-footer">
        <a href="{{ route('admin.attendance.daily', ['date' => $date]) }}"
         class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">
         Reset
     </a>
     <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center" style="background:#0ea5e9;">
        <i class="fas fa-filter me-1"></i> Apply Filters
    </button>
</div>
</form>
</div>
{{-- ───────────── Scripts ───────────── --}}
<script>
    const ATT_URLS = {
        reportDetails: @js(route('admin.attendance.reportDetails')),
        base: @js(url('admin/attendance')),
    };
        // Same color identity as the PHP palette above.
    const STATUS_COLORS = {
        approved: '#16a34a', pending: '#d97706', rejected: '#dc2626', draft: '#7c3aed', not_submitted: '#64748b',
    };
    const ATTENDANCE_COLORS = { present: '#0d9488', half_day: '#7c3aed', absent: '#db2777' };
    const hexToRgba = (hex, a) => {
        const n = parseInt(hex.slice(1), 16);
        return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
    };
        // Paint a badge element with a solid color identity.
    function paintBadge(el, color, text) {
        el.className = 'badge-premium px-2 py-1';
        el.style.cssText = `background:${hexToRgba(color, 0.14)};color:${color};border:1px solid ${hexToRgba(color, 0.4)};font-weight:700;`;
        el.innerText = text;
    }
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
    const showModal = (id) => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
        // Keep all current filters in the URL and only change the given params.
    function goWithParams(updates) {
        const url = new URL(window.location.href);
        Object.entries(updates).forEach(([k, v]) => {
            if (v === null || v === '') url.searchParams.delete(k);
            else url.searchParams.set(k, v);
        });
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCount = document.getElementById('selectedCount');
        window.updateBulkBar = function () {
            const checked = document.querySelectorAll('.item-checkbox:checked').length;
            if (selectedCount) selectedCount.textContent = checked;
            if (bulkActionBar) bulkActionBar.classList.toggle('active', checked > 0);
            if (selectAll) selectAll.checked = checkboxes.length > 0 && checked === checkboxes.length;
        };
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach((cb) => (cb.checked = selectAll.checked));
                updateBulkBar();
            });
        }
        checkboxes.forEach((cb) => cb.addEventListener('change', updateBulkBar));
        window.deselectAll = function () {
            checkboxes.forEach((cb) => (cb.checked = false));
            updateBulkBar();
        };
        window.submitBulkAction = function (action) {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one employee.');
                return;
            }
            if (action === 'reject') {
                const reason = prompt(
                `Please enter rejection reason for the ${checked.length} selected employee report(s):`,
                'Report rejected by administrator.'
            );
                if (!reason || !reason.trim()) return;
                document.getElementById('bulkRejectionReason').value = reason.trim();
            } else if (action === 'approve') {
                if (!confirm(`Approve attendance/reports for ${checked.length} selected employee(s)?`)) return;
            } else if (action === 'mark_present' || action === 'mark_absent') {
                const word = action === 'mark_present' ? 'PRESENT' : 'ABSENT';
                if (!confirm(`Mark ${checked.length} selected employee(s) as ${word}?`)) return;
            }
            document.getElementById('bulkActionType').value = action;
            document.getElementById('bulkActionForm').submit();
        };
            // Search (Enter) and date change both preserve the current filters.
        const searchInput = document.getElementById('gridSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    goWithParams({ search: searchInput.value.trim() });
                }
            });
        }
        const dateInput = document.getElementById('gridDateInput');
        if (dateInput) {
            dateInput.addEventListener('change', () => {
                if (dateInput.value) goWithParams({ date: dateInput.value });
            });
        }
            // ESC closes the filter panel.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDailyFilters();
        });
    });
        /* ───── Approval status dropdown (Super Admin) ───── */
    document.addEventListener('DOMContentLoaded', function () {
        const labels = { draft: 'Draft', pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
        document.querySelectorAll('.approval-select').forEach((sel) => {
            sel.addEventListener('change', function () {
                const newStatus = sel.value;
                const oldStatus = sel.dataset.current || '';
                let reason = '';
                if (newStatus === 'rejected') {
                    reason = prompt(`Rejection reason for ${sel.dataset.userName} (required):`, '');
                    if (!reason || !reason.trim()) { sel.value = oldStatus; return; }
                    reason = reason.trim();
                } else {
                    const from = labels[oldStatus] || 'Not Submitted';
                    if (!confirm(`Change status for ${sel.dataset.userName}\nfrom ${from} to ${labels[newStatus]}?`)) {
                        sel.value = oldStatus;
                        return;
                    }
                }
                document.getElementById('as_user_id').value = sel.dataset.userId;
                document.getElementById('as_date').value = sel.dataset.date;
                document.getElementById('as_status').value = newStatus;
                document.getElementById('as_reason').value = reason;
                sel.disabled = true;
                document.getElementById('approvalStatusForm').submit();
            });
        });
    });
        /* ───── Single approve / reject ───── */
    function submitSingleApproval(actionUrl) {
        if (confirm('Approve this daily attendance report?')) {
            const form = document.getElementById('singleApproveForm');
            form.action = actionUrl;
            form.submit();
        }
    }
    function openRejectModal(attendanceId, userName) {
        document.getElementById('rejectUserName').innerText = userName;
        document.getElementById('rejectForm').action = `${ATT_URLS.base}/${attendanceId}/reject`;
        document.getElementById('rejection_reason_input').value = '';
        showModal('rejectModal');
    }
        // Reject from inside the detail modal: close the detail modal first, then open the reject modal.
    function openRejectFromDetail(attendanceId, userName) {
        const el = document.getElementById('reportDetailModal');
        const inst = bootstrap.Modal.getInstance(el);
        if (inst && el.classList.contains('show')) {
            el.addEventListener('hidden.bs.modal', () => openRejectModal(attendanceId, userName), { once: true });
            inst.hide();
        } else {
            openRejectModal(attendanceId, userName);
        }
    }
        /* ───── Edit modal ───── */
    function openEditModal(d) {
        document.getElementById('edit_user_id').value = d.userId;
        document.getElementById('edit_user_name').value = d.userName;
        document.getElementById('edit_date').value = d.date;
        document.getElementById('edit_attendance_status').value = d.attStatus;
        const appSel = document.getElementById('edit_approval_status');
        if (appSel) appSel.value = d.appStatus;
        document.getElementById('edit_check_in').value = d.checkIn;
        document.getElementById('edit_check_out').value = d.checkOut;
        document.getElementById('edit_remarks').value = d.remarks;
        document.getElementById('edit_correction_reason').value = '';
        showModal('editModal');
    }
        /* ───── Report detail modal ───── */
    function openReportDetailModal(userId, date) {
        const spinner = document.getElementById('modalLoadingSpinner');
        const content = document.getElementById('modalReportContent');
        const errorBox = document.getElementById('modalError');
        const actionBtns = document.getElementById('modalActionButtons');
        spinner.style.display = 'block';
        content.style.display = 'none';
        errorBox.style.display = 'none';
        actionBtns.innerHTML = '';
        showModal('reportDetailModal');
        const qs = new URLSearchParams({ user_id: userId, date: date });
        fetch(`${ATT_URLS.reportDetails}?${qs.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((res) => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then((data) => {
            spinner.style.display = 'none';
            content.style.display = 'block';
            renderReportDetails(data);
        })
        .catch((err) => {
            console.error(err);
            spinner.style.display = 'none';
            errorBox.innerText = 'Failed to load report details. Please try again.';
            errorBox.style.display = 'block';
        });
    }
    function renderReportDetails(data) {
            // User
        document.getElementById('modalUserName').innerText = data.user.name;
        document.getElementById('modalReportDate').innerText = `${data.user.role ?? ''} · ${data.date}`;
        const avatarEl = document.getElementById('modalUserAvatar');
        if (data.user.profile_image) {
            avatarEl.innerHTML = `<img src="${esc(data.user.profile_image)}" alt="${esc(data.user.name)}" style="width:100%;height:100%;border-radius:inherit;object-fit:cover;">`;
        } else {
            avatarEl.innerText = (data.user.name || 'U').charAt(0).toUpperCase();
        }
            // Attendance
        const att = data.attendance;
        const appBadge = document.getElementById('modalApprovalBadge');
        const attBadge = document.getElementById('modalAttendanceBadge');
        const shiftTime = document.getElementById('modalShiftTime');
        const workingHours = document.getElementById('modalWorkingHours');
        if (att) {
            const appStatus = att.approval_status || 'draft';
            paintBadge(appBadge, STATUS_COLORS[appStatus] || STATUS_COLORS.not_submitted,
                appStatus.charAt(0).toUpperCase() + appStatus.slice(1));
            const attStatus = att.attendance_status || 'present';
            paintBadge(attBadge, ATTENDANCE_COLORS[attStatus] || ATTENDANCE_COLORS.absent,
                attStatus.replace(/_/g, ' ').toUpperCase());
            shiftTime.innerText = `${att.check_in || '--:--'} - ${att.check_out || '--:--'}`;
            workingHours.innerText = att.working_hours || '0h 0m';
        } else {
            paintBadge(appBadge, STATUS_COLORS.not_submitted, 'Not Submitted');
            paintBadge(attBadge, ATTENDANCE_COLORS.absent, 'ABSENT');
            shiftTime.innerText = '--:-- - --:--';
            workingHours.innerText = '0h 0m';
        }
        document.getElementById('modalTrackedHours').innerText = data.total_tracked_formatted || '0h 0m';
            // Remarks
        const reportText = document.getElementById('modalReportText');
        if (att && att.remarks) {
            reportText.innerText = att.remarks;
            reportText.classList.remove('text-low', 'fst-italic');
        } else {
            reportText.innerText = 'No work report description submitted for this date.';
            reportText.classList.add('text-low', 'fst-italic');
        }
        document.getElementById('modalSubmittedAt').innerText =
        att && att.submitted_at ? `Submitted on: ${att.submitted_at}` : 'Submission: None';
        document.getElementById('modalApproverInfo').innerText =
        att && att.approver_name ? `Actioned by: ${att.approver_name} (${att.approved_at || ''})` : '';
        const rejAlert = document.getElementById('modalRejectionAlert');
        if (att && att.approval_status === 'rejected' && att.rejection_reason) {
            document.getElementById('modalRejectionReason').innerText = att.rejection_reason;
            rejAlert.style.display = 'block';
        } else {
            rejAlert.style.display = 'none';
        }
            // Time logs
        const timeLogs = data.time_logs || [];
        const body = document.getElementById('modalTimeLogsBody');
        body.innerHTML = '';
        document.getElementById('modalTasksCount').innerText = timeLogs.length;
        document.getElementById('modalTotalTrackedSummary').innerText = data.total_tracked_formatted || '0h 0m';
        if (timeLogs.length === 0) {
            body.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-low">No task timer activity recorded for this day.</td></tr>`;
        } else {
            timeLogs.forEach((tl) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td>
                            <a href="/details/${encodeURIComponent(tl.task_id)}" class="text-high fw-semibold text-decoration-none" target="_blank" rel="noopener">
                                <i class="fas fa-check-circle text-primary me-1"></i> ${esc(tl.task_title)}
                            </a>
                        </td>
                        <td class="text-low small">${esc(tl.project_name)}</td>
                        <td class="text-low small">${esc(tl.start_time)} - ${esc(tl.end_time)}</td>
                        <td><span class="badge-premium bg-subtle text-high border-subtle">${esc(tl.formatted_duration)}</span></td>
                        <td><span class="badge-premium bg-secondary-subtle text-low small">${esc(tl.mode)}</span></td>
                    <td class="text-low small">${esc(tl.description) || '-'}</td>`;
                    body.appendChild(tr);
                });
        }
            // Task logs (notes)
        const section = document.getElementById('modalTaskLogsSection');
        const list = document.getElementById('modalTaskLogsList');
        list.innerHTML = '';
        if (data.task_logs && data.task_logs.length > 0) {
            section.style.display = 'block';
            data.task_logs.forEach((t) => {
                const li = document.createElement('li');
                li.className = 'glass-card p-2 mb-2 border-main d-flex justify-content-between align-items-center';
                li.innerHTML = `
                        <div>
                            <span class="badge-premium bg-secondary-subtle text-low small me-2">${esc(t.created_at)}</span>
                            <strong class="text-high">${esc(t.task_title)}:</strong>
                            <span class="text-low small ms-1">${esc(t.note)}</span>
                        </div>
                    <span class="badge-premium bg-primary-subtle text-primary small">${esc(t.type)}</span>`;
                    list.appendChild(li);
                });
        } else {
            section.style.display = 'none';
        }
            // Approve / Reject buttons (event listeners, so no inline string escaping issues)
        const actionBtns = document.getElementById('modalActionButtons');
        actionBtns.innerHTML = '';
        if (data.can_approve && att && att.approval_status === 'pending') {
            const approveBtn = document.createElement('button');
            approveBtn.type = 'button';
            approveBtn.className = 'btn-premium btn btn-success d-flex align-items-center gap-1';
            approveBtn.innerHTML = '<i class="fas fa-check"></i> Approve Report';
            approveBtn.addEventListener('click', () => submitSingleApproval(`${ATT_URLS.base}/${att.id}/approve`));
            const rejectBtn = document.createElement('button');
            rejectBtn.type = 'button';
            rejectBtn.className = 'btn-premium btn btn-danger d-flex align-items-center gap-1';
            rejectBtn.innerHTML = '<i class="fas fa-times"></i> Reject Report';
            rejectBtn.addEventListener('click', () => openRejectFromDetail(att.id, data.user.name));
            actionBtns.append(approveBtn, rejectBtn);
        }
    }
        /* ───── History modal ───── */
    function openHistoryModal(attendanceId, userName) {
        const loading = document.getElementById('historyLoading');
        const content = document.getElementById('historyContent');
        const timeline = document.getElementById('historyTimeline');
        loading.style.display = 'block';
        content.style.display = 'none';
        timeline.innerHTML = '';
        document.getElementById('historyModalLabel').innerText = `Attendance Audit Trail — ${userName}`;
        showModal('historyModal');
        fetch(`${ATT_URLS.base}/${attendanceId}/history`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((res) => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then((data) => {
            loading.style.display = 'none';
            content.style.display = 'block';
            if (!data.logs || data.logs.length === 0) {
                timeline.innerHTML = '<li class="text-low py-3">No activity logs recorded for this attendance.</li>';
                return;
            }
            data.logs.forEach((log) => {
                const li = document.createElement('li');
                li.className = 'mb-3 position-relative pb-2 border-bottom border-subtle';
                const actor = log.actor_name || (log.user ? log.user.name : 'System');
                const when = new Date(log.created_at).toLocaleString();
                li.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge-premium bg-primary-subtle text-primary fw-semibold">${esc(log.action)}</span>
                            <span class="text-low small">${esc(when)}</span>
                        </div>
                        <div class="text-high">${esc(log.description)}</div>
                        <div class="text-low small mt-1">Actor: <strong class="text-high">${esc(actor)}</strong>
                    ${log.ip_address ? `| IP: ${esc(log.ip_address)}` : ''}</div>`;
                    timeline.appendChild(li);
                });
        })
        .catch((err) => {
            console.error(err);
            loading.style.display = 'none';
            content.style.display = 'block';
            timeline.innerHTML = '<li class="text-danger py-3">Failed to load history.</li>';
        });
    }
        /* ───── Filter slideover ───── */
    function openDailyFilters() {
        document.getElementById('filterSlideoverDaily')?.classList.add('show');
        document.getElementById('filterBackdropDaily')?.classList.add('show');
    }
    function closeDailyFilters() {
        document.getElementById('filterSlideoverDaily')?.classList.remove('show');
        document.getElementById('filterBackdropDaily')?.classList.remove('show');
    }
</script>
</x-admin>