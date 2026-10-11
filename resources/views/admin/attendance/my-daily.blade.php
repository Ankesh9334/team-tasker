<x-admin>
    <x-slot:title>
        My Daily Attendance | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">My Daily Attendance</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Record punches, review daily hours, inspect tracked tasks, and submit your report.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form id="dailyDateForm" action="{{ route('admin.attendance.myDaily') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                <input type="date" name="date" value="{{ $date }}" class="form-premium-control form-control-sm" style="width: 155px; padding: 6px 10px;" onchange="this.form.submit()">
                @php
                    $todayStr = now()->format('Y-m-d');
                @endphp
                <a href="{{ route('admin.attendance.myDaily', ['date' => $todayStr]) }}" 
                   class="btn-premium btn-premium-secondary btn-sm {{ $date === $todayStr ? 'active' : '' }}" 
                   title="Go to Today">
                    Today
                </a>
            </form>

            <a href="{{ route('admin.attendance.myMonthly') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-calendar-alt"></i> Monthly Report
            </a>
            <a href="{{ route('admin.attendance.calendar') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-2 shadow-sm">
                <i class="fas fa-calendar-week"></i> Calendar View
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Shift Punch & Status Card -->
        <div class="col-lg-7">
            <div class="glass-card p-4 border-main h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-low small d-block">Selected Date</span>
                            <h4 class="fw-bold text-high mb-0">{{ $today->format('l, d F Y') }}</h4>
                        </div>
                        @if($myAttendance)
                            @php
                                $statusBadge = match ($myAttendance->attendance_status) {
                                    'present' => 'success',
                                    'half_day' => 'warning',
                                    'absent' => 'danger',
                                    default => 'secondary',
                                };
                                $appBadge = match ($myAttendance->approval_status) {
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    'draft' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <div class="d-flex gap-2">
                                <span class="badge-premium bg-{{ $statusBadge }}-subtle text-{{ $statusBadge }} border border-{{ $statusBadge }} border-opacity-25 px-3 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $myAttendance->attendance_status)) }}
                                </span>
                                <span class="badge-premium bg-{{ $appBadge }}-subtle text-{{ $appBadge }} border border-{{ $appBadge }} border-opacity-25 px-3 py-1">
                                    {{ ucfirst($myAttendance->approval_status ?? 'Approved') }}
                                </span>
                            </div>
                        @else
                            <span class="badge-premium bg-secondary-subtle text-low px-3 py-1">Not Punched In</span>
                        @endif
                    </div>

                    <hr class="border-subtle my-3">

                    <div class="row text-center py-3 g-3">
                        <div class="col-4 border-end border-subtle">
                            <span class="text-low small d-block mb-1">Check In</span>
                            <h5 class="fw-bold text-high mb-0">
                                {{ $myAttendance && $myAttendance->check_in ? \Carbon\Carbon::parse($myAttendance->check_in)->format('h:i A') : '--:--' }}
                            </h5>
                        </div>
                        <div class="col-4 border-end border-subtle">
                            <span class="text-low small d-block mb-1">Check Out</span>
                            <h5 class="fw-bold text-high mb-0">
                                {{ $myAttendance && $myAttendance->check_out ? \Carbon\Carbon::parse($myAttendance->check_out)->format('h:i A') : '--:--' }}
                            </h5>
                        </div>
                        <div class="col-4">
                            <span class="text-low small d-block mb-1">Total Hours</span>
                            <h5 class="fw-bold text-primary mb-0">
                                {{ $myAttendance ? $myAttendance->formatted_working_hours : '0h 0m' }}
                            </h5>
                        </div>
                    </div>
                </div>

                <!-- Action Punch Buttons -->
                <div class="pt-4 border-top border-subtle d-flex gap-3">
                    @if(!$myAttendance || !$myAttendance->check_in)
                        <button type="button" class="btn-premium btn-premium-primary flex-grow-1 justify-content-center py-2 fs-6 shadow-sm" data-bs-toggle="modal" data-bs-target="#globalClockInModal">
                            <i class="fas fa-sign-in-alt me-2"></i> Punch In Shift
                        </button>
                    @elseif(!$myAttendance->check_out)
                        <button type="button" class="btn btn-danger flex-grow-1 justify-content-center py-2 fs-6 d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#globalClockOutModal">
                            <i class="fas fa-sign-out-alt me-2"></i> Punch Out Shift
                        </button>
                    @else
                        <button type="button" class="btn btn-success flex-grow-1 py-2 fs-6" disabled>
                            <i class="fas fa-check-circle me-2"></i> Shift Completed for Today
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Submission & Approval Lifecycle Card -->
        <div class="col-lg-5">
            <div class="glass-card p-4 border-main h-100">
                <h5 class="fw-bold text-high mb-3">Daily Report Submission</h5>

                @if(!$myAttendance)
                    <div class="p-4 text-center text-low border border-dashed border-subtle rounded">
                        <i class="fas fa-clock fa-2x mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0">Punch in to initiate your daily attendance and work record.</p>
                    </div>
                @elseif($myAttendance->isApproved())
                    <div class="p-4 rounded border border-success border-opacity-25 bg-success-subtle text-success text-center">
                        <i class="fas fa-check-circle fa-3x mb-2 text-success"></i>
                        <h5 class="fw-bold mb-1">Approved & Verified</h5>
                        <p class="small mb-0">
                            Your report was approved by
                            <strong>{{ $myAttendance->approver?->name ?? 'Super Admin' }}</strong>
                            at {{ $myAttendance->approved_at ? $myAttendance->approved_at->format('h:i A') : '-' }}.
                        </p>
                    </div>
                @elseif($myAttendance->isPending())
                    <div class="p-4 rounded border border-warning border-opacity-25 bg-warning-subtle text-warning text-center">
                        <i class="fas fa-hourglass-half fa-3x mb-2"></i>
                        <h5 class="fw-bold mb-1">Pending Management Review</h5>
                        <p class="small mb-0">
                            Submitted at
                            {{ $myAttendance->submitted_at ? $myAttendance->submitted_at->format('h:i A') : '-' }}.
                            Awaiting approval.
                        </p>
                    </div>
                @elseif($myAttendance->isRejected())
                    <div class="alert alert-danger border-0 p-3 mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-exclamation-circle fa-lg mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Report Rejected</h6>
                                <p class="mb-1 small"><strong>Reason:</strong> {{ $myAttendance->rejection_reason }}</p>
                                <small class="text-low">Please adjust your remarks or clarify details below and resubmit.</small>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.attendance.resubmit', $myAttendance->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Corrected Remarks / Explanation</label>
                            <textarea name="remarks" class="form-premium-control w-100" rows="3" required placeholder="Explain corrections...">{{ $myAttendance->remarks }}</textarea>
                        </div>
                        <button type="submit" class="btn-premium btn-premium-primary w-100 justify-content-center shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Resubmit Corrected Report
                        </button>
                    </form>
                @else
                    <!-- Draft state -->
                    <p class="text-low small mb-3">
                        Once you have finished your work or punched out, submit your daily attendance report for management verification.
                    </p>

                    <form action="{{ route('admin.attendance.submit', $myAttendance->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Daily Tasks / Notes</label>
                            <textarea name="remarks" class="form-premium-control w-100" rows="3" placeholder="Summary of work completed today...">{{ $myAttendance->remarks }}</textarea>
                        </div>
                        <button type="submit" class="btn-premium btn-premium-primary w-100 justify-content-center shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Submit for Approval
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Task Activity & Timers Standard Data Grid -->
    <div class="data-grid-wrapper mb-4">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" form="taskSearchForm" value="{{ request('search') }}" 
                       placeholder="Search task title or note..." 
                       onchange="document.getElementById('taskSearchForm').submit()">
                @if(request('search'))
                    <a href="{{ route('admin.attendance.myDaily', ['date' => $date]) }}" 
                       class="text-low ms-2 text-decoration-none small" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            <form id="taskSearchForm" action="{{ route('admin.attendance.myDaily') }}" method="GET" class="d-none">
                <input type="hidden" name="date" value="{{ $date }}">
            </form>

            <div class="data-grid-results">{{ $timeLogs->count() }} Tasks Tracked</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                <span class="badge-premium bg-primary-subtle text-primary fw-semibold px-3 py-1">
                    Total Tracked: {{ $formattedTotalTracked }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>TASK <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>PROJECT</th>
                        <th>TIME PERIOD</th>
                        <th>DURATION</th>
                        <th>MODE</th>
                        <th>NOTES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timeLogs as $tl)
                        <tr>
                            <td>
                                <a href="{{ route('details', $tl->task_id) }}" class="text-high fw-semibold text-decoration-none">
                                    <i class="fas fa-check-circle text-primary me-1"></i>
                                    {{ $tl->task?->title ?? 'Untitled Task' }}
                                </a>
                            </td>
                            <td class="text-low small">{{ $tl->task?->project?->name ?? 'General' }}</td>
                            <td class="text-low small">
                                {{ $tl->start_time ? $tl->start_time->format('h:i A') : '--' }} - {{ $tl->end_time ? $tl->end_time->format('h:i A') : '--' }}
                            </td>
                            <td>
                                <span class="badge-premium bg-subtle text-high border-subtle">
                                    {{ gmdate('H\h i\m', $tl->duration) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-premium bg-secondary-subtle text-low small">{{ $tl->mode }}</span>
                            </td>
                            <td class="text-low small text-truncate" style="max-width: 250px;" title="{{ $tl->description }}">
                                {{ $tl->description ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-low">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-stopwatch fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-semibold text-high mb-1">No tracked tasks for this date</h6>
                                    <p class="small text-low mb-0">Start a task timer in your task board or details to log work for {{ $today->format('d M Y') }}.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Attendance History Standard Data Grid -->
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary"></i>
                <span class="text-high fw-semibold">Recent Attendance History</span>
            </div>

            <div class="data-grid-results">{{ $recentAttendances->total() }} Records</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                {{ $recentAttendances->links('components.pagination.premium') }}
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
                        <th>REMARKS</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAttendances as $rAtt)
                        @php
                            $rDateStr = $rAtt->attendance_date 
                                ? $rAtt->attendance_date->format('Y-m-d') 
                                : \Carbon\Carbon::parse($rAtt->date)->format('Y-m-d');
                            $rDateFormatted = $rAtt->attendance_date 
                                ? $rAtt->attendance_date->format('d M Y (D)') 
                                : \Carbon\Carbon::parse($rAtt->date)->format('d M Y (D)');
                            $isCurrentDate = ($rDateStr === $date);
                            $rStatusColor = match($rAtt->attendance_status) {
                                'present' => 'success',
                                'half_day' => 'warning',
                                'absent' => 'danger',
                                default => 'secondary',
                            };
                            $rAppColor = match($rAtt->approval_status) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'info',
                            };
                        @endphp
                        <tr class="{{ $isCurrentDate ? 'table-primary bg-opacity-25' : '' }}">
                            <td class="text-high fw-semibold">
                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                {{ $rDateFormatted }}
                                @if($isCurrentDate)
                                    <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Active View</span>
                                @endif
                            </td>
                            <td class="text-high">
                                {{ $rAtt->check_in ? \Carbon\Carbon::parse($rAtt->check_in)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-high">
                                {{ $rAtt->check_out ? \Carbon\Carbon::parse($rAtt->check_out)->format('h:i A') : '--:--' }}
                            </td>
                            <td class="text-low">
                                <span class="badge-premium bg-subtle text-high border-subtle">
                                    {{ $rAtt->formatted_working_hours }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $rStatusColor }}-subtle text-{{ $rStatusColor }} border border-{{ $rStatusColor }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $rAtt->attendance_status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-premium bg-{{ $rAppColor }}-subtle text-{{ $rAppColor }} border border-{{ $rAppColor }} border-opacity-25 px-2 py-1">
                                    {{ ucfirst($rAtt->approval_status ?? 'Approved') }}
                                </span>
                            </td>
                            <td class="text-low small text-truncate" style="max-width: 200px;" title="{{ $rAtt->remarks }}">
                                {{ $rAtt->remarks ?: '-' }}
                            </td>
                            <td class="text-end pe-4">
                                @if(!$isCurrentDate)
                                    <a href="{{ route('admin.attendance.myDaily', ['date' => $rDateStr]) }}" 
                                       class="btn-premium btn-premium-secondary btn-sm" 
                                       title="Inspect and log work for this date">
                                        View Date
                                    </a>
                                @else
                                    <span class="text-low small">Viewing</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-low">
                                No attendance records logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance Audit Trail / Activity History for User -->
    @if($myAttendance && $myAttendance->logs->count() > 0)
        <div class="glass-card p-4 border-main mb-4">
            <h5 class="fw-bold text-high mb-3 d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary"></i> Today's Activity Trail
            </h5>
            <ul class="list-unstyled mb-0">
                @foreach($myAttendance->logs as $log)
                    <li class="d-flex align-items-start gap-3 py-2 border-bottom border-subtle">
                        <div class="badge-premium bg-primary-subtle text-primary mt-1" style="font-size: 0.75rem;">
                            {{ $log->created_at->format('h:i A') }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-high fw-semibold" style="font-size: 0.9rem;">{{ $log->description }}</div>
                            <div class="text-low small">Action: {{ $log->action }} &bull; By: {{ $log->user?->name ?? 'System' }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-admin>