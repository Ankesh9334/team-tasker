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
