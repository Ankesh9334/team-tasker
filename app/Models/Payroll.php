<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'employee_salary_id',
        'payroll_month',
        'monthly_salary',
        'scheduled_working_days',
        'present_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'absent_days',
        'half_days',
        'daily_rate',
        'deduction_amount',
        'bonus_amount',
        'other_deductions',
        'final_salary',
        'overtime_hours',
        'status',
        'generated_by',
        'approved_by',
        'generated_at',
        'approved_at',
        'paid_at',
        'payment_method',
        'payment_reference',
        'approval_notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'payroll_month' => 'date',
            'monthly_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'final_salary' => 'decimal:2',
            'half_days' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** Example: PS-202609-00012 */
    protected function payslipNo(): Attribute
    {
        return Attribute::get(fn () => 'PS-'
            . $this->payroll_month->format('Ym')
            . '-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }

    /** Approved ya paid payroll me koi change nahi hona chahiye. */
    public function isLocked(): bool
    {
        return in_array($this->status, ['approved', 'paid'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employeeSalary(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}