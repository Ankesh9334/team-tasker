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
        static::saving(function (self $a) {
            // Naye (main) aur purane (legacy) columns ko sync rakho.
            // Jo column abhi badla hai (dirty), wahi "source of truth" hai —
            // isse edit/correction ke baad purani value wapas nahi aati.
            self::syncPair($a, 'attendance_date', 'date');
            self::syncPair($a, 'check_in', 'clock_in');
            self::syncPair($a, 'check_out', 'clock_out');
            self::syncPair($a, 'remarks', 'notes');

            // working_minutes <-> work_hours
            if ($a->isDirty('working_minutes') && $a->working_minutes !== null) {
                $a->work_hours = round($a->working_minutes / 60, 2);
            } elseif ($a->isDirty('work_hours') && $a->work_hours !== null) {
                $a->working_minutes = (int) round(((float) $a->work_hours) * 60);
            } elseif ($a->work_hours === null && $a->working_minutes !== null) {
                $a->work_hours = round($a->working_minutes / 60, 2);
            } elseif (! $a->working_minutes && $a->work_hours !== null) {
                $a->working_minutes = (int) round(((float) $a->work_hours) * 60);
            }

            // attendance_status <-> status
            if ($a->isDirty('attendance_status') && $a->attendance_status) {
                $a->status = match ($a->attendance_status) {
                    'half_day' => 'Half-Day',
                    'absent' => 'Absent',
                    default => 'Present',
                };
            } elseif ($a->isDirty('status') && $a->status) {
                $a->attendance_status = match ($a->status) {
                    'Absent' => 'absent',
                    'Half-Day' => 'half_day',
                    default => 'present',
                };
            } elseif ($a->attendance_status && ! $a->status) {
                $a->status = match ($a->attendance_status) {
                    'half_day' => 'Half-Day',
                    'absent' => 'Absent',
                    default => 'Present',
                };
            } elseif ($a->status && ! $a->attendance_status) {
                $a->attendance_status = match ($a->status) {
                    'Absent' => 'absent',
                    'Half-Day' => 'half_day',
                    default => 'present',
                };
            }
        });
    }

    private static function syncPair(self $a, string $main, string $legacy): void
    {
        if ($a->isDirty($main)) {
            $a->{$legacy} = $a->{$main};
        } elseif ($a->isDirty($legacy)) {
            $a->{$main} = $a->{$legacy};
        } elseif ($a->{$main} === null && $a->{$legacy} !== null) {
            $a->{$main} = $a->{$legacy};
        } elseif ($a->{$legacy} === null && $a->{$main} !== null) {
            $a->{$legacy} = $a->{$main};
        }
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