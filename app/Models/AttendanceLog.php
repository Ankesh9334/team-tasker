<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'user_type',
        'action',
        'description',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolves actor name whether the actor was a User or an Admin.
     */
    public function getActorNameAttribute(): string
    {
        if (! $this->user_id) {
            return 'System';
        }

        if ($this->user_type === 'admin') {
            $admin = Admin::find($this->user_id);
            if ($admin) {
                return $admin->name;
            }
        }

        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }

        $user = User::find($this->user_id);
        if ($user) {
            return $user->name;
        }

        $admin = Admin::find($this->user_id);
        if ($admin) {
            return $admin->name;
        }

        return 'User #'.$this->user_id;
    }
}
