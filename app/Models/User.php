<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_approved',
        'profile_image',
        'phone',
        'company',
        'fcm_token',
        'otp',
        'otp_expires_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission($permission)
    {
        if (! $this->role) {
            return false;
        }

        // Super Admin Bypass
        if (in_array(strtolower($this->role->slug), ['super-admin', 'admin'])) {
            return true;
        }

        if (is_string($permission) && str_contains($permission, '|')) {
            $permission = explode('|', $permission);
        }

        if (is_array($permission)) {
            foreach ($permission as $p) {
                if ($this->hasPermission(trim($p))) {
                    return true;
                }
            }

            return false;
        }

        $permissions = $this->role->permissions ?? [];
        if (is_array($permissions)) {
            if (in_array('*', $permissions) || in_array($permission, $permissions)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole($slug)
    {
        if (! $this->role) {
            return false;
        }

        $slug = strtolower($slug);
        $roleSlug = strtolower($this->role->slug);

        if ($slug === 'super-admin' && in_array($roleSlug, ['super-admin', 'admin'])) {
            return true;
        }

        return $roleSlug === $slug;
    }

    // Relationships for Blocking
    public function blocking()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'blocker_id', 'blocked_id');
    }

    public function blockedBy()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'blocked_id', 'blocker_id');
    }

    public function unreadChatMessagesCount()
    {
        $userId = $this->id;

        return \Illuminate\Support\Facades\DB::table('messages')
            ->join('conversation_participants', function ($join) use ($userId) {
                $join->on('messages.conversation_id', '=', 'conversation_participants.conversation_id')
                    ->where('conversation_participants.user_id', '=', $userId);
            })
            ->where('messages.user_id', '!=', $userId)
            ->where(function ($query) {
                $query->whereNull('conversation_participants.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
            })
            ->count();
    }
}
