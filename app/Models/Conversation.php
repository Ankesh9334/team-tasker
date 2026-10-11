<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['type', 'name'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at', 'user_id', 'client_id')
            ->wherePivotNotNull('user_id')
            ->withTimestamps();
    }

    public function clientParticipants()
    {
        return $this->belongsToMany(Client::class, 'conversation_participants', 'conversation_id', 'client_id')
            ->withPivot('last_read_at', 'user_id', 'client_id')
            ->wherePivotNotNull('client_id')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
