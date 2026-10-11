<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'reply_to_id', 'user_id', 'client_id', 'body', 'device_type',
        'is_forwarded', 'reactions', 'attachment_path', 'attachment_type',
        'attachment_original_name', 'delivered_at', 'read_at', 'deleted_at',
    ];

    protected $casts = [
        'is_forwarded' => 'boolean',
        'reactions' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['is_read'];

    public function getIsReadAttribute()
    {
        return ! is_null($this->read_at);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with(['user', 'client']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
