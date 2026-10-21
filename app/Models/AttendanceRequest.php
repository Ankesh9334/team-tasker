<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'action_by',
        'action_notes',
        'is_paid',
    ];

    // Sirf is_paid cast kiya hai. start_date/end_date ko bina cast ke chhoda hai,
    // taaki aapke purane code aur emails mein dates pehle jaise hi dikhein.
    protected $casts = [
        'is_paid' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
