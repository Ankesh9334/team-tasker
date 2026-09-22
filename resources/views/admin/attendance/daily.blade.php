<x-admin>
    <x-slot:title>
        Daily Work Reports & Attendance Approval | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Daily Work Reports</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Review daily employee work submissions, inspect detailed
                task activities, and manage report approvals.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal"
                data-bs-target="#globalSubmitReportModal">
                <i class="fas fa-plus-circle me-1"></i> Submit Report
            </button>
            <a href="{{ route('admin.attendance.reports', ['export' => 'daily', 'date' => $date]) }}"
                class="btn-premium btn-premium-secondary px-4 py-2 shadow-sm">
                <i class="fas fa-file-csv me-1"></i> Export CSV
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

    <!-- Daily Statistics Cards (Clickable Filters) -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        <!-- 1. Total Users -->
        <div class="col">
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: All Users">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ !request('approval_status') ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Total Users</span>
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
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => 'submitted'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Submitted Reports">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'submitted' ? 'active-filter' : '' }}">
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
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => 'pending'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Pending Approval">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'pending' ? 'active-filter' : '' }}">
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
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => 'approved'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Approved">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'approved' ? 'active-filter' : '' }}">
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
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => 'rejected'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Rejected">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'rejected' ? 'active-filter' : '' }}">
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
            <a href="{{ route('admin.attendance.daily', array_merge(request()->except(['approval_status', 'page']), ['date' => $date, 'approval_status' => 'not_submitted'])) }}"
                class="d-block h-100 text-decoration-none" title="Filter: Not Submitted">
                <div
                    class="glass-card p-3 h-100 border-main stat-filter-card {{ request('approval_status') === 'not_submitted' ? 'active-filter' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-low d-block" style="font-size: 0.75rem;">Not Submitted</span>
                        @if(request('approval_status') === 'not_submitted')
                            <i class="fas fa-check-circle text-secondary" style="font-size: 0.75rem;"></i>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-0 dark:text-white">{{ $dailyStats['not_submitted'] }}</h4>
                </div>
            </a>
        </div>
    </div>

    {{-- ── Bulk Action Form wraps the data-grid-wrapper ── --}}
    <form id="bulkActionForm" action="{{ route('admin.attendance.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkActionType">
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="rejection_reason" id="bulkRejectionReason">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search name or email..." value="{{ request('search') }}"
                        onkeypress="if(event.key === 'Enter') { event.preventDefault(); window.location.href='{{ route('admin.attendance.daily') }}?date={{ $date }}&search=' + encodeURIComponent(this.value); }">
                </div>

                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="filter_date" value="{{ $date }}"
                        class="form-premium-control form-control-sm" style="width: 140px; padding: 6px 10px;"
                        onchange="window.location.href='{{ route('admin.attendance.daily') }}?date=' + this.value + '{{ request('search') ? '&search=' . urlencode(request('search')) : '' }}'">
                    <a href="{{ route('admin.attendance.daily', array_merge(request()->except('date'), ['date' => 'today'])) }}"
                        class="btn-premium btn-premium-secondary btn-sm {{ $date == date('Y-m-d') ? 'active' : '' }}">Today</a>
                </div>

                <div class="data-grid-results">{{ $users->total() }} Employees</div>

                <div class="data-grid-actions d-flex align-items-center gap-2">
                    {{ $users->links('components.pagination.premium') }}
                </div>
            </div>

            <div class="data-grid-bulk-actions" id="bulkActionBar">
                <div class="data-grid-bulk-left">
                    <span class="data-grid-bulk-count"><span id="selectedCount">0</span> Items Selected</span>
                    <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('approve')">
                        <i class="fas fa-check-circle"></i> Approve Selected
                    </button>
                    <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('mark_present')">
                        <i class="fas fa-user-check"></i> Mark Present
                    </button>
                    <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('mark_absent')">
                        <i class="fas fa-user-times"></i> Mark Absent
                    </button>
                    <button type="button" class="btn-bulk-danger" onclick="submitBulkAction('reject')">
                        <i class="fas fa-times-circle"></i> Reject Selected
                    </button>
                </div>
                <button type="button" class="btn-deselect-all" onclick="deselectAll()">
                    Deselect All
                </button>
            </div>

            <div class="table-responsive">
                <table class="table data-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll">
                            </th>
                            <th>EMPLOYEE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>DATE</th>
                            <th>SHIFT & HOURS</th>
                            <th>DAILY WORK REPORT</th>
                            <th>TRACKED ACTIVITY</th>
                            <th class="text-center">APPROVAL STATUS</th>
                            <th>ACTION BY</th>
                            <th class="text-end pe-4">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $attendance = $attendances->get($user->id);
                                $isPending = $attendance && $attendance->approval_status === 'pending';
                                $isApproved = $attendance && $attendance->approval_status === 'approved';
                                $isRejected = $attendance && $attendance->approval_status === 'rejected';
                                $isDraft = $attendance && ($attendance->approval_status === 'draft' || empty($attendance->approval_status));

                                $appStatusBadge = match ($attendance?->approval_status) {
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    'draft' => 'info',
                                    default => 'secondary',
                                };

                                $tLogs = $timeLogCounts->get($user->id);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $user->id }}"
                                        class="data-grid-checkbox item-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-premium" style="width: 32px; height: 32px;">
                                            @if ($user->profile_image)
                                                <img alt="team-tasker" src="{{ asset('storage/' . $user->profile_image) }}">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white"
                                                    style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-high">{{ $user->name }}</div>
                                            <div class="text-low extra-small">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-low small">
                                    {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                </td>
                                <td>
                                    <div class="text-high small fw-medium">
                                        {{ $attendance && $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '--:--' }}
                                        -
                                        {{ $attendance && $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '--:--' }}
                                    </div>
                                    <div class="text-low" style="font-size: 0.75rem;">
                                        Total: <strong
                                            class="text-high">{{ $attendance ? $attendance->formatted_working_hours : '0h 0m' }}</strong>
                                    </div>
                                </td>
                                <td style="max-width: 240px;">
                                    @if($attendance && $attendance->remarks)
                                        <div class="text-high small text-truncate" title="{{ $attendance->remarks }}"
                                            style="cursor: pointer;"
                                            onclick="openReportDetailModal({{ $user->id }}, '{{ $date }}')">
                                            <i class="fas fa-file-alt text-primary me-1"></i> {{ $attendance->remarks }}
                                        </div>
                                        @if($attendance->submitted_at)
                                            <div class="text-low" style="font-size: 0.75rem;">Submitted
                                                {{ $attendance->submitted_at->format('h:i A') }}</div>
                                        @endif
                                    @else
                                        <span class="text-low small fst-italic">No report submitted</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tLogs && $tLogs->count > 0)
                                        <span
                                            class="badge-premium bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1"
                                            style="cursor: pointer;"
                                            onclick="openReportDetailModal({{ $user->id }}, '{{ $date }}')"
                                            title="Click to view detailed tasks">
                                            <i class="fas fa-stopwatch me-1"></i>
                                            {{ gmdate('H\h i\m', $tLogs->total_duration) }} ({{ $tLogs->count }}
                                            {{ Str::plural('task', $tLogs->count) }})
                                        </span>
                                    @else
                                        <span class="badge-premium bg-secondary-subtle text-low px-2 py-1"
                                            style="font-size: 0.75rem;">
                                            0 tasks logged
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($attendance && $attendance->approval_status)
                                        @if($attendance->approval_status === 'approved')
                                            <span class="badge-premium"
                                                style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">APPROVED</span>
                                        @elseif($attendance->approval_status === 'pending')
                                            <span class="badge-premium"
                                                style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PENDING</span>
                                        @elseif($attendance->approval_status === 'rejected')
                                            <span class="badge-premium"
                                                style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">REJECTED</span>
                                        @else
                                            <span class="badge-premium"
                                                style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ strtoupper($attendance->approval_status) }}</span>
                                        @endif
                                    @else
                                        <span class="badge-premium"
                                            style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">NOT
                                            SUBMITTED</span>
                                    @endif
                                </td>
                                <td class="text-low small">
                                    {{ $attendance && $attendance->approver ? $attendance->approver->name : '-' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- View Details & Activities Button -->
                                        <button type="button" class="action-link border-0 bg-transparent"
                                            title="View Full Report & Activity Details"
                                            onclick="openReportDetailModal({{ $user->id }}, '{{ $date }}')">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        @if($isPending)
                                            <!-- Direct Approve -->
                                            <button type="button" class="action-link border-0 bg-transparent"
                                                title="Approve Report" style="color: var(--success);"
                                                onclick="submitSingleApproval('{{ route('admin.attendance.approve', $attendance->id) }}')">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <!-- Open Reject Modal -->
                                            <button type="button" class="action-link delete border-0 bg-transparent"
                                                title="Reject Report"
                                                onclick="openRejectModal({{ $attendance->id }}, '{{ addslashes($user->name) }}')">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif

                                        <!-- Edit / Correction Modal trigger -->
                                        @php
                                            $attId = $attendance ? $attendance->id : 0;
                                            $cIn = $attendance && $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '';
                                            $cOut = $attendance && $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '';
                                            $attStatus = $attendance ? $attendance->attendance_status : 'present';
                                            $appStatus = $attendance ? $attendance->approval_status : 'approved';
                                            $remarks = $attendance ? $attendance->remarks : '';
                                        @endphp
                                        <button type="button" class="action-link border-0 bg-transparent"
                                            title="Manual Correction"
                                            onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $date }}', {{ $attId }}, '{{ $attStatus }}', '{{ $appStatus }}', '{{ $cIn }}', '{{ $cOut }}', '{{ addslashes($remarks) }}')">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>

                                        <!-- History Modal trigger -->
                                        @if($attendance)
                                            <button type="button" class="action-link border-0 bg-transparent"
                                                title="Audit History"
                                                onclick="openHistoryModal({{ $attendance->id }}, '{{ addslashes($user->name) }}')">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
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

    <!-- Hidden Form for Single Approve Action -->
    <form id="singleApproveForm" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Daily Work Report & Activity Details Modal -->
    <div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content glass-card border-main shadow-lg">
                <div class="modal-header border-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div id="modalUserAvatar" class="avatar-premium"
                            style="width: 42px; height: 42px; font-size: 1.1rem;">U</div>
                        <div>
                            <h5 class="modal-title fw-bold text-high mb-0" id="modalUserName">Daily Work Report</h5>
                            <div class="text-low small" id="modalReportDate">Date details</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalLoadingSpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-low mt-2 small">Loading report & task activity...</p>
                    </div>

                    <div id="modalReportContent" style="display: none;">
                        <!-- Status and Shift Overview Strip -->
                        <div
                            class="glass-card p-3 mb-4 border-main d-flex flex-wrap justify-content-between align-items-center gap-3">
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

                        <!-- 1. Daily Work Report Submission -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-file-alt text-primary"></i> Daily Work Report / Remarks
                            </h6>
                            <div class="glass-card p-3 border-main" style="background: rgba(var(--primary-rgb), 0.04);">
                                <div id="modalReportText" class="text-high"
                                    style="white-space: pre-wrap; font-size: 0.92rem; line-height: 1.5;">
                                    No remarks submitted.
                                </div>
                                <div
                                    class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-subtle">
                                    <span class="text-low small" id="modalSubmittedAt">Not submitted</span>
                                    <span class="text-low small" id="modalApproverInfo"></span>
                                </div>
                            </div>
                            <div id="modalRejectionAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 border-0"
                                style="display: none; background: rgba(220, 38, 38, 0.15); color: #ef4444;">
                                <strong>Rejection Reason:</strong> <span id="modalRejectionReason"></span>
                            </div>
                        </div>

                        <!-- 2. Detailed Task Activities (Time Logs) -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-0">
                                    <i class="fas fa-tasks text-success"></i> Tracked Task Activities (<span
                                        id="modalTasksCount">0</span>)
                                </h6>
                                <span class="text-low small">Total Tracked: <strong id="modalTotalTrackedSummary"
                                        class="text-primary">0h 0m</strong></span>
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
                                    <tbody id="modalTimeLogsBody">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 3. Task Activity Notes (Task Logs) -->
                        <div id="modalTaskLogsSection" class="mb-2" style="display: none;">
                            <h6 class="fw-bold text-high d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-clipboard-list text-warning"></i> Additional Activity Notes & Updates
                            </h6>
                            <ul class="list-unstyled mb-0" id="modalTaskLogsList">
                                <!-- Dynamic list -->
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-subtle d-flex justify-content-between">
                    <div id="modalActionButtons" class="d-flex gap-2">
                        <!-- Populated dynamically if pending and user can approve -->
                    </div>
                    <button type="button" class="btn-premium btn-premium-secondary"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal (Mandatory Reason) -->
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
                            You are rejecting the daily report for <strong id="rejectUserName"
                                class="text-high"></strong>.
                            Please specify the reason below (mandatory).
                        </p>
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Rejection Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason_input"
                                class="form-premium-control w-100" rows="4" required
                                placeholder="e.g. Check-out time appears incorrect. Please correct and resubmit."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn btn-danger">
                            <i class="fas fa-times me-1"></i> Reject Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Super Admin Edit / Correction Modal -->
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
                            <input type="text" id="edit_user_name" class="form-premium-control w-100 bg-subtle"
                                disabled>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-high fw-semibold">Attendance Status</label>
                                <select name="attendance_status" id="edit_attendance_status"
                                    class="form-premium-control w-100">
                                    <option value="present">Present</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-high fw-semibold">Approval Status</label>
                                <select name="approval_status" id="edit_approval_status"
                                    class="form-premium-control w-100">
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-high fw-semibold">Check In</label>
                                <input type="time" name="check_in" id="edit_check_in"
                                    class="form-premium-control w-100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-high fw-semibold">Check Out</label>
                                <input type="time" name="check_out" id="edit_check_out"
                                    class="form-premium-control w-100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Remarks</label>
                            <textarea name="remarks" id="edit_remarks" class="form-premium-control w-100" rows="2"
                                placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Correction Reason (Logged to Audit
                                Trail)</label>
                            <input type="text" name="correction_reason" class="form-premium-control w-100"
                                placeholder="e.g. Employee forgot to punch out; confirmed with team lead.">
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn-premium-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Audit History Modal -->
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
                        <ul class="timeline-list list-unstyled position-relative ps-4" id="historyTimeline">
                            <!-- Populated via AJAX -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-subtle">
                    <button type="button" class="btn-premium btn-premium-secondary"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const bulkActionBar = document.getElementById('bulkActionBar');
            const selectedCount = document.getElementById('selectedCount');

            window.updateBulkBar = function () {
                const checked = document.querySelectorAll('.item-checkbox:checked').length;
                if (selectedCount) selectedCount.textContent = checked;

                if (bulkActionBar) {
                    if (checked > 0) {
                        bulkActionBar.classList.add('active');
                    } else {
                        bulkActionBar.classList.remove('active');
                    }
                }
            };

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    if (!this.checked && selectAll) {
                        selectAll.checked = false;
                    } else if (selectAll && document.querySelectorAll('.item-checkbox:checked').length === checkboxes.length) {
                        selectAll.checked = true;
                    }
                    updateBulkBar();
                });
            });

            window.deselectAll = function () {
                if (selectAll) selectAll.checked = false;
                checkboxes.forEach(cb => cb.checked = false);
                updateBulkBar();
            };

            window.submitBulkAction = function (action) {
                const checked = document.querySelectorAll('.item-checkbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one employee.');
                    return;
                }

                document.getElementById('bulkActionType').value = action;

                if (action === 'reject') {
                    const reason = prompt(`Please enter rejection reason for the ${checked.length} selected employee report(s):`, 'Report rejected by administrator.');
                    if (!reason) return;
                    document.getElementById('bulkRejectionReason').value = reason;
                }

                if (action === 'approve') {
                    if (!confirm(`Approve attendance/reports for ${checked.length} selected employee(s)?`)) {
                        return;
                    }
                }

                document.getElementById('bulkActionForm').submit();
            };
        });

        function submitSingleApproval(actionUrl) {
            if (confirm('Approve this daily attendance report?')) {
                const form = document.getElementById('singleApproveForm');
                form.action = actionUrl;
                form.submit();
            }
        }

        function openRejectModal(attendanceId, userName) {
            document.getElementById('rejectUserName').innerText = userName;
            const form = document.getElementById('rejectForm');
            form.action = '{{ url("admin/attendance") }}/' + attendanceId + '/reject';
            document.getElementById('rejection_reason_input').value = '';
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        function openEditModal(userId, userName, date, attId, attStatus, appStatus, checkIn, checkOut, remarks) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_name').value = userName;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_attendance_status').value = attStatus;
            document.getElementById('edit_approval_status').value = appStatus;
            document.getElementById('edit_check_in').value = checkIn;
            document.getElementById('edit_check_out').value = checkOut;
            document.getElementById('edit_remarks').value = remarks;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openReportDetailModal(userId, date) {
            const modal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
            const spinner = document.getElementById('modalLoadingSpinner');
            const content = document.getElementById('modalReportContent');
            const actionBtns = document.getElementById('modalActionButtons');

            spinner.style.display = 'block';
            content.style.display = 'none';
            actionBtns.innerHTML = '';
            modal.show();

            fetch(`{{ route('admin.attendance.reportDetails') }}?user_id=${userId}&date=${date}`, {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    spinner.style.display = 'none';
                    content.style.display = 'block';

                    // User details
                    document.getElementById('modalUserName').innerText = data.user.name;
                    document.getElementById('modalReportDate').innerText = `${data.user.role} · ${data.date}`;
                    const avatarEl = document.getElementById('modalUserAvatar');
                    if (data.user.profile_image) {
                        avatarEl.innerHTML = `<img src="${data.user.profile_image}" alt="${data.user.name}" style="width: 100%; height: 100%; border-radius: inherit; object-fit: cover;">`;
                    } else {
                        avatarEl.innerText = data.user.name.charAt(0).toUpperCase();
                    }

                    // Attendance info
                    const att = data.attendance;
                    const appBadge = document.getElementById('modalApprovalBadge');
                    const attBadge = document.getElementById('modalAttendanceBadge');
                    const shiftTime = document.getElementById('modalShiftTime');
                    const workingHours = document.getElementById('modalWorkingHours');
                    const trackedHours = document.getElementById('modalTrackedHours');

                    if (att) {
                        const appClass = att.approval_status === 'approved' ? 'bg-success-subtle text-success border border-success border-opacity-25' : (att.approval_status === 'pending' ? 'bg-warning-subtle text-warning border border-warning border-opacity-25' : (att.approval_status === 'rejected' ? 'bg-danger-subtle text-danger border border-danger border-opacity-25' : 'bg-secondary-subtle text-low'));
                        appBadge.className = `badge-premium px-2 py-1 ${appClass}`;
                        appBadge.innerText = att.approval_status.charAt(0).toUpperCase() + att.approval_status.slice(1);

                        const attClass = att.attendance_status === 'present' ? 'bg-success-subtle text-success' : (att.attendance_status === 'half_day' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
                        attBadge.className = `badge-premium px-2 py-1 ${attClass}`;
                        attBadge.innerText = att.attendance_status.replace('_', ' ').toUpperCase();

                        shiftTime.innerText = `${att.check_in || '--:--'} - ${att.check_out || '--:--'}`;
                        workingHours.innerText = att.working_hours || '0h 0m';
                    } else {
                        appBadge.className = 'badge-premium px-2 py-1 bg-secondary-subtle text-low';
                        appBadge.innerText = 'Not Submitted';
                        attBadge.className = 'badge-premium px-2 py-1 bg-danger-subtle text-danger';
                        attBadge.innerText = 'ABSENT';
                        shiftTime.innerText = '--:-- - --:--';
                        workingHours.innerText = '0h 0m';
                    }

                    trackedHours.innerText = data.total_tracked_formatted;

                    // Report Remarks
                    const reportText = document.getElementById('modalReportText');
                    const submittedAt = document.getElementById('modalSubmittedAt');
                    const approverInfo = document.getElementById('modalApproverInfo');
                    const rejAlert = document.getElementById('modalRejectionAlert');
                    const rejReason = document.getElementById('modalRejectionReason');

                    if (att && att.remarks) {
                        reportText.innerText = att.remarks;
                        reportText.classList.remove('text-low', 'fst-italic');
                    } else {
                        reportText.innerText = 'No work report description submitted for this date.';
                        reportText.classList.add('text-low', 'fst-italic');
                    }

                    submittedAt.innerText = att && att.submitted_at ? `Submitted on: ${att.submitted_at}` : 'Submission: None';
                    approverInfo.innerText = att && att.approver_name ? `Actioned by: ${att.approver_name} (${att.approved_at || ''})` : '';

                    if (att && att.approval_status === 'rejected' && att.rejection_reason) {
                        rejAlert.style.display = 'block';
                        rejReason.innerText = att.rejection_reason;
                    } else {
                        rejAlert.style.display = 'none';
                    }

                    // Time Logs
                    const timeLogsBody = document.getElementById('modalTimeLogsBody');
                    timeLogsBody.innerHTML = '';
                    document.getElementById('modalTasksCount').innerText = data.time_logs.length;
                    document.getElementById('modalTotalTrackedSummary').innerText = data.total_tracked_formatted;

                    if (data.time_logs.length === 0) {
                        timeLogsBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-low">No task timer activity recorded for this day.</td></tr>`;
                    } else {
                        data.time_logs.forEach(tl => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                            <td>
                                <a href="/details/${tl.task_id}" class="text-high fw-semibold text-decoration-none" target="_blank">
                                    <i class="fas fa-check-circle text-primary me-1"></i> ${tl.task_title}
                                </a>
                            </td>
                            <td class="text-low small">${tl.project_name}</td>
                            <td class="text-low small">${tl.start_time} - ${tl.end_time}</td>
                            <td>
                                <span class="badge-premium bg-subtle text-high border-subtle">${tl.formatted_duration}</span>
                            </td>
                            <td><span class="badge-premium bg-secondary-subtle text-low small">${tl.mode}</span></td>
                            <td class="text-low small">${tl.description || '-'}</td>
                        `;
                            timeLogsBody.appendChild(tr);
                        });
                    }

                    // Task Logs (Notes)
                    const taskLogsSection = document.getElementById('modalTaskLogsSection');
                    const taskLogsList = document.getElementById('modalTaskLogsList');
                    taskLogsList.innerHTML = '';

                    if (data.task_logs && data.task_logs.length > 0) {
                        taskLogsSection.style.display = 'block';
                        data.task_logs.forEach(tLog => {
                            const li = document.createElement('li');
                            li.className = 'glass-card p-2 mb-2 border-main d-flex justify-content-between align-items-center';
                            li.innerHTML = `
                            <div>
                                <span class="badge-premium bg-secondary-subtle text-low small me-2">${tLog.created_at}</span>
                                <strong class="text-high">${tLog.task_title}:</strong>
                                <span class="text-low small ms-1">${tLog.note}</span>
                            </div>
                            <span class="badge-premium bg-primary-subtle text-primary small">${tLog.type}</span>
                        `;
                            taskLogsList.appendChild(li);
                        });
                    } else {
                        taskLogsSection.style.display = 'none';
                    }

                    // Action buttons inside modal
                    if (data.can_approve && att && att.approval_status === 'pending') {
                        actionBtns.innerHTML = `
                        <button type="button" class="btn-premium btn btn-success d-flex align-items-center gap-1" onclick="submitSingleApproval('{{ url("admin/attendance") }}/${att.id}/approve')">
                            <i class="fas fa-check"></i> Approve Report
                        </button>
                        <button type="button" class="btn-premium btn btn-danger d-flex align-items-center gap-1" onclick="openRejectModal(${att.id}, '${data.user.name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-times"></i> Reject Report
                        </button>
                    `;
                    }
                })
                .catch(err => {
                    spinner.style.display = 'none';
                    content.style.display = 'block';
                    content.innerHTML = `<div class="alert alert-danger">Failed to load report details. Please try again.</div>`;
                });
        }

        function openHistoryModal(attendanceId, userName) {
            const loading = document.getElementById('historyLoading');
            const content = document.getElementById('historyContent');
            const timeline = document.getElementById('historyTimeline');

            loading.style.display = 'block';
            content.style.display = 'none';
            timeline.innerHTML = '';

            new bootstrap.Modal(document.getElementById('historyModal')).show();

            fetch('{{ url("admin/attendance") }}/' + attendanceId + '/history', {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    loading.style.display = 'none';
                    content.style.display = 'block';

                    if (!data.logs || data.logs.length === 0) {
                        timeline.innerHTML = '<li class="text-low py-3">No activity logs recorded for this attendance.</li>';
                        return;
                    }

                    data.logs.forEach(log => {
                        const li = document.createElement('li');
                        li.className = 'mb-3 position-relative pb-2 border-bottom border-subtle';
                        const actorName = log.user ? log.user.name : 'System';
                        const dateStr = new Date(log.created_at).toLocaleString();
                        li.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge-premium bg-primary-subtle text-primary fw-semibold">${log.action}</span>
                            <span class="text-low small">${dateStr}</span>
                        </div>
                        <div class="text-high">${log.description}</div>
                        <div class="text-low small mt-1">Actor: <strong class="text-high">${actorName}</strong> ${log.ip_address ? `| IP: ${log.ip_address}` : ''}</div>
                    `;
                        timeline.appendChild(li);
                    });
                })
                .catch(err => {
                    loading.style.display = 'none';
                    content.style.display = 'block';
                    timeline.innerHTML = '<li class="text-danger py-3">Failed to load history.</li>';
                });
        }
    </script>
</x-admin>

<!-- Advanced Filter Slideover for Daily Work Reports (identical to Leave Requests) -->
<div class="filter-slideover" id="filterSlideoverDaily">
    <form action="{{ route('admin.attendance.daily') }}" method="GET" class="h-100 d-flex flex-column">
        <div class="filter-slideover-header">
            <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
            <div class="filter-slideover-close"
                onclick="document.getElementById('filterSlideoverDaily').classList.remove('show')">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="filter-slideover-body">
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">EMPLOYEE / USER</label>
                @php
                    $selectedUserIds = is_array(request('user_id'))
                        ? array_values(array_filter(array_map('strval', request('user_id'))))
                        : (request('user_id') ? [(string) request('user_id')] : []);
                @endphp
                <x-multiselect id="filter_user_id" name="user_id[]" placeholder="Select employees..."
                    :selected="$selectedUserIds" class="w-100">
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </x-multiselect>
            </div>
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">DATE / DAY</label>
                <input type="date" name="date" class="form-premium-control bg-white text-dark border-main"
                    value="{{ $date }}">
            </div>
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">APPROVAL STATUS</label>
                <x-select id="filter_approval_status" name="approval_status" placeholder="All Approval Statuses"
                    :selected="request('approval_status')" class="w-100">
                    <option value="pending">Pending Approval</option>
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
                    <option value="present">Present</option>
                    <option value="half_day">Half Day</option>
                    <option value="absent">Absent</option>
                </x-select>
            </div>
            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">SEARCH KEYWORD</label>
                <input type="text" name="search" class="form-premium-control bg-white text-dark border-main"
                    placeholder="Search by name, email, or remarks..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="filter-slideover-footer">
            <a href="{{ route('admin.attendance.daily') }}"
                class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</a>
            <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center"
                style="background: #0ea5e9;">Apply Filters</button>
        </div>
    </form>
</div>