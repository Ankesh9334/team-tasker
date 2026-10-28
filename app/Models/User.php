<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'last_seen_at',

        // Employee details
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'employee_code',
        'designation',
        'department',
        'joining_date',
        'pan_number',
        'aadhaar_number',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'aadhaar_number',
        'bank_account_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'aadhaar_number' => 'encrypted',
        'bank_account_number' => 'encrypted',
    ];

    public function getWhatsappStatusAttribute(): string
    {
        return \App\Helpers\ChatPresenceHelper::formatLastSeen(
            $this->last_seen_at
        );
    }

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
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants'
        )
        ->withPivot('last_read_at')
        ->withTimestamps();
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Salary relations
    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class)
        ->where('is_active', true)
        ->latestOfMany('effective_from');
    }

    public function hasPermission($permission)
    {
        if (! $this->role) {
            return false;
        }

        // Super Admin / Admin bypass
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
            if (
                in_array('*', $permissions) ||
                in_array($permission, $permissions)
            ) {
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

        if (
            $slug === 'super-admin' &&
            in_array($roleSlug, ['super-admin', 'admin'])
        ) {
            return true;
        }

        return $roleSlug === $slug;
    }

    // Relationships for Blocking
    public function blocking()
    {
        return $this->belongsToMany(
            User::class,
            'blocked_users',
            'blocker_id',
            'blocked_id'
        );
    }

    public function blockedBy()
    {
        return $this->belongsToMany(
            User::class,
            'blocked_users',
            'blocked_id',
            'blocker_id'
        );
    }

    public function unreadChatMessagesCount()
    {
        $userId = $this->id;

        return \Illuminate\Support\Facades\DB::table('messages')
        ->join('conversation_participants', function ($join) use ($userId) {
            $join->on(
                'messages.conversation_id',
                '=',
                'conversation_participants.conversation_id'
            )->where(
                'conversation_participants.user_id',
                '=',
                $userId
            );
        })
        ->where('messages.user_id', '!=', $userId)
        ->where(function ($query) {
            $query->whereNull('conversation_participants.last_read_at')
            ->orWhereColumn(
                'messages.created_at',
                '>',
                'conversation_participants.last_read_at'
            );
        })
        ->count();
    }
}
