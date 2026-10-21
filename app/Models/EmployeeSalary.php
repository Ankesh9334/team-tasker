<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSalary extends Model
{
    protected $fillable = [
        'user_id',
        'monthly_salary',
        'working_days',
        'effective_from',
        'effective_until',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'working_days' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
