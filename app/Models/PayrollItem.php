<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'work_date',
        'day_type',
        'daily_rate',
        'deduction_amount',
        'working_minutes',
        'overtime_hours',
        'attendance_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'daily_rate' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
