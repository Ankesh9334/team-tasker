<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\EmployeeSalary;
use App\Models\Holiday;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Salary rule:
 *   ek din ki salary = monthly_salary / mahine ke scheduled working days
 *   absent / unpaid leave = poora din katega, half day = aadha din katega
 *   present, paid leave, weekly off, company holiday = kuch nahi katega (paid)
 *   (company holiday aur weekly off mein leave ho to bhi alag se nahi ginte)
 *   final = monthly_salary - attendance deduction - other deductions + bonus
 *
 * Saara paisa paise (integer) mein calculate hota hai, taaki float/rounding ka farak na aaye.
 * ISO weekdays: Monday = 1, Sunday = 7.
 */
class PayrollCalculationService
{
    /** Testing ke liye true karo: adhoora mahina bhi chalega, aaj ya aane wale din ki katauti nahi hogi. */
    private const ALLOW_INCOMPLETE_MONTH = false;

    /**
     * true: agar kisi working din ki attendance abhi approve nahi hui (pending/draft),
     * to payroll nahi banega. Warna wo din galti se "absent" ginke salary kat jati.
     */
    private const BLOCK_ON_UNAPPROVED_ATTENDANCE = true;

    /** Isse zyada kaam kiya to overtime hours record hote hain (paid nahi hote). */
    private const STANDARD_WORKDAY_MINUTES = 480;

    /** Har din ki katauti "half-day units" mein: poora din = 2, half day = 1. */
    private const UNITS = [
        'present' => 0, 'paid_leave' => 0, 'weekly_off' => 0, 'holiday' => 0,
        'half_day' => 1, 'absent' => 2, 'unpaid_leave' => 2,
    ];

