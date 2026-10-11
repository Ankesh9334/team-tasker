<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'deadline' => 'datetime',
        'next_occurrence_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_recurring' => 'boolean',
        'custom_fields' => 'json',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function blockedBy()
    {
        return $this->belongsTo(TaskDependency::class, 'depends_on_id');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function logs()
    {
        return $this->hasMany(TaskLog::class)->orderBy('created_at', 'desc');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class)->orderBy('created_at', 'desc');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
