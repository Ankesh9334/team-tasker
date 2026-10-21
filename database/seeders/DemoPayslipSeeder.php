<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\EmployeeSalary;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Demo data: 4 employees ka pichle poore mahine ka attendance + payroll + payslip.
 *
 * Run:  php artisan db:seed --class=DemoPayslipSeeder
 *
 * - Dobara run karne par demo employees ka purana data (approved/paid payroll bhi) hatakar naya banta hai.
 * - Har employee ka data ek transaction mein banta hai: beech mein error aaye to us employee ka adhoora data nahi bachta.
 * - Demo users: *.demo@coxfuture.test, password: password
 * - Production par nahi chalta.
 */
class DemoPayslipSeeder extends Seeder
{
    private Carbon $holidayFrom;

    private Carbon $holidayTo;

    public function run(PayrollCalculationService $calculator): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Demo seeder does not run in production.');

            return;
        }

        // Holiday / leave tables migration se aate hain.
        foreach (['holidays', 'attendance_requests'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->command?->error("Table {$table} is missing. Run `php artisan migrate` first, then run this seeder again.");

                return;
            }
        }

        if (! Schema::hasColumn('attendance_requests', 'is_paid')) {
            $this->command?->error('Column attendance_requests.is_paid is missing. Run `php artisan migrate` first, then run this seeder again.');

            return;
        }

        // Payment details wale columns migration se aate hain. Na hon to pehle migrate karo.
        foreach (['payment_method', 'payment_reference'] as $col) {
            if (! Schema::hasColumn('payrolls', $col)) {
                $this->command?->error("Column payrolls.{$col} is missing. Run `php artisan migrate` first, then run this seeder again.");

                return;
            }
        }

        // Pichla poora mahina (payroll sirf complete mahine ka banta hai).
        $monthStart = Carbon::today()->startOfMonth()->subMonthNoOverflow()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        // Project mein 'employee' role nahi hai (sirf super-admin, hr-manager, manager, client).
        // Demo users ko kam-se-kam permissions wala alag role dete hain: admin/HR jaisa power nahi.
        $role = Role::query()->where('slug', 'employee')->first()
        ?? Role::query()->firstOrCreate(
            ['slug' => 'demo-employee'],
            [
                'name' => 'Employee (Demo)',
                'permissions' => ['dashboard.view', 'tasks.my_tasks', 'tasks.view', 'attendance.punch', 'attendance.submit', 'chat.view'],
            ]
        );
        $admin = User::query()
        ->whereHas('role', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin']))
        ->orderBy('id')
        ->first();
        $generatedBy = (int) ($admin->id ?? User::query()->orderBy('id')->value('id'));

        // Company holiday (sab employees ke liye paid): mahine ke 8-9 tarikh.
        $this->holidayFrom = $monthStart->copy()->addDays(7)->startOfDay();
        $this->holidayTo = $monthStart->copy()->addDays(8)->startOfDay();
        Holiday::query()->where('name', 'like', '%(Demo)')->delete();
        Holiday::create([
            'name' => 'Company Holiday (Demo)',
            'start_date' => $this->holidayFrom->toDateString(),
            'end_date' => $this->holidayTo->toDateString(),
            'description' => 'Demo company holiday - paid for everyone.',
            'created_by' => $admin?->id,
        ]);

        $monSat = [1, 2, 3, 4, 5, 6];
        $monFri = [1, 2, 3, 4, 5];

        // Ordinal = us mahine ka kaunsa working din (1 = pehla working din).
        $employees = [
            [
                'name' => 'Aarav Sharma', 'email' => 'aarav.demo@coxfuture.test', 'phone' => '9810011001',
                'designation' => 'Software Engineer', 'department' => 'Engineering',
                'salary' => 60000, 'days' => $monSat, 'join_day' => 1,
                'absent_marked' => [3], 'absent_missing' => [10], 'half' => [16], 'overtime' => [5, 12, 19],
                'bonus' => 0, 'other' => 0, 'final_status' => 'paid', 'method' => 'bank_transfer',
            ],
            [
                'name' => 'Priya Verma', 'email' => 'priya.demo@coxfuture.test', 'phone' => '9810011002',
                'designation' => 'UI/UX Designer', 'department' => 'Design',
                'salary' => 35000, 'days' => $monFri, 'join_day' => 1,
                'absent_marked' => [], 'absent_missing' => [], 'half' => [], 'overtime' => [4, 9],
                'bonus' => 2000, 'other' => 0, 'final_status' => 'paid', 'method' => 'upi',
            ],
            [
                'name' => 'Rohan Gupta', 'email' => 'rohan.demo@coxfuture.test', 'phone' => '9810011003',
                'designation' => 'Sales Executive', 'department' => 'Sales',
                'salary' => 25000, 'days' => $monSat, 'join_day' => 1,
                'absent_marked' => [2, 9], 'absent_missing' => [17], 'half' => [6, 20], 'overtime' => [],
                'paid_leave' => [12], 'unpaid_leave' => [13],
                'bonus' => 0, 'other' => 500, 'final_status' => 'approved', 'method' => null,
            ],
            [
                'name' => 'Meera Joshi', 'email' => 'meera.demo@coxfuture.test', 'phone' => '9810011004',
                'designation' => 'QA Engineer', 'department' => 'Quality',
                'salary' => 30000, 'days' => $monSat, 'join_day' => 16, // mahine ki 16 tarikh ko join
                'absent_marked' => [], 'absent_missing' => [], 'half' => [], 'overtime' => [],
                'bonus' => 0, 'other' => 0, 'final_status' => 'paid', 'method' => 'bank_transfer',
            ],
        ];

        $report = [];

        foreach ($employees as $i => $e) {
            $report[] = DB::transaction(
                fn () => $this->seedEmployee($calculator, $i, $e, $monthStart, $monthEnd, $role, $admin, $generatedBy)
            );
        }

        $this->command?->info('Demo payroll for ' . $monthStart->format('F Y') . ' (expected / actual):');
        $this->command?->table(
            ['User ID', 'Employee', 'Salary', 'Working days', 'Deduction', 'Net pay', 'Check', 'Payslip'],
            $report
        );
        $this->command?->info('In the Payroll Dashboard choose month "' . $monthStart->format('F, Y') . '" to see these payrolls.');
        $this->command?->info('Demo login: <name>.demo@coxfuture.test / password');
        $this->command?->info('To test "Generate Draft Payroll", use an employee ID that is NOT in the list above.');
    }

    private function seedEmployee(
        PayrollCalculationService $calculator,
        int $i,
        array $e,
        Carbon $monthStart,
        Carbon $monthEnd,
        ?Role $role,
        ?User $admin,
        int $generatedBy
    ): array {
        $user = User::query()->updateOrCreate(
            ['email' => $e['email']],
            [
                'name' => $e['name'],
                'password' => Hash::make('password'),
                'role_id' => $role?->id,
                'is_approved' => true,
                'phone' => $e['phone'],
                'company' => 'CoxFuture Technologies Pvt Ltd',
                'email_verified_at' => now(),
            ]
        );

        // Jo extra columns aapke users table mein hain, sirf unhe bharo (payslip mein dikhenge).
        $extras = [
            'designation' => $e['designation'],
            'department' => $e['department'],
            'employee_code' => 'CXF-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'joining_date' => $monthStart->copy()->day($e['join_day'])->toDateString(),
            'pan' => 'DEMOP' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) . 'A',
            'uan' => '1000000000' . ($i + 1) . '0',
            'bank_account' => 'HDFC ****' . (5670 + $i),
        ];
        $user->forceFill(array_filter(
            $extras,
            fn ($col) => Schema::hasColumn('users', $col),
            ARRAY_FILTER_USE_KEY
        ))->save();

        // Is demo user ka purana data saaf (approved / paid bhi).
        $oldIds = Payroll::query()->where('user_id', $user->id)->pluck('id');
        PayrollItem::query()->whereIn('payroll_id', $oldIds)->delete();
        Payroll::query()->whereIn('id', $oldIds)->delete();
        Attendance::query()->where('user_id', $user->id)->delete();
        AttendanceRequest::query()->where('user_id', $user->id)->where('type', 'Leave')->delete();
        EmployeeSalary::query()->where('user_id', $user->id)->delete();

        $joinDate = $monthStart->copy()->day($e['join_day']);

        EmployeeSalary::create([
            'user_id' => $user->id,
            'monthly_salary' => $e['salary'],
            'working_days' => $e['days'],
            'effective_from' => $joinDate->toDateString(),
            'effective_until' => null,
            'is_active' => true,
            'notes' => 'Demo salary',
        ]);

        // ---- Attendance (har working din, sab approved) ----
        $ordinal = 0;
        $scheduled = 0;
        $units = 0;

        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            if (! in_array($d->dayOfWeekIso, $e['days'], true)) {
                continue;
            }

            $ordinal++;
            $scheduled++;

            if ($d->lt($joinDate)) {
                $units += 2; // joining se pehle: salary nahi milegi

                continue;
            }

            // Company holiday: paid, na attendance chahiye na katauti.
            if ($d->betweenIncluded($this->holidayFrom, $this->holidayTo)) {
                continue;
            }

            // Approved leave: paid = katauti nahi, unpaid (personal) = poora din katega.
            $paidLeave = in_array($ordinal, $e['paid_leave'] ?? [], true);
            $unpaidLeave = in_array($ordinal, $e['unpaid_leave'] ?? [], true);
            if ($paidLeave || $unpaidLeave) {
                (new AttendanceRequest())->forceFill([
                    'user_id' => $user->id,
                    'type' => 'Leave',
                    'start_date' => $d->toDateString(),
                    'end_date' => $d->toDateString(),
                    'reason' => $paidLeave ? 'Paid leave (demo)' : 'Personal leave (demo)',
                    'status' => 'Approved',
                    'action_by' => $admin?->id,
                    'action_notes' => 'Demo leave',
                    'is_paid' => $paidLeave,
                ])->save();
                $units += $unpaidLeave ? 2 : 0;

                continue;
            }

            if (in_array($ordinal, $e['absent_missing'], true)) {
                $units += 2; // attendance mark hi nahi hui

                continue;
            }

            $row = [
                'user_id' => $user->id,
                'attendance_date' => $d->toDateString(),
                'approval_status' => 'approved',
                'submitted_at' => $d->copy()->setTime(19, 0),
                'approved_at' => $d->copy()->addDay()->setTime(10, 0),
                'approved_by' => $admin?->id,
                'approved_by_type' => $admin ? 'user' : null,
            ];

            if (in_array($ordinal, $e['absent_marked'], true)) {
                $units += 2;
                $row += [
                    'attendance_status' => 'absent',
                    'working_minutes' => 0,
                    'remarks' => 'Absent (demo)',
                ];
            } else {
                $isHalf = in_array($ordinal, $e['half'], true);
                $minutes = $isHalf ? 240 : 480 + (in_array($ordinal, $e['overtime'], true) ? 90 : 0);
                $in = $d->copy()->setTime(9, 0)->addMinutes(($ordinal * 7) % 35);
                $units += $isHalf ? 1 : 0;
                $row += [
                    'attendance_status' => $isHalf ? 'half_day' : 'present',
                    'working_minutes' => $minutes,
                    'check_in' => $in->toDateTimeString(),
                    'check_out' => $in->copy()->addMinutes($minutes)->toDateTimeString(),
                    'remarks' => $isHalf ? 'Half day (demo)' : null,
                ];
            }

            Attendance::create($row);
        }

        // ---- Payroll: asli service se generate ----
        $payroll = $calculator->generate($user->id, $monthStart->format('Y-m'), $generatedBy);

        // Bonus / other deduction (admin ne manually daale).
        $monthlyPaise = $this->paise($e['salary']);
        $bonusPaise = $this->paise($e['bonus']);
        $otherPaise = $this->paise($e['other']);
        $expDed = (int) round($monthlyPaise * $units / (2 * $scheduled));
        $expNet = max(0, $monthlyPaise - $expDed + $bonusPaise - $otherPaise);

        $payroll->update([
            'bonus_amount' => $this->money($bonusPaise),
            'other_deductions' => $this->money($otherPaise),
            'final_salary' => $this->money(
                max(0, $this->paise($payroll->monthly_salary) - $this->paise($payroll->deduction_amount) + $bonusPaise - $otherPaise)
            ),
        ]);

        // ---- Approve / Paid ----
        $payroll->update([
            'status' => 'approved',
            'approved_by' => $generatedBy,
            'approved_at' => now(),
            'approval_notes' => 'Demo payroll approved.',
        ]);

        if ($e['final_status'] === 'paid') {
            $paidAt = $monthEnd->copy()->addDays(3)->setTime(11, 30);
            $payroll->update([
                'status' => 'paid',
                'paid_at' => $paidAt->isFuture() ? now() : $paidAt,
                'payment_method' => $e['method'],
                'payment_reference' => sprintf('DEMOUTR%09d', 100000000 + $user->id * 7919),
            ]);
        }

        $payroll = $payroll->fresh();
        $actDed = $this->paise($payroll->deduction_amount);
        $actNet = $this->paise($payroll->final_salary);

        return [
            $user->id,
            $e['name'],
            number_format($e['salary'], 2),
            $scheduled,
            $this->money($expDed) . ' / ' . $this->money($actDed),
            $this->money($expNet) . ' / ' . $this->money($actNet),
            ($actDed === $expDed && $actNet === $expNet) ? 'OK' : 'MISMATCH',
            Route::has('admin.payroll.payslip')
            ? route('admin.payroll.payslip', $payroll->id)
            : 'payroll id ' . $payroll->id,
        ];
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
