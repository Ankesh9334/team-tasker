<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryService
{
    /**
     * Purani active salary ko (effective_from - 1 din) par close karta hai
     * aur nayi salary create karta hai.
     */
    public function setSalary(int $userId, array $data): EmployeeSalary
    {
        $workingDays = array_values(array_unique(array_map('intval', $data['working_days'])));

        return DB::transaction(function () use ($userId, $data, $workingDays) {
            EmployeeSalary::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNull('effective_until')
            ->whereDate('effective_from', '<', $data['effective_from'])
            ->update([
                'effective_until' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
                'is_active' => false,
            ]);

            return EmployeeSalary::create([
                'user_id' => $userId,
                'monthly_salary' => $data['monthly_salary'],
                'working_days' => $workingDays,
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'] ?? null,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}