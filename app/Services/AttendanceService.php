<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Notifications\AttendanceNotification;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceService
{
    /**
     * Get current application time in configured timezone.
     */
    public function now(): Carbon
    {
        return Carbon::now(config('app.timezone'));
    }

    /**
     * Get today's date in configured timezone.
     */
    public function today(): Carbon
    {
        return Carbon::today(config('app.timezone'));
    }

    /**
     * Record a punch in for the authenticated user.
     * Naya record hamesha `draft` se start hota hai.
     */
    public function punchIn(Authenticatable $user, ?string $remarks = null, ?string $ip = null): Attendance
    {
        $today = $this->today();
        $now = $this->now();
        $userId = $user->getAuthIdentifier();

        $existing = Attendance::where('user_id', $userId)
        ->where(function ($q) use ($today) {
            $q->where('attendance_date', $today->format('Y-m-d'))
            ->orWhere('date', $today->format('Y-m-d'));
        })
        ->first();

        if ($existing && $existing->check_in) {
            throw new \InvalidArgumentException('You have already punched in for today.');
        }

        return DB::transaction(function () use ($user, $userId, $today, $now, $remarks, $ip, $existing) {
            if ($existing) {
                $existing->check_in = $now->format('H:i:s');
                $existing->attendance_status = 'present';
                $existing->approval_status = $existing->approval_status ?: 'draft';
                if ($remarks) {
                    $existing->remarks = $remarks;
                }
                if ($ip) {
                    $existing->ip_address = $ip;
                }
                $existing->save();
                $attendance = $existing;
            } else {
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'attendance_date' => $today->format('Y-m-d'),
                    'check_in' => $now->format('H:i:s'),
                    'working_minutes' => 0,
                    'attendance_status' => 'present',
                    'approval_status' => 'draft',
                    'remarks' => $remarks,
                    'ip_address' => $ip,
                ]);
            }

            $this->logActivity(
                $attendance,
                $user,
                'attendance.punch_in',
                "Punched in at {$now->format('h:i A')}",
                ['check_in' => $now->format('H:i:s'), 'remarks' => $remarks],
                $ip
            );

            return $attendance;
        });
    }

    /**
     * Record a punch out. Approval status ko chhedta nahi — draft hi rehta hai
     * jab tak Super Admin change na kare.
     */
    public function punchOut(Authenticatable $user, ?string $remarks = null, ?string $ip = null): Attendance
    {
        $today = $this->today();
        $now = $this->now();
        $userId = $user->getAuthIdentifier();

        $attendance = Attendance::where('user_id', $userId)
        ->where(function ($q) use ($today) {
            $q->where('attendance_date', $today->format('Y-m-d'))
            ->orWhere('date', $today->format('Y-m-d'));
        })
        ->first();

        if (! $attendance || ! $attendance->check_in) {
            throw new \InvalidArgumentException('You must punch in before punching out.');
        }

        if ($attendance->check_out) {
            throw new \InvalidArgumentException('You have already punched out for today.');
        }

        return DB::transaction(function () use ($attendance, $user, $now, $remarks, $ip) {
            $workingMinutes = $this->calculateWorkingMinutes($attendance->check_in, $now->format('H:i:s'), $attendance->attendance_date?->format('Y-m-d'));

            $attendance->check_out = $now->format('H:i:s');
            $attendance->working_minutes = $workingMinutes;
            if ($remarks) {
                $attendance->remarks = $attendance->remarks
                ? $attendance->remarks."\n".$remarks
                : $remarks;
            }
            if ($ip) {
                $attendance->ip_address = $ip;
            }
            $attendance->save();

            $formattedHours = $attendance->formatted_working_hours;

            $this->logActivity(
                $attendance,
                $user,
                'attendance.punch_out',
                "Punched out at {$now->format('h:i A')} (Total: {$formattedHours})",
                [
                    'check_out' => $now->format('H:i:s'),
                    'working_minutes' => $workingMinutes,
                    'remarks' => $remarks,
                ],
                $ip
            );

            return $attendance;
        });
    }

    /**
     * Calculate working minutes between check in and check out server-side.
     * Accurately handles same-day and overnight time calculations.
     */
    public function calculateWorkingMinutes(?string $checkIn, ?string $checkOut, ?string $baseDate = null): int
    {
        if (! $checkIn || ! $checkOut) {
            return 0;
        }

        $dateStr = $baseDate ?: $this->today()->format('Y-m-d');
        $in = Carbon::parse("{$dateStr} {$checkIn}", config('app.timezone'));
        $out = Carbon::parse("{$dateStr} {$checkOut}", config('app.timezone'));

        if ($out->lessThan($in)) {
            $out->addDay();
        }

        $minutes = (int) $in->diffInMinutes($out, false);

        return max(0, $minutes);
    }

    /**
     * Normal user submits daily attendance report for approval.
     */
    public function submitAttendance(Attendance $attendance, Authenticatable $user, ?string $remarks = null): Attendance
    {
        $userId = $user->getAuthIdentifier();
        $isSuperAdmin = ($user instanceof Admin) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin'));

        if ($attendance->user_id !== $userId && ! $isSuperAdmin) {
            throw new AuthorizationException('Unauthorized to submit another user’s report.');
        }

        if ($attendance->isApproved()) {
            throw new \InvalidArgumentException('Approved attendance records cannot be re-submitted.');
        }

        return DB::transaction(function () use ($attendance, $user, $remarks) {
            $attendance->approval_status = 'pending';
            $attendance->submitted_at = $this->now();
            if ($remarks !== null) {
                $attendance->remarks = $remarks;
            }
            $attendance->save();

            $this->logActivity(
                $attendance,
                $user,
                'attendance.submitted',
                'Daily report submitted for approval.',
                ['remarks' => $remarks, 'submitted_at' => $attendance->submitted_at]
            );

            $user->notify(new AttendanceNotification($attendance, 'submitted'));

            return $attendance;
        });
    }

    /**
     * Super Admin approves daily attendance report.
     */
    public function approveAttendance(Attendance $attendance, Authenticatable $admin): Attendance
    {
        if ($attendance->approval_status === 'approved') {
            return $attendance;
        }

        $adminId = $admin->getAuthIdentifier();
        $adminName = $admin->name ?? 'Admin';

        return DB::transaction(function () use ($attendance, $admin, $adminId, $adminName) {
            $attendance->approval_status = 'approved';
            $attendance->approved_at = $this->now();
            $attendance->approved_by = $adminId;
            $attendance->approved_by_type = ($admin instanceof Admin) ? 'admin' : 'user';
            $attendance->rejection_reason = null;
            $attendance->save();

            $this->logActivity(
                $attendance,
                $admin,
                'attendance.approved',
                "Daily attendance report approved by {$adminName}.",
                ['approved_by' => $adminId, 'approved_at' => $attendance->approved_at]
            );

            $attendance->user?->notify(new AttendanceNotification($attendance, 'approved'));

            return $attendance;
        });
    }

    /**
     * Super Admin rejects daily attendance report with mandatory reason.
     */
    public function rejectAttendance(Attendance $attendance, Authenticatable $admin, string $reason): Attendance
    {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw new \InvalidArgumentException('A rejection reason is mandatory.');
        }

        $adminId = $admin->getAuthIdentifier();

        return DB::transaction(function () use ($attendance, $admin, $adminId, $trimmedReason) {
            $attendance->approval_status = 'rejected';
            $attendance->rejection_reason = $trimmedReason;
            $attendance->approved_at = null;
            $attendance->approved_by = null;
            $attendance->approved_by_type = null;
            $attendance->save();

            $this->logActivity(
                $attendance,
                $admin,
                'attendance.rejected',
                "Daily attendance report rejected. Reason: {$trimmedReason}",
                ['rejected_by' => $adminId, 'reason' => $trimmedReason]
            );

            $attendance->user?->notify(new AttendanceNotification($attendance, 'rejected', $trimmedReason));

            return $attendance;
        });
    }

    /**
     * Normal user resubmits corrected daily attendance report after rejection.
     */
    public function resubmitAttendance(Attendance $attendance, Authenticatable $user, ?string $remarks = null): Attendance
    {
        $userId = $user->getAuthIdentifier();
        $isSuperAdmin = ($user instanceof Admin) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin'));

        if ($attendance->user_id !== $userId && ! $isSuperAdmin) {
            throw new AuthorizationException('Unauthorized to resubmit another user’s report.');
        }

        if ($attendance->approval_status !== 'rejected' && $attendance->approval_status !== 'draft') {
            throw new \InvalidArgumentException('Only rejected or draft attendance reports can be resubmitted.');
        }

        return DB::transaction(function () use ($attendance, $user, $remarks) {
            $attendance->approval_status = 'pending';
            $attendance->submitted_at = $this->now();
            if ($remarks !== null) {
                $attendance->remarks = $remarks;
            }
            $attendance->save();

            $this->logActivity(
                $attendance,
                $user,
                'attendance.resubmitted',
                'Corrected attendance report resubmitted for approval.',
                ['remarks' => $remarks, 'submitted_at' => $attendance->submitted_at]
            );

            $user->notify(new AttendanceNotification($attendance, 'resubmitted'));

            return $attendance;
        });
    }

    /**
     * Bulk approve pending records. Skips already approved or non-pending records.
     */
    public function bulkApprove(array $attendanceIds, Authenticatable $admin): array
    {
        if (empty($attendanceIds)) {
            return ['approved' => 0, 'skipped' => 0];
        }

        return DB::transaction(function () use ($attendanceIds, $admin) {
            $attendances = Attendance::with('user')
            ->whereIn('id', $attendanceIds)
            ->where('approval_status', 'pending')
            ->get();

            $approvedCount = 0;
            foreach ($attendances as $att) {
                $this->approveAttendance($att, $admin);
                $approvedCount++;
            }

            $skippedCount = count($attendanceIds) - $approvedCount;

            return [
                'approved' => $approvedCount,
                'skipped' => $skippedCount,
            ];
        });
    }

    /**
     * Super Admin: koi bhi approval status set karo (draft / pending / approved / rejected).
     * Dropdown aur manual correction dono yahi use karte hain.
     */
    public function setApprovalStatus(Attendance $attendance, string $newStatus, Authenticatable $actor, ?string $reason = null): Attendance
    {
        if (! in_array($newStatus, ['draft', 'pending', 'approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Invalid approval status.');
        }

        $isSuperAdmin = ($actor instanceof Admin) || (method_exists($actor, 'hasRole') && $actor->hasRole('super-admin'));
        if (! $isSuperAdmin) {
            throw new AuthorizationException('Only Super Admin can change approval status.');
        }

        $oldStatus = $attendance->approval_status ?: 'draft';
        if ($oldStatus === $newStatus) {
            return $attendance;
        }

        $reason = $reason ? trim($reason) : null;
        $actorId = $actor->getAuthIdentifier();
        $actorType = ($actor instanceof Admin) ? 'admin' : 'user';

        DB::transaction(function () use ($attendance, $newStatus, $oldStatus, $reason, $actor, $actorId, $actorType) {
            $attendance->approval_status = $newStatus;

            switch ($newStatus) {
                case 'approved':
                $attendance->approved_at = $this->now();
                $attendance->approved_by = $actorId;
                $attendance->approved_by_type = $actorType;
                $attendance->rejection_reason = null;
                break;

                case 'pending':
                $attendance->approved_at = null;
                $attendance->approved_by = null;
                $attendance->approved_by_type = null;
                $attendance->submitted_at = $attendance->submitted_at ?: $this->now();
                $attendance->rejection_reason = null;
                break;

                case 'draft':
                $attendance->approved_at = null;
                $attendance->approved_by = null;
                $attendance->approved_by_type = null;
                $attendance->submitted_at = null;
                $attendance->rejection_reason = null;
                break;

                case 'rejected':
                $attendance->approved_at = null;
                $attendance->approved_by = null;
                $attendance->approved_by_type = null;
                $attendance->rejection_reason = $reason ?: 'Marked rejected by Super Admin.';
                break;
            }

            $attendance->save();

            $this->logActivity(
                $attendance,
                $actor,
                'attendance.status_changed',
                'Approval status changed from '.ucfirst($oldStatus).' to '.ucfirst($newStatus)
                .' by '.($actor->name ?? 'Super Admin')
                .($newStatus === 'rejected' ? ". Reason: {$attendance->rejection_reason}" : ''),
                ['old' => $oldStatus, 'new' => $newStatus, 'reason' => $attendance->rejection_reason]
            );
        });

        // Notification transaction ke baad — fail ho to status change na ruke
        try {
            if ($newStatus === 'approved') {
                $attendance->user?->notify(new AttendanceNotification($attendance, 'approved'));
            } elseif ($newStatus === 'rejected') {
                $attendance->user?->notify(new AttendanceNotification($attendance, 'rejected', $attendance->rejection_reason));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $attendance;
    }

    /**
     * Super Admin manual correction. Approval status yahan direct set nahi hota,
     * setApprovalStatus() se hota hai (taaki approved_by / logs sahi rahein).
     */
    public function correctAttendance(Attendance $attendance, array $data, Authenticatable $admin, ?string $reason = null): Attendance
    {
        $newApproval = $data['approval_status'] ?? null;
        $oldApproval = $attendance->approval_status ?: 'draft';

        DB::transaction(function () use ($attendance, $data, $admin, $reason) {
            $oldValues = [
                'check_in' => $attendance->check_in,
                'check_out' => $attendance->check_out,
                'working_minutes' => $attendance->working_minutes,
                'attendance_status' => $attendance->attendance_status,
                'remarks' => $attendance->remarks,
            ];

            foreach (['check_in', 'check_out', 'attendance_status', 'remarks'] as $field) {
                if (isset($data[$field])) {
                    $attendance->{$field} = $data[$field];
                }
            }

            if ($attendance->check_in && $attendance->check_out) {
                $attendance->working_minutes = $this->calculateWorkingMinutes(
                    $attendance->check_in,
                    $attendance->check_out,
                    $attendance->attendance_date?->format('Y-m-d')
                );
            }

            $attendance->save();

            $this->logActivity(
                $attendance,
                $admin,
                'attendance.updated',
                "Attendance corrected by Super Admin {$admin->name}".($reason ? ". Reason: {$reason}" : ''),
                [
                    'old' => $oldValues,
                    'new' => $attendance->only(['check_in', 'check_out', 'working_minutes', 'attendance_status', 'remarks']),
                    'correction_reason' => $reason,
                ]
            );
        });

        if ($newApproval && $newApproval !== $oldApproval) {
            $this->setApprovalStatus($attendance, $newApproval, $admin, $reason);
        }

        return $attendance;
    }

    /**
     * Log attendance activity trail.
     */
    public function logActivity(
        Attendance $attendance,
        ?Authenticatable $actor,
        string $action,
        string $description,
        ?array $metadata = null,
        ?string $ip = null
    ): AttendanceLog {
        return AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => $actor?->getAuthIdentifier() ?? $actor?->id,
            'user_type' => ($actor instanceof Admin) ? 'admin' : 'user',
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get daily statistics for a given date.
     */
    public function getDailyStats(string $date): array
    {
        $totalUsers = User::count();

        $records = Attendance::where('attendance_date', $date)->get();

        $approved = $records->where('approval_status', 'approved')->count();
        $pending = $records->where('approval_status', 'pending')->count();
        $rejected = $records->where('approval_status', 'rejected')->count();
        $draft = $records->where('approval_status', 'draft')->count();

        $submitted = $approved + $pending + $rejected;
        $notSubmitted = max(0, $totalUsers - $records->count());

        return [
            'total_users' => $totalUsers,
            'submitted' => $submitted,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'draft' => $draft,
            'not_submitted' => $notSubmitted,
        ];
    }

    /**
     * Query daily reports with server-side filters.
     */
    public function getDailyReport(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $date = $filters['date'] ?? $this->today()->format('Y-m-d');
        if ($date === 'today') {
            $date = $this->today()->format('Y-m-d');
        } elseif ($date === 'yesterday') {
            $date = $this->today()->subDay()->format('Y-m-d');
        }

        $search = $filters['search'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $attendanceStatus = $filters['attendance_status'] ?? null;
        $approvalStatus = $filters['approval_status'] ?? null;

        $query = User::query()
        ->with(['attendances' => function ($q) use ($date) {
            $q->where('attendance_date', $date);
        }])
        ->when($userId, function ($q) use ($userId) {
            if (is_array($userId)) {
                $cleanIds = array_filter($userId);
                if (! empty($cleanIds)) {
                    $q->whereIn('id', $cleanIds);
                }
            } else {
                $q->where('id', $userId);
            }
        })
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        });

        if ($attendanceStatus) {
            if ($attendanceStatus === 'absent') {
                $query->where(function ($q) use ($date) {
                    $q->whereDoesntHave('attendances', function ($sub) use ($date) {
                        $sub->where('attendance_date', $date);
                    })->orWhereHas('attendances', function ($sub) use ($date) {
                        $sub->where('attendance_date', $date)
                        ->where('attendance_status', 'absent');
                    });
                });
            } else {
                $query->whereHas('attendances', function ($q) use ($date, $attendanceStatus) {
                    $q->where('attendance_date', $date)
                    ->where('attendance_status', $attendanceStatus);
                });
            }
        }

        if ($approvalStatus) {
            if ($approvalStatus === 'not_submitted') {
                $query->whereDoesntHave('attendances', function ($q) use ($date) {
                    $q->where('attendance_date', $date);
                });
            } elseif ($approvalStatus === 'submitted') {
                $query->whereHas('attendances', function ($q) use ($date) {
                    $q->where('attendance_date', $date)
                    ->whereIn('approval_status', ['pending', 'approved', 'rejected']);
                });
            } else {
                $query->whereHas('attendances', function ($q) use ($date, $approvalStatus) {
                    $q->where('attendance_date', $date)
                    ->where('approval_status', $approvalStatus);
                });
            }
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    /**
     * Compute monthly attendance summary per user for a given month (YYYY-MM).
     */
    public function getMonthlyUserStats(Authenticatable|User $user, string $month): array
    {
        $startDate = Carbon::parse("{$month}-01", config('app.timezone'))->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $today = $this->today();

        $lastDay = $endDate->lessThan($today) ? $endDate : $today;
        $totalDaysInPeriod = max(1, $startDate->diffInDays($lastDay) + 1);

        $userId = $user->getAuthIdentifier() ?? $user->id;
        $records = Attendance::where('user_id', $userId)
        ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
        ->get();

        $presentDays = $records->where('attendance_status', 'present')->where('approval_status', 'approved')->count();
        $halfDays = $records->where('attendance_status', 'half_day')->where('approval_status', 'approved')->count();
        $absentDays = $records->where('attendance_status', 'absent')->where('approval_status', 'approved')->count();
        $pendingDays = $records->where('approval_status', 'pending')->count();
        $rejectedDays = $records->where('approval_status', 'rejected')->count();
        $approvedDays = $records->where('approval_status', 'approved')->count();

        $totalWorkingMinutes = (int) $records->where('approval_status', 'approved')->sum('working_minutes');
        $totalHours = intdiv($totalWorkingMinutes, 60);
        $remainingMinutes = $totalWorkingMinutes % 60;
        $formattedHours = "{$totalHours}h {$remainingMinutes}m";

        $effectiveDays = $presentDays + ($halfDays * 0.5);
        $attendancePercentage = round(($effectiveDays / $totalDaysInPeriod) * 100, 1);

        return [
            'user' => $user,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'pending_days' => $pendingDays,
            'rejected_days' => $rejectedDays,
            'approved_days' => $approvedDays,
            'total_working_minutes' => $totalWorkingMinutes,
            'formatted_working_hours' => $formattedHours,
            'attendance_percentage' => min(100.0, max(0.0, $attendancePercentage)),
            'total_period_days' => $totalDaysInPeriod,
        ];
    }

    /**
     * Get monthly aggregated report for all users or filtered user.
     */
    public function getMonthlyReport(string $month, int|string|array|null $userId = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (is_array($userId)) {
            $cleanIds = array_values(array_filter($userId));
        } elseif (! empty($userId)) {
            $cleanIds = [(int) $userId];
        } else {
            $cleanIds = [];
        }

        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        $startDate = Carbon::parse("{$month}-01", config('app.timezone'))->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::parse("{$month}-01", config('app.timezone'))->endOfMonth()->format('Y-m-d');

        $usersQuery = User::query()
        ->when(! empty($cleanIds), function ($q) use ($cleanIds) {
            $q->whereIn('id', $cleanIds);
        })
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($status, function ($q) use ($status, $startDate, $endDate) {
            $this->applyMonthlyStatusFilter($q, $status, $startDate, $endDate);
        })
        ->orderBy('name');

        $users = $usersQuery->paginate($perPage)->withQueryString();

        $users->getCollection()->transform(function ($user) use ($month) {
            $user->monthly_stats = $this->getMonthlyUserStats($user, $month);

            return $user;
        });

        return $users;
    }

    /**
     * Shared monthly status filter (report + CSV export).
     */
    protected function applyMonthlyStatusFilter($q, string $status, string $startDate, string $endDate): void
    {
        if ($status === 'pending') {
            $q->whereHas('attendances', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('approval_status', 'pending');
            });
        } elseif ($status === 'absent') {
            $q->whereHas('attendances', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('attendance_status', 'absent');
            });
        } elseif ($status === 'half_day') {
            $q->whereHas('attendances', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('attendance_status', 'half_day');
            });
        } elseif ($status === 'present') {
            $q->whereHas('attendances', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('attendance_status', 'present')
                ->where('approval_status', 'approved');
            });
        }
    }

    /**
     * Compute team aggregated monthly stats for a given month.
     */
    public function getMonthlyTeamStats(string $month): array
    {
        $startDate = Carbon::parse("{$month}-01", config('app.timezone'))->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $totalUsers = User::count();

        $records = Attendance::whereBetween('attendance_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ])->get();

        $presentCount = $records->where('attendance_status', 'present')->where('approval_status', 'approved')->count();
        $halfDayCount = $records->where('attendance_status', 'half_day')->where('approval_status', 'approved')->count();
        $absentCount = $records->where('attendance_status', 'absent')->where('approval_status', 'approved')->count();
        $pendingCount = $records->where('approval_status', 'pending')->count();
        $rejectedCount = $records->where('approval_status', 'rejected')->count();
        $approvedCount = $records->where('approval_status', 'approved')->count();

        $totalWorkingMinutes = (int) $records->where('approval_status', 'approved')->sum('working_minutes');
        $totalHours = intdiv($totalWorkingMinutes, 60);
        $remMinutes = $totalWorkingMinutes % 60;

        return [
            'total_users' => $totalUsers,
            'present' => $presentCount,
            'half_days' => $halfDayCount,
            'absent' => $absentCount,
            'pending' => $pendingCount,
            'rejected' => $rejectedCount,
            'approved' => $approvedCount,
            'formatted_hours' => "{$totalHours}h {$remMinutes}m",
        ];
    }

    /**
     * Generate calendar matrix for a user for a given month.
     */
    public function getUserMonthlyCalendar(int $userId, string $month): array
    {
        $startDate = Carbon::parse("{$month}-01", config('app.timezone'))->startOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $attendances = Attendance::where('user_id', $userId)
        ->whereBetween('attendance_date', [
            $startDate->format('Y-m-d'),
            $startDate->copy()->endOfMonth()->format('Y-m-d'),
        ])
        ->get()
        ->keyBy(fn ($item) => $item->attendance_date?->format('Y-m-d') ?? Carbon::parse($item->date)->format('Y-m-d'));

        $calendarDays = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startDate->copy()->day($day);
            $dateStr = $date->format('Y-m-d');
            $att = $attendances->get($dateStr);

            $badge = '-';
            $label = 'No Record';
            $statusClass = 'secondary';

            if ($att) {
                if ($att->attendance_status === 'present') {
                    $badge = 'P';
                    $label = 'Present';
                    $statusClass = $att->approval_status === 'approved' ? 'success' : 'info';
                } elseif ($att->attendance_status === 'half_day') {
                    $badge = 'H';
                    $label = 'Half Day';
                    $statusClass = 'warning';
                } elseif ($att->attendance_status === 'absent') {
                    $badge = 'A';
                    $label = 'Absent';
                    $statusClass = 'danger';
                }

                if ($att->approval_status === 'rejected') {
                    $label .= ' (Rejected)';
                    $statusClass = 'danger';
                } elseif ($att->approval_status === 'pending') {
                    $label .= ' (Pending Approval)';
                }
            }

            $calendarDays[] = [
                'day' => $day,
                'date' => $dateStr,
                'day_name' => $date->format('D'),
                'is_weekend' => $date->isWeekend(),
                'attendance' => $att,
                'badge' => $badge,
                'label' => $label,
                'class' => $statusClass,
            ];
        }

        return $calendarDays;
    }

    /**
     * Stream CSV export for daily attendance (filters ke saath).
     */
    public function exportDailyCsv(string $date, array $filters = []): StreamedResponse
    {
        $userIds = $filters['user_id'] ?? null;
        $userIds = is_array($userIds) ? array_filter($userIds) : array_filter([$userIds]);
        $search = $filters['search'] ?? null;
        $approvalStatus = $filters['approval_status'] ?? null;
        $attendanceStatus = $filters['attendance_status'] ?? null;

        $users = User::with(['attendances' => function ($q) use ($date) {
            $q->where('attendance_date', $date);
        }])
        ->when(! empty($userIds), fn ($q) => $q->whereIn('id', $userIds))
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->orderBy('name')
        ->get()
        ->filter(function ($user) use ($approvalStatus, $attendanceStatus) {
            $att = $user->attendances->first();

            if ($approvalStatus) {
                $current = $att ? ($att->approval_status ?: 'draft') : 'not_submitted';
                if ($approvalStatus === 'submitted') {
                    if (! in_array($current, ['pending', 'approved', 'rejected'], true)) {
                        return false;
                    }
                } elseif ($current !== $approvalStatus) {
                    return false;
                }
            }

            if ($attendanceStatus) {
                $currentAtt = $att ? $att->attendance_status : 'absent';
                if ($currentAtt !== $attendanceStatus) {
                    return false;
                }
            }

            return true;
        });

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"daily_attendance_{$date}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($users, $date) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Name',
                'Email',
                'Date',
                'Check In',
                'Check Out',
                'Working Hours',
                'Attendance Status',
                'Approval Status',
                'Remarks',
                'Rejection Reason',
            ]);

            foreach ($users as $user) {
                $att = $user->attendances->first();

                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $date,
                    $att && $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '-',
                    $att && $att->check_out ? Carbon::parse($att->check_out)->format('h:i A') : '-',
                    $att ? $att->formatted_working_hours : '0h 0m',
                    $att ? ucfirst(str_replace('_', ' ', $att->attendance_status)) : 'Absent (Not Submitted)',
                    $att ? ucfirst($att->approval_status ?: 'draft') : 'Not Submitted',
                    $att ? ($att->remarks ?? '-') : '-',
                    $att ? ($att->rejection_reason ?? '-') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Stream CSV export for monthly attendance report.
     */
    public function exportMonthlyCsv(string $month, array $filters = [], ?int $userId = null): StreamedResponse
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        $startDate = Carbon::parse("{$month}-01", config('app.timezone'))->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::parse("{$month}-01", config('app.timezone'))->endOfMonth()->format('Y-m-d');

        $usersQuery = User::query()
        ->when($userId, fn ($q) => $q->where('id', $userId))
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($status, function ($q) use ($status, $startDate, $endDate) {
            $this->applyMonthlyStatusFilter($q, $status, $startDate, $endDate);
        })
        ->orderBy('name');

        $users = $usersQuery->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"monthly_attendance_{$month}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($users, $month) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Name',
                'Email',
                'Month',
                'Present Days',
                'Half Days',
                'Absent Days',
                'Pending Days',
                'Rejected Days',
                'Approved Days',
                'Total Working Hours',
                'Attendance Percentage',
            ]);

            foreach ($users as $user) {
                $stats = $this->getMonthlyUserStats($user, $month);

                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $month,
                    $stats['present_days'],
                    $stats['half_days'],
                    $stats['absent_days'],
                    $stats['pending_days'],
                    $stats['rejected_days'],
                    $stats['approved_days'],
                    $stats['formatted_working_hours'],
                    $stats['attendance_percentage'].'%',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}