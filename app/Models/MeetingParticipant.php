<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    use HasFactory;

    const ROLE_HOST = 'host';

    const ROLE_PARTICIPANT = 'participant';

    const STATUS_INVITED = 'invited';

    const STATUS_RINGING = 'ringing';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_REJECTED = 'rejected';

    const STATUS_JOINED = 'joined';

    const STATUS_LEFT = 'left';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'role',
        'status',
        'invited_at',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
