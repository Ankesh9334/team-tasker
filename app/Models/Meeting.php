<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use HasFactory;

    const TYPE_DIRECT_CALL = 'direct_call';

    const TYPE_GROUP_CALL = 'group_call';

    const TYPE_PROJECT_MEETING = 'project_meeting';

    const TYPE_TASK_MEETING = 'task_meeting';

    const TYPE_SCHEDULED_MEETING = 'scheduled_meeting';

    const MODE_AUDIO = 'audio';

    const MODE_VIDEO = 'video';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_RINGING = 'ringing';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REJECTED = 'rejected';

    const STATUS_MISSED = 'missed';

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'type',
        'mode',
        'provider',
        'room_name',
        'created_by',
        'project_id',
        'task_id',
        'scheduled_at',
        'duration',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($meeting) {
            if (empty($meeting->uuid)) {
                $meeting->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'meeting_participants')
            ->withPivot('role', 'status', 'invited_at', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeRinging($query)
    {
        return $query->where('status', self::STATUS_RINGING);
    }
}
