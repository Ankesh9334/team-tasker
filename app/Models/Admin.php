<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function hasPermission($permission)
    {
        // Admins table users have all permissions implicitly
        return true;
    }

    public function hasRole($slug)
    {
        // Admins act as super-admin/manager
        if ($slug === 'client') {
            return false;
        }

        return in_array($slug, ['admin', 'super-admin', 'manager']);
    }

    public function conversations()
    {
        return $this->belongsToMany(\App\Models\Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    // Relationships for Blocking
    public function blocking()
    {
        return $this->belongsToMany(\App\Models\User::class, 'blocked_users', 'blocker_id', 'blocked_id');
    }

    public function blockedBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'blocked_users', 'blocked_id', 'blocker_id');
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
