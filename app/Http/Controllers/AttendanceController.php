<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Setting;
use App\Models\TaskLog;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /* ---------------------------------------------------------------
     |  Helpers
     | --------------------------------------------------------------- */

    /**
     * Sirf Super Admin approval status badal sakta hai.
     */
    protected function isSuperAdmin(): bool
    {
        $u = Auth::user();

        return $u && (($u instanceof \App\Models\Admin)
            || (method_exists($u, 'hasRole') && $u->hasRole('super-admin')));
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless($this->isSuperAdmin(), 403, 'Only Super Admin can change attendance approval status.');
    }

    protected function resolveDate(?string $date): string
    {
        $today = $this->attendanceService->today();

        if (! $date || $date === 'today') {
            return $today->format('Y-m-d');
        }
        if ($date === 'yesterday') {
            return $today->copy()->subDay()->format('Y-m-d');
        }

        return $date;
    }

    /**
     * Attendance Dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $date = $this->resolveDate($request->input('date'));

        $today = $this->attendanceService->today();
        $todayStr = $today->format('Y-m-d');

        $search = $request->input('search');
        $approvalStatus = $request->input('approval_status');
        $attendanceStatus = $request->input('attendance_status') ?: $request->input('status');

        $filters = [
            'date' => $date,
            'search' => $search,
            'attendance_status' => $attendanceStatus,
            'approval_status' => $approvalStatus,
        ];

        $perPage = (int) $request->input('per_page', 15);
        $users = $this->attendanceService->getDailyReport($filters, $perPage > 0 ? $perPage : 15);
        $dailyStats = $this->attendanceService->getDailyStats($date);

        $attendances = Attendance::with(['user', 'approver'])
        ->where('attendance_date', $date)
        ->whereIn('user_id', $users->pluck('id'))
        ->get()
        ->keyBy('user_id');

        $timeLogCounts = TimeLog::whereIn('user_id', $users->pluck('id'))
        ->whereDate('start_time', $date)
        ->selectRaw('user_id, count(*) as count, sum(duration) as total_duration')
        ->groupBy('user_id')
        ->get()
        ->keyBy('user_id');

        $myAttendance = Attendance::where('user_id', $user->id)
        ->where(function ($q) use ($todayStr) {
            $q->where('attendance_date', $todayStr)->orWhere('date', $todayStr);
        })
        ->first();

        // 7-Day Trend Graph Data
        $chartLabels = [];
        $chartPresent = [];
        $chartAbsent = [];
        $chartLate = [];
        $chartLeave = [];
        $personalWorkHours = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = $this->attendanceService->today()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $chartLabels[] = $d->format('D');

            $stats = $this->attendanceService->getDailyStats($dateStr);
            $chartPresent[] = $stats['approved'] + $stats['pending'];
            $chartLate[] = 0;
            $chartLeave[] = 0;
            $chartAbsent[] = $stats['not_submitted'] + $stats['rejected'];

            $myAtt = Attendance::where('user_id', $user->id)
            ->where(function ($q) use ($dateStr) {
                $q->where('attendance_date', $dateStr)->orWhere('date', $dateStr);
            })
            ->first();

            $personalWorkHours[] = $myAtt ? round(((float) $myAtt->working_minutes) / 60, 2) : 0;
        }

        $presentToday = $dailyStats['approved'] + $dailyStats['pending'];
        $lateToday = 0;
        $onLeaveToday = 0;
        $absentToday = $dailyStats['not_submitted'] + $dailyStats['rejected'];

        return view('admin.attendance.dashboard', compact(
            'users',
            'attendances',
            'date',
            'dailyStats',
            'timeLogCounts',
            'presentToday',
            'lateToday',
            'onLeaveToday',
            'absentToday',
            'myAttendance',
            'today',
            'chartLabels',
            'chartPresent',
            'chartAbsent',
            'chartLate',
            'chartLeave',
            'personalWorkHours'
        ));
    }

    /**
     * Super Admin Daily Reports Table & Approval view.
     */
    public function daily(Request $request)
    {
        Gate::authorize('viewAny', Attendance::class);

        $date = $this->resolveDate($request->input('date'));

        $selectedUserId = $request->input('user_id') ?: $request->input('user_ids');
        if (is_array($selectedUserId)) {
            $selectedUserId = array_values(array_filter($selectedUserId));
            if (empty($selectedUserId)) {
                $selectedUserId = null;
            }
        }
        $search = $request->input('search');
        $attendanceStatus = $request->input('attendance_status') ?: $request->input('status');
        $approvalStatus = $request->input('approval_status');

        $filters = [
            'date' => $date,
            'user_id' => $selectedUserId,
            'search' => $search,
            'attendance_status' => $attendanceStatus,
            'approval_status' => $approvalStatus,
        ];

        $perPage = (int) $request->input('per_page', 15);
        $users = $this->attendanceService->getDailyReport($filters, $perPage > 0 ? $perPage : 15);
        $dailyStats = $this->attendanceService->getDailyStats($date);

        $attendances = Attendance::with(['user', 'approver'])
        ->where('attendance_date', $date)
        ->whereIn('user_id', $users->pluck('id'))
        ->get()
        ->keyBy('user_id');

        // Summarize time log activities for displayed users on this date
        $timeLogCounts = TimeLog::whereIn('user_id', $users->pluck('id'))
        ->whereDate('start_time', $date)
        ->selectRaw('user_id, count(*) as count, sum(duration) as total_duration')
        ->groupBy('user_id')
        ->get()
        ->keyBy('user_id');

        $allUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        // Dropdown / bulk actions sirf Super Admin ko dikhenge
        $isSuperAdmin = $this->isSuperAdmin();

        return view('admin.attendance.daily', compact(
            'users',
            'attendances',
            'date',
            'dailyStats',
            'allUsers',
            'timeLogCounts',
            'selectedUserId',
            'isSuperAdmin'
        ));
    }

    /**
     * AJAX endpoint to return full daily work report & activity details for a user on a given date.
     */
    public function reportDetails(Request $request)
    {
        Gate::authorize('viewAny', Attendance::class);

        $userId = $request->input('user_id');
        $date = $this->resolveDate($request->input('date'));

        $user = User::with('role')->findOrFail($userId);

        $attendance = Attendance::with(['approver', 'logs.user'])
        ->where('user_id', $userId)
        ->where('attendance_date', $date)
        ->first();

        // Time logs / task activity on that date
        $timeLogs = TimeLog::where('user_id', $userId)
        ->whereDate('start_time', $date)
        ->with('task.project')
        ->orderBy('start_time')
        ->get()
        ->map(function ($log) {
            return [
                'id' => $log->id,
                'task_id' => $log->task_id,
                'task_title' => $log->task?->title ?? 'General Task',
                'project_name' => $log->task?->project?->name ?? 'General',
                'start_time' => $log->start_time ? $log->start_time->format('h:i A') : '--:--',
                'end_time' => $log->end_time ? $log->end_time->format('h:i A') : '--:--',
                'duration_seconds' => $log->duration,
                'formatted_duration' => gmdate('H\h i\m', $log->duration),
                'description' => $log->description,
                'mode' => $log->mode,
            ];
        });

        // Task logs / notes on that date
        $taskLogs = TaskLog::where('user_id', $userId)
        ->whereDate('created_at', $date)
        ->with('task')
        ->orderBy('created_at')
        ->get()
        ->map(function ($log) {
            return [
                'id' => $log->id,
                'task_id' => $log->task_id,
                'task_title' => $log->task?->title ?? 'General',
                'note' => $log->note,
                'type' => $log->type,
                'created_at' => $log->created_at ? $log->created_at->format('h:i A') : '',
            ];
        });

        $totalTrackedSeconds = $timeLogs->sum('duration_seconds');
        $formattedTotalTracked = gmdate('H\h i\m', $totalTrackedSeconds);

        // Sirf Super Admin approve / reject kar sakta hai
        $canApprove = $this->isSuperAdmin();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name ?? 'Employee',
                'profile_image' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
            ],
            'date' => Carbon::parse($date)->format('l, d F Y'),
            'date_raw' => $date,
            'attendance' => $attendance ? [
                'id' => $attendance->id,
                'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('h:i A') : null,
                'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('h:i A') : null,
                'working_hours' => $attendance->formatted_working_hours,
                'attendance_status' => $attendance->attendance_status,
                'approval_status' => $attendance->approval_status,
                'remarks' => $attendance->remarks,
                'submitted_at' => $attendance->submitted_at ? $attendance->submitted_at->format('d M Y, h:i A') : null,
                'approver_name' => $attendance->approver_name,
                'approved_at' => $attendance->approved_at ? Carbon::parse($attendance->approved_at)->format('d M Y, h:i A') : null,
                'rejection_reason' => $attendance->rejection_reason,
            ] : null,
            'time_logs' => $timeLogs,
            'task_logs' => $taskLogs,
            'total_tracked_seconds' => $totalTrackedSeconds,
            'total_tracked_formatted' => $formattedTotalTracked,
            'can_approve' => $canApprove,
        ]);
    }

    /**
     * Normal User Today's Attendance & Submission view.
     */
    public function myDaily(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', $this->attendanceService->today()->format('Y-m-d'));
        $today = Carbon::parse($date);
        $todayStr = $today->format('Y-m-d');

        $myAttendance = Attendance::with(['logs.user', 'approver'])
        ->where('user_id', $user->id)
        ->where(function ($q) use ($todayStr) {
            $q->where('attendance_date', $todayStr)->orWhere('date', $todayStr);
        })
        ->first();

        $search = $request->input('search');

        $timeLogsQuery = TimeLog::where('user_id', $user->id)
        ->whereDate('start_time', $todayStr)
        ->with('task.project')
        ->orderBy('start_time');

        if ($search) {
            $timeLogsQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                ->orWhereHas('task', function ($tq) use ($search) {
                    $tq->where('title', 'like', "%{$search}%");
                });
            });
        }

        $timeLogs = $timeLogsQuery->get();

        $totalTrackedSeconds = $timeLogs->sum('duration');
        $formattedTotalTracked = gmdate('H\h i\m', $totalTrackedSeconds);

        $recentAttendances = Attendance::where('user_id', $user->id)
        ->orderByDesc('attendance_date')
        ->orderByDesc('date')
        ->paginate(10, ['*'], 'history_page')
        ->withQueryString();

        return view('admin.attendance.my-daily', compact(
            'myAttendance',
            'today',
            'date',
            'timeLogs',
            'formattedTotalTracked',
            'recentAttendances'
        ));
    }

    /**
     * Super Admin Monthly Reports Aggregation view.
     */
    public function monthly(Request $request)
    {
        Gate::authorize('viewReport', Attendance::class);

        $month = $request->input('month', $this->attendanceService->today()->format('Y-m'));
        $search = $request->input('search');
        $selectedUserId = $request->input('user_id');
        if (empty($selectedUserId)) {
            $selectedUserId = null;
        }
        $status = $request->input('status');
        if (empty($status)) {
            $status = null;
        }

        $filters = [
            'search' => $search,
            'status' => $status,
        ];

        $perPage = (int) $request->input('per_page', 15);
        $users = $this->attendanceService->getMonthlyReport($month, $selectedUserId, $filters, $perPage > 0 ? $perPage : 15);
        $teamStats = $this->attendanceService->getMonthlyTeamStats($month);
        $allUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        $selectedUser = null;
        $selectedUserCalendar = [];
        $selectedUserStats = null;
        if ($selectedUserId && ! is_array($selectedUserId)) {
            $selectedUser = User::find($selectedUserId);
            if ($selectedUser) {
                $selectedUserStats = $this->attendanceService->getMonthlyUserStats($selectedUser, $month);
                $selectedUserCalendar = $this->attendanceService->getUserMonthlyCalendar($selectedUser->id, $month);
            }
        }

        return view('admin.attendance.monthly', compact(
            'users',
            'month',
            'selectedUser',
            'selectedUserStats',
            'selectedUserCalendar',
            'teamStats',
            'allUsers'
        ));
    }

    /**
     * Normal User Monthly Report view (strictly restricted to own data).
     */
    public function myMonthly(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', $this->attendanceService->today()->format('Y-m'));
        $status = $request->input('status');
        $search = $request->input('search');

        $stats = $this->attendanceService->getMonthlyUserStats($user, $month);
        $calendar = $this->attendanceService->getUserMonthlyCalendar($user->id, $month);

        $startDate = Carbon::parse("{$month}-01")->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Attendance::where('user_id', $user->id)
        ->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orWhereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        });

        if ($status) {
            if (in_array($status, ['pending', 'approved', 'rejected'])) {
                $query->where('approval_status', $status);
            } else {
                $query->where('attendance_status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                ->orWhere('attendance_date', 'like', "%{$search}%")
                ->orWhere('date', 'like', "%{$search}%");
            });
        }

        $records = $query->orderByDesc('attendance_date')
        ->orderByDesc('date')
        ->paginate(15)
        ->withQueryString();

        return view('admin.attendance.my-monthly', compact('user', 'month', 'status', 'search', 'stats', 'calendar', 'records'));
    }

    /**
     * Punch In (strictly decoupled from GPS).
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();

        try {
            $this->attendanceService->punchIn(
                $user,
                $request->input('notes') ?: $request->input('remarks'),
                $request->ip()
            );

            return redirect()->back()->with('success', 'Punched in successfully at '.$this->attendanceService->now()->format('h:i A'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Punch Out. Status draft hi rehta hai jab tak Super Admin change na kare.
     */
    public function clockOut(Request $request)
    {
        $user = Auth::user();

        try {
            $attendance = $this->attendanceService->punchOut(
                $user,
                $request->input('notes') ?: $request->input('remarks'),
                $request->ip()
            );

            return redirect()->back()->with('success', 'Punched out successfully at '.$this->attendanceService->now()->format('h:i A')." (Total: {$attendance->formatted_working_hours})");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit daily attendance report for approval.
     */
    public function submit(Request $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::findOrFail($id);

        Gate::authorize('submit', $attendance);

        try {
            $this->attendanceService->submitAttendance(
                $attendance,
                $user,
                $request->input('remarks')
            );

            return redirect()->back()->with('success', 'Daily attendance submitted successfully for approval.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit today's daily work report from the global header button.
     */
    public function submitTodayReport(Request $request)
    {
        $user = Auth::user();
        $today = $this->attendanceService->today();
        $userId = $user->getAuthIdentifier();

        $attendance = Attendance::where('user_id', $userId)
        ->where(function ($q) use ($today) {
            $q->where('attendance_date', $today->format('Y-m-d'))
            ->orWhere('date', $today->format('Y-m-d'));
        })
        ->first();

        $remarks = $request->input('remarks');

        try {
            if (! $attendance) {
                $now = $this->attendanceService->now();
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'attendance_date' => $today->format('Y-m-d'),
                    'check_in' => $now->format('H:i:s'),
                    'check_out' => $now->format('H:i:s'),
                    'working_minutes' => 480,
                    'attendance_status' => 'present',
                    'approval_status' => 'draft',
                    'remarks' => $remarks,
                ]);
            }

            Gate::authorize('submit', $attendance);

            if ($attendance->isApproved()) {
                return redirect()->back()->with('error', 'Your daily report for today has already been approved by Super Admin.');
            }

            if ($attendance->isRejected()) {
                $this->attendanceService->resubmitAttendance($attendance, $user, $remarks);

                return redirect()->back()->with('success', 'Corrected daily work report resubmitted successfully for Super Admin approval.');
            }

            $this->attendanceService->submitAttendance($attendance, $user, $remarks);

            return redirect()->back()->with('success', 'Daily work report submitted successfully for Super Admin approval.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin approve daily report.
     */
    public function approve(Request $request, $id)
    {
        $this->ensureSuperAdmin();

        $admin = Auth::user();
        $attendance = Attendance::findOrFail($id);

        Gate::authorize('approve', $attendance);

        try {
            $this->attendanceService->approveAttendance($attendance, $admin);

            return redirect()->back()->with('success', "Attendance report for {$attendance->user->name} approved successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin reject daily report with mandatory reason.
     */
    public function reject(Request $request, $id)
    {
        $this->ensureSuperAdmin();

        $admin = Auth::user();
        $attendance = Attendance::findOrFail($id);

        Gate::authorize('reject', $attendance);

        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        try {
            $this->attendanceService->rejectAttendance(
                $attendance,
                $admin,
                $request->input('rejection_reason')
            );

            return redirect()->back()->with('success', "Attendance report for {$attendance->user->name} was rejected.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * User resubmits corrected daily report.
     */
    public function resubmit(Request $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::findOrFail($id);

        Gate::authorize('resubmit', $attendance);

        try {
            $this->attendanceService->resubmitAttendance(
                $attendance,
                $user,
                $request->input('remarks')
            );

            return redirect()->back()->with('success', 'Your corrected attendance report has been resubmitted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin bulk approve reports.
     */
    public function bulkApprove(Request $request)
    {
        $this->ensureSuperAdmin();

        $admin = Auth::user();
        Gate::authorize('bulkApprove', Attendance::class);

        $attendanceIds = $request->input('attendance_ids', []);
        $userIds = $request->input('ids', []);
        if (empty($attendanceIds) && ! empty($userIds)) {
            $request->merge(['action' => 'approve']);

            return $this->bulkAction($request);
        }

        if (is_string($attendanceIds)) {
            $attendanceIds = explode(',', $attendanceIds);
        }

        $result = $this->attendanceService->bulkApprove($attendanceIds, $admin);

        return redirect()->back()->with('success', "Bulk approval complete: {$result['approved']} reports approved, {$result['skipped']} skipped.");
    }

    /**
     * Super Admin bulk action for daily attendance & reports.
     * Supported actions: approve, reject, mark_present, mark_absent.
     */
    public function bulkAction(Request $request)
    {
        $this->ensureSuperAdmin();

        $admin = Auth::user();
        Gate::authorize('bulkApprove', Attendance::class);

        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array) $ids));

        if (empty($ids)) {
            return back()->with('error', 'No employees selected.');
        }

        $action = $request->input('action');
        $date = $this->resolveDate($request->input('date'));

        $reason = $request->input('rejection_reason', 'Bulk action applied by administrator.');
        $adminType = ($admin instanceof \App\Models\Admin) ? 'admin' : 'user';

        $count = 0;
        foreach ($ids as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $attendance = Attendance::firstOrNew([
                'user_id' => $userId,
                'attendance_date' => $date,
            ]);

            switch ($action) {
                case 'approve':
                if (! $attendance->exists) {
                    $attendance->attendance_status = 'present';
                    $attendance->check_in = '09:00:00';
                    $attendance->check_out = '18:00:00';
                    $attendance->working_minutes = 540;
                }
                $attendance->approval_status = 'approved';
                $attendance->approved_at = now();
                $attendance->approved_by = $admin->id;
                $attendance->approved_by_type = $adminType;
                $attendance->rejection_reason = null;
                $attendance->save();

                $this->attendanceService->logActivity(
                    $attendance,
                    $admin,
                    'attendance.approved',
                    "Bulk approved attendance report for {$user->name} on {$date}."
                );
                $count++;
                break;

                case 'reject':
                if (! $attendance->exists) {
                    $attendance->attendance_status = 'absent';
                    $attendance->working_minutes = 0;
                }
                $attendance->approval_status = 'rejected';
                $attendance->rejection_reason = $reason;
                $attendance->approved_at = null;
                $attendance->approved_by = null;
                $attendance->approved_by_type = null;
                $attendance->save();

                $this->attendanceService->logActivity(
                    $attendance,
                    $admin,
                    'attendance.rejected',
                    "Bulk rejected report for {$user->name} on {$date}: {$reason}"
                );
                $count++;
                break;

                case 'mark_present':
                if (! $attendance->exists) {
                    $attendance->check_in = '09:00:00';
                    $attendance->check_out = '18:00:00';
                    $attendance->working_minutes = 540;
                }
                $attendance->attendance_status = 'present';
                $attendance->approval_status = 'approved';
                $attendance->approved_at = now();
                $attendance->approved_by = $admin->id;
                $attendance->approved_by_type = $adminType;
                $attendance->rejection_reason = null;
                $attendance->save();

                $this->attendanceService->logActivity(
                    $attendance,
                    $admin,
                    'attendance.marked_present',
                    "Marked present in bulk for {$user->name} on {$date}."
                );
                $count++;
                break;

                case 'mark_absent':
                $attendance->attendance_status = 'absent';
                $attendance->working_minutes = 0;
                $attendance->approval_status = 'approved';
                $attendance->approved_at = now();
                $attendance->approved_by = $admin->id;
                $attendance->approved_by_type = $adminType;
                $attendance->save();

                $this->attendanceService->logActivity(
                    $attendance,
                    $admin,
                    'attendance.marked_absent',
                    "Marked absent in bulk for {$user->name} on {$date}."
                );
                $count++;
                break;

                default:
                return back()->with('error', 'Invalid bulk action specified.');
            }
        }

        return redirect()->back()->with('success', "Bulk action '{$action}' applied successfully to {$count} employees.");
    }

    /**
     * Super Admin attendance manual correction.
     * Naya record hamesha `draft` se start hota hai.
     */
    public function updateDailyAttendance(Request $request)
    {
        $admin = Auth::user();
        Gate::authorize('update', Attendance::class);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'attendance_status' => 'required|in:present,absent,half_day,Present,Late,Half-Day,Absent',
            'approval_status' => 'nullable|in:draft,pending,approved,rejected',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'remarks' => 'nullable|string|max:1000',
            'correction_reason' => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::firstOrNew([
            'user_id' => $request->user_id,
            'attendance_date' => $request->date,
        ]);

        $statusMapped = match (strtolower($request->attendance_status)) {
            'absent' => 'absent',
            'half-day', 'half_day' => 'half_day',
            default => 'present',
        };

        $data = [
            'attendance_status' => $statusMapped,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'remarks' => $request->remarks,
        ];

        // Approval status sirf Super Admin badal sakta hai
        if ($this->isSuperAdmin() && $request->filled('approval_status')) {
            $data['approval_status'] = $request->approval_status;
        }

        if (! $attendance->exists) {
            $attendance->user_id = $request->user_id;
            $attendance->attendance_date = $request->date;
            $attendance->approval_status = 'draft';
            $attendance->fill(collect($data)->except('approval_status')->all());

            if ($attendance->check_in && $attendance->check_out) {
                $attendance->working_minutes = $this->attendanceService->calculateWorkingMinutes(
                    $attendance->check_in,
                    $attendance->check_out,
                    $request->date
                );
            } else {
                $attendance->working_minutes = $attendance->working_minutes ?? 0;
            }
            $attendance->save();

            $this->attendanceService->logActivity(
                $attendance,
                $admin,
                'attendance.created',
                "Attendance record manually created by {$admin->name}.",
                ['created_by' => $admin->id]
            );

            if (isset($data['approval_status']) && $data['approval_status'] !== 'draft') {
                $this->attendanceService->setApprovalStatus(
                    $attendance,
                    $data['approval_status'],
                    $admin,
                    $request->input('correction_reason')
                );
            }
        } else {
            $this->attendanceService->correctAttendance(
                $attendance,
                $data,
                $admin,
                $request->input('correction_reason')
            );
        }

        return redirect()->back()->with('success', 'Attendance record saved successfully.');
    }

    /**
     * Daily page ke dropdown se approval status badlo (Super Admin only).
     * Record na ho (Not Submitted) to pehle draft record banta hai, phir status set hota hai.
     */
    public function changeApprovalStatus(Request $request)
    {
        $this->ensureSuperAdmin();
        $actor = Auth::user();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'approval_status' => 'required|in:draft,pending,approved,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $newStatus = $validated['approval_status'];

        if ($newStatus === 'rejected' && blank($validated['rejection_reason'] ?? null)) {
            return back()->with('error', 'Rejection reason is required.');
        }

        $attendance = Attendance::firstOrNew([
            'user_id' => $validated['user_id'],
            'attendance_date' => $date,
        ]);

        if (! $attendance->exists) {
            $attendance->attendance_status = 'absent';
            $attendance->working_minutes = 0;
            $attendance->approval_status = 'draft';
            $attendance->save();

            $this->attendanceService->logActivity(
                $attendance,
                $actor,
                'attendance.created',
                "Attendance record created by {$actor->name} while changing approval status."
            );
        }

        $oldStatus = $attendance->approval_status ?: 'draft';
        if ($oldStatus === $newStatus) {
            return back()->with('success', 'Status is already '.ucfirst($newStatus).'.');
        }

        try {
            $this->attendanceService->setApprovalStatus(
                $attendance,
                $newStatus,
                $actor,
                $validated['rejection_reason'] ?? null
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Approval status for {$attendance->user->name} changed from "
            .ucfirst($oldStatus).' to '.ucfirst($newStatus).'.');
    }

    /**
     * Update attendance approval status by attendance id (Super Admin only).
     */
    public function updateApprovalStatus(Request $request, $attendance)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'approval_status' => 'required|in:draft,pending,approved,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $attendance = Attendance::findOrFail($attendance);
        $oldStatus = $attendance->approval_status ?: 'draft';

        if ($oldStatus === $validated['approval_status']) {
            return back()->with('success', 'Attendance status is already '.ucfirst($oldStatus).'.');
        }

        try {
            $this->attendanceService->setApprovalStatus(
                $attendance,
                $validated['approval_status'],
                Auth::user(),
                $validated['rejection_reason'] ?? null
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Attendance status updated to '.ucfirst($validated['approval_status']).'.');
    }

    /**
     * View audit history logs for an attendance record.
     */
    public function history($id)
    {
        $attendance = Attendance::with(['logs.user', 'user'])->findOrFail($id);

        Gate::authorize('view', $attendance);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'attendance' => $attendance,
                'logs' => $attendance->logs,
            ]);
        }

        return view('admin.attendance.history', compact('attendance'));
    }

    /**
     * CSV Export.
     */
    public function reports(Request $request)
    {
        $users = User::all();

        if ($request->has('export')) {
            Gate::authorize('export', Attendance::class);

            $exportType = $request->input('export');
            $month = $request->input('month', $this->attendanceService->today()->format('Y-m'));
            $userId = $request->input('user_id');

            if ($exportType === 'daily') {
                $date = $this->resolveDate($request->input('date'));

                return $this->attendanceService->exportDailyCsv($date, $request->all());
            }

            $monthlyUserId = is_array($userId) ? null : $userId;

            return $this->attendanceService->exportMonthlyCsv($month, $request->all(), $monthlyUserId && $monthlyUserId !== 'all' ? (int) $monthlyUserId : null);
        }

        return view('admin.attendance.reports', compact('users'));
    }

    /**
     * Settings Page.
     */
    public function settings()
    {
        Gate::authorize('manage', Attendance::class);

        $officeStartTime = Setting::where('key', 'office_start_time')->value('value') ?? '09:15';
        $officeEndTime = Setting::where('key', 'office_end_time')->value('value') ?? '18:00';
        $workingDays = Setting::where('key', 'working_days')->value('value') ?? '5';
        $allowedIps = Setting::where('key', 'allowed_ips')->value('value') ?? '';

        return view('admin.attendance.settings', compact('officeStartTime', 'officeEndTime', 'workingDays', 'allowedIps'));
    }

    /**
     * Update Settings.
     */
    public function updateSettings(Request $request)
    {
        Gate::authorize('manage', Attendance::class);

        $request->validate([
            'office_start_time' => 'required|date_format:H:i',
            'office_end_time' => 'required|date_format:H:i',
            'working_days' => 'required|in:5,6',
            'allowed_ips' => 'nullable|string',
        ]);

        Setting::updateOrCreate(['key' => 'office_start_time'], ['value' => $request->office_start_time]);
        Setting::updateOrCreate(['key' => 'office_end_time'], ['value' => $request->office_end_time]);
        Setting::updateOrCreate(['key' => 'working_days'], ['value' => $request->working_days]);
        Setting::updateOrCreate(['key' => 'allowed_ips'], ['value' => $request->allowed_ips]);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Calendar view.
     */
    public function calendar(Request $request)
    {
        $userId = Auth::id();
        if (Auth::user()->hasPermission('attendance.calendar_all') || Auth::user()->hasRole('super-admin')) {
            $userId = $request->input('user_id', Auth::id());
        }

        $events = [];

        if ($userId === 'all') {
            $attendances = Attendance::with('user')->get();
            $requests = AttendanceRequest::with(['user', 'actionBy'])->get();
            $selectedUser = null;
        } else {
            $attendances = Attendance::with('user')->where('user_id', $userId)->get();
            $requests = AttendanceRequest::with(['user', 'actionBy'])->where('user_id', $userId)->get();
            $selectedUser = User::find($userId);
        }

        // 1. Process Attendance Logs
        foreach ($attendances as $att) {
            $color = match ($att->attendance_status) {
                'present' => '#10b981',
                'half_day' => '#0ea5e9',
                'absent' => '#ef4444',
                default => '#6b7280',
            };

            $titlePrefix = ($userId === 'all' && $att->user) ? "{$att->user->name} - " : '';
            $title = $titlePrefix.ucfirst(str_replace('_', ' ', $att->attendance_status)).($att->check_in ? ' ('.Carbon::parse($att->check_in)->format('h:i A').')' : '');
            if ($att->approval_status === 'pending') {
                $title .= ' [Pending]';
            } elseif ($att->approval_status === 'rejected') {
                $title .= ' [Rejected]';
            } elseif ($att->approval_status === 'draft') {
                $title .= ' [Draft]';
            }

            $events[] = [
                'id' => 'att_'.$att->id,
                'title' => $title,
                'start' => $att->attendance_date?->format('Y-m-d') ?? Carbon::parse($att->date)->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'allDay' => true,
                'extendedProps' => [
                    'is_request' => false,
                    'status' => ucfirst(str_replace('_', ' ', $att->attendance_status)),
                    'approval_status' => ucfirst($att->approval_status ?: 'draft'),
                    'check_in' => $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '--:--',
                    'check_out' => $att->check_out ? Carbon::parse($att->check_out)->format('h:i A') : '--:--',
                    'working_hours' => $att->formatted_working_hours,
                    'remarks' => $att->remarks,
                    'user_name' => $att->user?->name,
                ],
            ];
        }

        // 2. Process Leave & Regularization Requests
        foreach ($requests as $req) {
            $type = $req->type;
            $status = $req->status;

            $color = match ($status) {
                'Approved' => $type === 'Leave' ? '#8b5cf6' : ($type === 'Overtime' ? '#06b6d4' : '#10b981'),
                'Rejected' => '#ef4444',
                default => '#f59e0b',
            };

            $startDate = Carbon::parse($req->start_date);
            $endDate = $req->end_date ? Carbon::parse($req->end_date) : $startDate->copy();
            $exclusiveEndDate = $endDate->copy()->addDay()->format('Y-m-d');

            $titlePrefix = ($userId === 'all' && $req->user) ? "{$req->user->name} - " : '';
            $title = "{$titlePrefix}{$type} [{$status}]";
            if ($req->type === 'Leave' && $req->start_date !== $req->end_date && $req->end_date) {
                $days = $startDate->diffInDays($endDate) + 1;
                $title = "{$titlePrefix}{$type} ({$days}d) [{$status}]";
            }

            $events[] = [
                'id' => 'req_'.$req->id,
                'title' => $title,
                'start' => $startDate->format('Y-m-d'),
                'end' => $exclusiveEndDate,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'allDay' => true,
                'extendedProps' => [
                    'is_request' => true,
                    'type' => $req->type,
                    'status' => $req->status,
                    'reason' => $req->reason,
                    'action_notes' => $req->action_notes,
                    'start_date' => $startDate->format('d M Y'),
                    'end_date' => $endDate->format('d M Y'),
                    'action_by' => $req->actionBy?->name,
                    'user_name' => $req->user?->name,
                    'user_id' => $req->user_id,
                ],
            ];
        }

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.attendance.calendar', compact('events', 'users', 'userId', 'selectedUser'));
    }

    /**
     * Requests list (leave/regularization).
     */
    public function requests(Request $request)
    {
        $status = $request->input('status');
        $type = $request->input('type');
        $search = $request->input('search');
        $userIds = $request->input('user_id');

        $isManager = Auth::user()->hasPermission('attendance.requests_manage') || Auth::user()->hasRole('super-admin');

        $requests = AttendanceRequest::with(['user', 'actionBy'])
        ->when(! $isManager, function ($query) {
            return $query->where('user_id', Auth::id());
        })
        ->when($isManager && ! empty($userIds), function ($query) use ($userIds) {
            $ids = is_array($userIds) ? array_filter($userIds) : [$userIds];
            if (! empty($ids)) {
                $query->whereIn('user_id', $ids);
            }
        })
        ->when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->when($type, function ($query) use ($type) {
            return $query->where('type', $type);
        })
        ->when($search, function ($query) use ($search) {
            return $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(15)
        ->withQueryString();

        $allUsers = $isManager ? User::select('id', 'name', 'email')->orderBy('name')->get() : collect();

        return view('admin.attendance.requests', compact('requests', 'allUsers'));
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Leave,Regularization,Overtime',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        AttendanceRequest::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Request submitted successfully.');
    }

    public function updateRequest(Request $request, $id)
    {
        $attendanceReq = AttendanceRequest::findOrFail($id);

        if ($attendanceReq->user_id !== Auth::id() && ! Auth::user()->hasPermission('attendance.requests_manage') && ! Auth::user()->hasRole('super-admin')) {
            return redirect()->back()->with('error', 'Unauthorized to edit this request.');
        }

        if ($attendanceReq->status !== 'Pending') {
            return redirect()->back()->with('error', 'Cannot edit a request that has already been processed.');
        }

        $validated = $request->validate([
            'type' => 'required|in:Leave,Regularization,Overtime',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $attendanceReq->update($validated);

        return redirect()->back()->with('success', 'Request updated successfully.');
    }

    public function updateRequestStatus(Request $request, $id)
    {
        $attendanceReq = AttendanceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'action_notes' => 'nullable|string',
        ]);

        $attendanceReq->update([
            'status' => $validated['status'],
            'action_by' => Auth::id(),
            'action_notes' => $validated['action_notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Request status updated successfully.');
    }

    public function bulkRequestAction(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No requests selected.');
        }

        $action = $request->input('action');
        if (! in_array($action, ['Approved', 'Rejected'])) {
            return back()->with('error', 'Invalid action.');
        }

        $count = AttendanceRequest::whereIn('id', $ids)
        ->where('status', 'Pending')
        ->update([
            'status' => $action,
            'action_by' => Auth::id(),
            'action_notes' => 'Bulk '.strtolower($action),
        ]);

        return back()->with('success', "{$count} request(s) {$action} successfully.");
    }
}