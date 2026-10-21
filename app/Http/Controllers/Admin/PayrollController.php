<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private const PAYMENT_METHODS = ['bank_transfer', 'upi', 'cheque', 'cash'];

    public function index(Request $request): View
    {
        $this->authorizePayroll('view');

        $month = $request->input('month', now()->format('Y-m'));

        try {
            $monthDate = Carbon::createFromFormat('!Y-m', $month);
        } catch (\Throwable $e) {
            $monthDate = now()->startOfMonth();
            $month = $monthDate->format('Y-m');
        }

        $payrolls = Payroll::query()
            ->with(['user', 'approver'])
            ->whereDate('payroll_month', $monthDate->toDateString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));

                $query->whereHas('user', function ($users) use ($search) {
                    $users->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Ek hi query me saari stats (pehle 5 queries thi).
        $counts = Payroll::query()
            ->whereDate('payroll_month', $monthDate->toDateString())
            ->selectRaw('status, COUNT(*) as total, SUM(final_salary) as net')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'total'    => $counts->sum('total'),
            'draft'    => (int) ($counts['draft']->total ?? 0),
            'pending'  => (int) ($counts['pending']->total ?? 0),
            'approved' => (int) ($counts['approved']->total ?? 0),
            'rejected' => (int) ($counts['rejected']->total ?? 0),
            'paid'     => (int) ($counts['paid']->total ?? 0),
            'net_payable' => (float) $counts->sum('net'),
            'net_paid'    => (float) ($counts['paid']->net ?? 0),
        ];

        return view('admin.payroll.index', compact('payrolls', 'month', 'stats'));
    }

    public function salaries(): View
    {
        $this->authorizePayroll('salary_view');

        $salaries = EmployeeSalary::query()->with('user')->latest('id')->paginate(15);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.payroll.salaries', compact('salaries', 'users'));
    }

    public function storeSalary(Request $request): RedirectResponse
    {
        $this->authorizePayroll('salary_manage');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'monthly_salary' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['required', 'integer', 'between:1,7'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workingDays = array_values(array_unique(array_map('intval', $validated['working_days'])));

        DB::transaction(function () use ($validated, $workingDays) {
            EmployeeSalary::query()
                ->where('user_id', $validated['user_id'])
                ->where('is_active', true)
                ->whereNull('effective_until')
                ->whereDate('effective_from', '<', $validated['effective_from'])
                ->update([
                    'effective_until' => Carbon::parse($validated['effective_from'])->subDay()->toDateString(),
                    'is_active' => false,
                ]);

            EmployeeSalary::create([
                'user_id' => $validated['user_id'],
                'monthly_salary' => $validated['monthly_salary'],
                'working_days' => $workingDays,
                'effective_from' => $validated['effective_from'],
                'effective_until' => $validated['effective_until'] ?? null,
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Employee salary saved successfully.');
    }

    public function generate(Request $request, PayrollCalculationService $calculator): RedirectResponse
    {
        $this->authorizePayroll('generate');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $payroll = $calculator->generate(
            (int) $validated['user_id'],
            $validated['month'],
            (int) $request->user()->id
        );

        $this->audit($payroll, 'generated');

        return redirect()
            ->route('admin.payroll.show', $payroll->id)
            ->with('success', 'Payroll generated as draft. Please review it.');
    }

    /**
     * NEW: Ek click me saare employees ka payroll generate karo.
     */
    public function bulkGenerate(Request $request, PayrollCalculationService $calculator): RedirectResponse
    {
        $this->authorizePayroll('generate');

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $created = 0;
        $skipped = [];

        $monthStart = Carbon::createFromFormat('!Y-m', $validated['month'])->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Jin employees ka salary record is month ko cover karta hai.
        $userIds = EmployeeSalary::query()
            ->whereDate('effective_from', '<=', $monthEnd->toDateString())
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $monthStart->toDateString());
            })
            ->pluck('user_id')
            ->unique();

        // Jinka payroll pehle se bana hai unhe skip karo, taaki
        // manual changes aur review ho chuke drafts overwrite na ho.
        $alreadyGenerated = Payroll::query()
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->pluck('user_id')
            ->all();

        foreach ($userIds as $userId) {
            if (in_array($userId, $alreadyGenerated, true)) {
                $skipped[] = $userId;
                continue;
            }

            try {
                $payroll = $calculator->generate((int) $userId, $validated['month'], (int) $request->user()->id);
                $this->audit($payroll, 'generated');
                $created++;
            } catch (ValidationException $e) {
                // Already generated / salary missing etc.
                $skipped[] = $userId;
            }
        }

        return back()->with(
            'success',
            "{$created} payroll generated." . (count($skipped) ? ' ' . count($skipped) . ' skipped (already exists or invalid).' : '')
        );
    }

    public function show(int $id): View
    {
        $this->authorizePayroll('view');

        $payroll = Payroll::with(['user', 'employeeSalary', 'approver', 'generator', 'items'])->findOrFail($id);

        return view('admin.payroll.show', compact('payroll'));
    }

    public function submit(int $id): RedirectResponse
    {
        $this->authorizePayroll('generate');

        return $this->transition($id, ['draft', 'rejected'], fn () => ['status' => 'pending'], 'submitted',
            'Payroll submitted for approval.', 'Only draft or rejected payroll can be submitted.');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $this->authorizePayroll('approve');

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Maker-checker: jisne generate kiya wo khud approve na kare.
        $payroll = Payroll::findOrFail($id);
        if ((int) $payroll->generated_by === (int) $user->id && !$user->hasRole('super-admin')) {
            return back()->with('error', 'You cannot approve a payroll that you generated yourself.');
        }

        return $this->transition($id, ['pending'], fn () => [
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
            'rejection_reason' => null,
        ], 'approved', 'Payroll approved successfully.', 'Only pending payroll can be approved.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->authorizePayroll('approve');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        return $this->transition($id, ['pending'], fn () => [
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => $validated['rejection_reason'],
        ], 'rejected', 'Payroll rejected. Review and regenerate it if needed.', 'Only pending payroll can be rejected.');
    }

    public function markPaid(Request $request, int $id): RedirectResponse
    {
        $this->authorizePayroll('approve');

        $validated = $request->validate([
            'payment_method' => ['required', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        return $this->transition($id, ['approved'], fn () => [
            'status' => 'paid',
            'paid_at' => isset($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now(),
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], 'paid', 'Payroll marked as paid.', 'Only approved payroll can be marked as paid.');
    }

    /**
     * NEW: Draft / rejected payroll delete (approved ya paid kabhi delete nahi hoga).
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->authorizePayroll('generate');

        $payroll = Payroll::findOrFail($id);

        if (!in_array($payroll->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Only draft or rejected payroll can be deleted.');
        }

        $this->audit($payroll, 'deleted');
        // Hard delete (payroll_items cascade ho jayenge). SoftDeletes mat lagana:
        // unique(user_id, payroll_month) ki wajah se dobara generate nahi ho paata.
        $payroll->delete();

        return redirect()->route('admin.payroll.index')->with('success', 'Payroll deleted.');
    }

    public function payslip(int $id): View|RedirectResponse
    {
        $this->authorizePayroll('payslip_view');

        $payroll = Payroll::with(['user', 'employeeSalary', 'items'])->findOrFail($id);

        // Sirf approved / paid payroll ki final payslip print hogi.
        if (!in_array($payroll->status, ['approved', 'paid'], true)) {
            return back()->with('error', 'Payslip is available only after payroll is approved.');
        }

        $amountInWords = $this->amountInWords((float) $payroll->final_salary);

        $payslipNo = 'PS-' . Carbon::parse($payroll->payroll_month)->format('Ym')
            . '-' . str_pad((string) $payroll->id, 5, '0', STR_PAD_LEFT);

        return view('admin.payroll.payslip', compact('payroll', 'amountInWords', 'payslipNo'));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Status change ko transaction + row lock me karta hai taaki
     * do admin ek saath click karein to double-approve / double-pay na ho.
     */
    private function transition(int $id, array $from, \Closure $data, string $event, string $ok, string $fail): RedirectResponse
    {
        $result = DB::transaction(function () use ($id, $from, $data, $event) {
            $payroll = Payroll::lockForUpdate()->findOrFail($id);

            if (!in_array($payroll->status, $from, true)) {
                return false;
            }

            $payroll->update($data());
            $this->audit($payroll, $event);

            return true;
        });

        return $result ? back()->with('success', $ok) : back()->with('error', $fail);
    }

    private function audit(Payroll $payroll, string $event): void
    {
        // Simple log. Better: activity_log table (spatie/laravel-activitylog).
        Log::info('payroll.' . $event, [
            'payroll_id' => $payroll->id,
            'user_id' => $payroll->user_id,
            'by' => request()->user()?->id,
            'ip' => request()->ip(),
        ]);
    }

    /** Indian numbering system me amount in words. */
    private function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven',
            'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $below100 = fn (int $n) => $n < 20 ? $ones[$n] : trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
        $below1000 = function (int $n) use ($below100, $ones) {
            $s = $n >= 100 ? $ones[intdiv($n, 100)] . ' Hundred ' : '';
            return trim($s . $below100($n % 100));
        };

        if ($rupees === 0) {
            $words = 'Zero';
        } else {
            $parts = [];
            foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $div => $label) {
                if ($rupees >= $div) {
                    $parts[] = $below1000(intdiv($rupees, $div)) . ' ' . $label;
                    $rupees %= $div;
                }
            }
            if ($rupees > 0) {
                $parts[] = $below1000($rupees);
            }
            $words = implode(' ', $parts);
        }

        $result = 'Rupees ' . $words;
        if ($paise > 0) {
            $result .= ' and ' . $below100($paise) . ' Paise';
        }

        return $result . ' Only';
    }

    private function authorizePayroll(string $permission): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return;
        }

        if (method_exists($user, 'can') && $user->can('payroll.' . $permission)) {
            return;
        }

        abort(403, 'You are not authorized to access this Payroll action.');
    }
}