    /**
     * Generate or regenerate a draft payroll for one employee.
     *
     * - Sirf approved attendance ginti mein aati hai.
     * - Working din par approved attendance nahi hai => Absent, poora din katega.
     * - Overtime record hota hai, paid nahi hota.
     * - Regenerate par admin ka bonus / other deductions bacha rehta hai.
     */
    public function generate(int $userId, string $month, int $generatedBy): Payroll
    {
        // "!" zaroori hai: iske bina 29/30/31 tarikh ko mahina overflow ho jata hai.
        $monthStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Chalu mahine ke future din "absent" na ginne ke liye.
        if (! self::ALLOW_INCOMPLETE_MONTH && $monthEnd->isFuture()) {
            throw ValidationException::withMessages([
                'month' => 'Payroll for ' . $monthStart->format('F Y') . ' can be generated only after the month ends ('
                    . $monthEnd->format('d-M-Y') . ').',
            ]);
        }

        // NOTE: is_active filter nahi lagaya, kyunki purana salary record naya salary save
        // hote hi is_active=false ho jata hai, jabki purane mahine ke payroll ke liye wahi chahiye.
        $records = EmployeeSalary::query()
            ->where('user_id', $userId)
            ->whereDate('effective_from', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $monthStart->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get();

        // Rate: mahine mein jo sabse naya salary record chal raha hai (raise poore mahine par lagu).
        $salary = $records->first();

        if (! $salary) {
            throw ValidationException::withMessages([
                'user_id' => 'No active salary record found for this employee in the selected month.',
            ]);
        }

        $workingDays = array_values(array_unique(array_filter(
            array_map('intval', (array) ($salary->working_days ?? [])),
            fn ($day) => $day >= 1 && $day <= 7
        )));

        if (empty($workingDays)) {
            throw ValidationException::withMessages([
                'working_days' => 'Working days are not configured for this employee.',
            ]);
        }

        $monthlyPaise = $this->paise($salary->monthly_salary);
        if ($monthlyPaise <= 0) {
            throw ValidationException::withMessages([
                'user_id' => 'Monthly salary must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($userId, $monthStart, $monthEnd, $salary, $records, $workingDays, $monthlyPaise, $generatedBy) {
            $existingPayroll = Payroll::query()
                ->where('user_id', $userId)
                ->whereDate('payroll_month', $monthStart->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existingPayroll && in_array($existingPayroll->status, ['approved', 'paid'], true)) {
                throw ValidationException::withMessages([
                    'month' => 'Approved or paid payroll cannot be regenerated.',
                ]);
            }

            $from = $monthStart->toDateString();
            $to = $monthEnd->toDateString();

            // Company holidays: ['Y-m-d' => 'Holiday name']
            $holidays = $this->holidayDates($monthStart, $monthEnd);

            $attendanceRecords = Attendance::query()
                ->where('user_id', $userId)
                ->whereBetween('attendance_date', [$from, $to])
                ->where('approval_status', 'approved')
                ->get()
                ->keyBy(fn ($attendance) => Carbon::parse($attendance->attendance_date)->toDateString());

            // ---- Jin working dino ki attendance abhi approve nahi hui ----
            if (self::BLOCK_ON_UNAPPROVED_ATTENDANCE) {
                $pendingDates = Attendance::query()
                    ->where('user_id', $userId)
                    ->whereBetween('attendance_date', [$from, $to])
                    ->where(function ($q) {
                        $q->whereNull('approval_status')
                            ->orWhereNotIn('approval_status', ['approved', 'rejected']);
                    })
                    ->pluck('attendance_date')
                    ->map(fn ($d) => Carbon::parse($d)->toDateString())
                    ->unique()
                    ->filter(fn ($d) => in_array(Carbon::parse($d)->dayOfWeekIso, $workingDays, true))
                    ->reject(fn ($d) => $attendanceRecords->has($d) || isset($holidays[$d]));

                if ($pendingDates->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'month' => $pendingDates->count() . ' day(s) of attendance are still pending approval. '
                            . 'Please approve the attendance first, then generate payroll.',
                    ]);
                }
            }

            $leaves = $this->leaveMap($userId, $monthStart, $monthEnd);
            $today = Carbon::today();

            // ---- Din-ba-din classification ----
            $days = [];
            $scheduledDays = 0;
            $counts = ['present' => 0, 'half_day' => 0, 'absent' => 0, 'paid_leave' => 0, 'unpaid_leave' => 0, 'holiday' => 0];

            for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
                $dateString = $date->toDateString();

                // Weekly off: monthly salary mein pehle se shamil hai, kabhi nahi katta
                // (leave ya holiday is din par ho to bhi alag se nahi ginta).
                if (! in_array($date->dayOfWeekIso, $workingDays, true)) {
                    $days[] = $this->row($dateString, 'weekly_off', null, 0, 0.0, 'Weekly off as per employee schedule.');
                    continue;
                }

                // Company holiday bhi paid working din hai: denominator mein ginta hai, par katta nahi.
                $scheduledDays++;
                $isHoliday = isset($holidays[$dateString]);
                $attendance = $attendanceRecords->get($dateString);

                // Din abhi khatam nahi hua (sirf testing mode): na absent, na katauti.
                if (self::ALLOW_INCOMPLETE_MONTH && $date->gte($today) && ! $attendance && ! $isHoliday) {
                    continue;
                }

                $status = strtolower((string) ($attendance->attendance_status ?? ''));
                $minutes = (int) ($attendance->working_minutes ?? 0);
                $employed = $records->contains(fn ($r) => $this->covers($r, $date));

                if (! $employed) {
                    $type = 'unpaid_leave';
                    $row = $this->row($dateString, $type, null, 0, 0.0, 'Salary record was not active on this date (before joining / after exit).');
                } elseif ($isHoliday) {
                    $type = 'holiday';
                    $row = $this->row($dateString, $type, $attendance, $minutes, 0.0, 'Paid company holiday: ' . $holidays[$dateString]);
                } elseif ($attendance && $status === 'half_day') {
                    $type = 'half_day';
                    $row = $this->row($dateString, $type, $attendance, $minutes, 0.0, 'Approved half-day attendance.');
                } elseif ($attendance && $status !== 'absent') {
                    $type = 'present';
                    $overtime = max(0, round(($minutes - self::STANDARD_WORKDAY_MINUTES) / 60, 2));
                    $row = $this->row($dateString, $type, $attendance, $minutes, (float) $overtime, 'Approved attendance.');
                } elseif (isset($leaves[$dateString])) {
                    $type = $leaves[$dateString] === 'paid' ? 'paid_leave' : 'unpaid_leave';
                    $row = $this->row($dateString, $type, $attendance, 0, 0.0,
                        $type === 'paid_leave' ? 'Approved paid leave.' : 'Approved unpaid (personal) leave.');
                } elseif ($attendance) {
                    $type = 'absent';
                    $row = $this->row($dateString, $type, $attendance, 0, 0.0, 'Approved absent attendance.');
                } else {
                    $type = 'absent';
                    $row = $this->row($dateString, $type, null, 0, 0.0, 'Attendance not marked; counted as absent.');
                }

                $counts[$type]++;
                $days[] = $row;
            }

            if ($scheduledDays === 0) {
                throw ValidationException::withMessages([
                    'month' => 'There are no scheduled working days in the selected month.',
                ]);
            }

            // ---- Katauti (paise mein) ----
            $units = array_sum(array_column($days, 'units'));
            $deductionPaise = (int) round($monthlyPaise * $units / (2 * $scheduledDays));
            $dailyRatePaise = (int) round($monthlyPaise / $scheduledDays);

            // Har din ki katauti. Rounding ka farak aakhri katne wale din mein adjust hota hai,
            // taaki din-wise total hamesha payroll ke total ke barabar rahe.
            $itemPaise = [];
            foreach ($days as $i => $row) {
                $itemPaise[$i] = $row['units'] > 0
                    ? (int) round($monthlyPaise * $row['units'] / (2 * $scheduledDays))
                    : 0;
            }
            $delta = $deductionPaise - array_sum($itemPaise);
            if ($delta !== 0) {
                foreach (array_reverse(array_keys($days)) as $i) {
                    if ($days[$i]['units'] > 0) {
                        $itemPaise[$i] += $delta;
                        break;
                    }
                }
            }

            $overtimeHours = round(array_sum(array_column($days, 'overtime_hours')), 2);

            // Regenerate par admin ke manual bonus / other deductions preserve karo.
            $bonusPaise = $this->paise($existingPayroll->bonus_amount ?? 0);
            $otherPaise = $this->paise($existingPayroll->other_deductions ?? 0);
            $finalPaise = max(0, $monthlyPaise - $deductionPaise - $otherPaise + $bonusPaise);

            $payrollData = [
                'user_id' => $userId,
                'employee_salary_id' => $salary->id,
                'payroll_month' => $monthStart->toDateString(),
                'monthly_salary' => $this->money($monthlyPaise),
                'scheduled_working_days' => $scheduledDays,
                'present_days' => $counts['present'],
                'paid_leave_days' => $counts['paid_leave'],
                'unpaid_leave_days' => $counts['unpaid_leave'],
                'absent_days' => $counts['absent'],
                'half_days' => $counts['half_day'],
                'daily_rate' => $this->money($dailyRatePaise),
                'deduction_amount' => $this->money($deductionPaise),
                'bonus_amount' => $this->money($bonusPaise),
                'other_deductions' => $this->money($otherPaise),
                'final_salary' => $this->money($finalPaise),
                'overtime_hours' => $overtimeHours,
                'status' => 'draft',
                'generated_by' => $generatedBy,
                'generated_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'paid_at' => null,
                'approval_notes' => null,
                'rejection_reason' => null,
            ];

            if ($existingPayroll) {
                $existingPayroll->update($payrollData);
                $payroll = $existingPayroll;
                $payroll->items()->delete();
            } else {
                $payroll = Payroll::create($payrollData);
            }

            foreach ($days as $i => $row) {
                $payroll->items()->create([
                    'work_date' => $row['work_date'],
                    'day_type' => $row['day_type'],
                    'daily_rate' => $this->money($dailyRatePaise),
                    'deduction_amount' => $this->money($itemPaise[$i]),
                    'working_minutes' => $row['working_minutes'],
                    'overtime_hours' => $row['overtime_hours'],
                    'attendance_id' => $row['attendance_id'],
                    'remarks' => $row['remarks'],
                ]);
            }

            return $payroll->load('items', 'user', 'employeeSalary');
        });
    }

    // ------------------------------------------------------------ hooks

    /**
     * Company holidays is range mein: ['2026-10-20' => 'Diwali', ...]
     * `holidays` table (Holiday model) se aati hain. Holiday paid hoti hai, salary nahi katti.
     */
    protected function holidayDates(Carbon $start, Carbon $end): array
    {
        if (! Schema::hasTable('holidays')) {
            return [];
        }

        $map = [];

        Holiday::query()
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$start->toDateString()])
            ->orderBy('start_date')
            ->get()
            ->each(function (Holiday $holiday) use (&$map, $start, $end) {
                $from = $holiday->start_date->copy()->startOfDay()->max($start);
                $to = ($holiday->end_date ?? $holiday->start_date)->copy()->startOfDay()->min($end);

                for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                    $map[$d->toDateString()] = $holiday->name;
                }
            });

        return $map;
    }

    /**
     * Employee ki approved leave: ['2026-10-12' => 'paid', '2026-10-13' => 'unpaid'].
     * Aapke existing `attendance_requests` table se aati hai: sirf type = 'Leave' aur status = 'Approved'.
     * (Regularization / Overtime requests leave nahi hain, unhe ignore karte hain.)
     *
     * is_paid = true  => company ki paid leave, salary nahi katti
     * is_paid = false => employee ki personal (unpaid) leave, salary katti hai
     * end_date khali ho to leave sirf start_date ke ek din ki hai.
     */
    protected function leaveMap(int $userId, Carbon $start, Carbon $end): array
    {
        if (! Schema::hasTable('attendance_requests')) {
            return [];
        }

        $hasPaidFlag = Schema::hasColumn('attendance_requests', 'is_paid');
        $map = [];

        AttendanceRequest::query()
            ->where('user_id', $userId)
            ->where('type', 'Leave')
            ->where('status', 'Approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$start->toDateString()])
            ->orderBy('id')
            ->get()
            ->each(function (AttendanceRequest $leave) use (&$map, $start, $end, $hasPaidFlag) {
                $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
                $leaveEnd = Carbon::parse($leave->end_date ?? $leave->start_date)->startOfDay();

                $from = $leaveStart->copy()->max($start);
                $to = $leaveEnd->copy()->min($end);
                $kind = ($hasPaidFlag && $leave->is_paid) ? 'paid' : 'unpaid';

                for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                    $map[$d->toDateString()] = $kind;
                }
            });

        return $map;
    }

    // ---------------------------------------------------------- helpers

    private function row(string $date, string $type, ?Attendance $attendance, int $minutes, float $overtime, string $remarks): array
    {
        return [
            'work_date' => $date,
            'day_type' => $type,
            'working_minutes' => $minutes,
            'overtime_hours' => $overtime,
            'attendance_id' => $attendance?->id,
            'remarks' => $remarks,
            'units' => self::UNITS[$type],
        ];
    }

    private function covers(EmployeeSalary $record, Carbon $date): bool
    {
        return $record->effective_from->copy()->startOfDay()->lte($date)
            && ($record->effective_until === null || $record->effective_until->copy()->startOfDay()->gte($date));
    }

    private function paise($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function money(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }
}
