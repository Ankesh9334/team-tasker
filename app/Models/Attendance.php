<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'check_in',
        'check_out',
        'working_minutes',
        'attendance_status',
        'approval_status',
        'remarks',
        'submitted_at',
        'approved_at',
        'approved_by',
        'approved_by_type',
        'rejection_reason',
        // Legacy column compatibility
        'date',
        'clock_in',
        'clock_out',
        'status',
        'work_hours',
        'ip_address',
        'clock_in_location',
        'clock_out_location',
        'location',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'date' => 'date',
        'working_minutes' => 'integer',
        'work_hours' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($attendance) {
            // Bi-directional synchronization for column safety
            if ($attendance->attendance_date && ! $attendance->date) {
                $attendance->date = $attendance->attendance_date;
            } elseif ($attendance->date && ! $attendance->attendance_date) {
                $attendance->attendance_date = $attendance->date;
            }

            if ($attendance->check_in && ! $attendance->clock_in) {
                $attendance->clock_in = $attendance->check_in;
            } elseif ($attendance->clock_in && ! $attendance->check_in) {
                $attendance->check_in = $attendance->clock_in;
            }

            if ($attendance->check_out && ! $attendance->clock_out) {
                $attendance->clock_out = $attendance->check_out;
            } elseif ($attendance->clock_out && ! $attendance->check_out) {
                $attendance->check_out = $attendance->clock_out;
            }

            if ($attendance->remarks && ! $attendance->notes) {
                $attendance->notes = $attendance->remarks;
            } elseif ($attendance->notes && ! $attendance->remarks) {
                $attendance->remarks = $attendance->notes;
            }

            if ($attendance->working_minutes !== null && $attendance->work_hours === null) {
                $attendance->work_hours = round($attendance->working_minutes / 60, 2);
            } elseif ($attendance->work_hours !== null && (! $attendance->working_minutes || $attendance->working_minutes == 0)) {
                $attendance->working_minutes = (int) round(((float) $attendance->work_hours) * 60);
            }

            if ($attendance->attendance_status && ! $attendance->status) {
                $attendance->status = match ($attendance->attendance_status) {
                    'half_day' => 'Half-Day',
                    'absent' => 'Absent',
                    default => 'Present'
                };
            } elseif ($attendance->status && ! $attendance->attendance_status) {
                $attendance->attendance_status = match ($attendance->status) {
                    'Absent' => 'absent',
                    'Half-Day' => 'half_day',
                    default => 'present'
                };
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Resolves the approver name whether approved by a User or an Admin.
     */
    public function getApproverNameAttribute(): ?string
    {
        if (! $this->approved_by) {
            return null;
        }

        if ($this->approved_by_type === 'admin') {
            $admin = Admin::find($this->approved_by);
            if ($admin) {
                return $admin->name;
            }
        }

        if ($this->relationLoaded('approver') && $this->approver) {
            return $this->approver->name;
        }

        $user = User::find($this->approved_by);
        if ($user) {
            return $user->name;
        }

        $admin = Admin::find($this->approved_by);

        return $admin?->name;
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Formatted string of working time, e.g. "8h 15m" or "0h 0m".
     */
    public function getFormattedWorkingHoursAttribute(): string
    {
        $minutes = (int) ($this->working_minutes ?? 0);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Check if a user can edit this record.
     * Approved records are locked for normal users.
     */
    public function canBeEditedBy(Authenticatable $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission('attendance.manage')) {
            return true;
        }

        if ($this->user_id !== $user->getAuthIdentifier()) {
            return false;
        }

        // Locked once approved
        if ($this->approval_status === 'approved') {
            return false;
        }

        return true;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function isDraft(): bool
    {
        return $this->approval_status === 'draft' || empty($this->approval_status);
    }
}
