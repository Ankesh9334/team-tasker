<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Payroll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    /**
     * Display company holidays.
     */
    public function index(Request $request): View
    {
        $this->authorizeHoliday($request);

        $year = (int) $request->input('year', now()->year);

        $holidays = Holiday::query()
        ->whereYear('start_date', $year)
        ->orderBy('start_date')
        ->get();

        return view('admin.holidays.index', compact('holidays', 'year'));
    }

    /**
     * Store a new company holiday.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHoliday($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $holiday = Holiday::create(
            $validated + ['created_by' => $request->user()->id]
        );

        $note = $this->payrollNote($holiday);

        return redirect()
        ->route('admin.holidays.index')
        ->with(
            'success',
            'Holiday added successfully.'
            . ($note !== '' ? ' ' . $note : '')
        );
    }

    /**
     * Delete a company holiday.
     */
    public function destroy(
        Request $request,
        Holiday $holiday
    ): RedirectResponse {
        $this->authorizeHoliday($request);

        $note = $this->payrollNote($holiday);

        $holiday->delete();

        return redirect()
        ->route('admin.holidays.index')
        ->with(
            'success',
            'Holiday deleted successfully.'
            . ($note !== '' ? ' ' . $note : '')
        );
    }

    /**
     * Check whether the holiday affects existing payrolls.
     */
    private function payrollNote(Holiday $holiday): string
    {
        $startDate = $holiday->start_date;
        $endDate = $holiday->end_date ?? $startDate;

        $from = $startDate->copy()->startOfMonth()->toDateString();
        $to = $endDate->copy()->startOfMonth()->toDateString();

        $open = Payroll::query()
        ->whereBetween('payroll_month', [$from, $to])
        ->whereIn('status', ['draft', 'pending', 'rejected'])
        ->count();

        $locked = Payroll::query()
        ->whereBetween('payroll_month', [$from, $to])
        ->whereIn('status', ['approved', 'paid'])
        ->count();

        $notes = [];

        if ($open > 0) {
            $notes[] = "{$open} draft/pending/rejected payroll(s) may need regeneration.";
        }

        if ($locked > 0) {
            $notes[] = "{$locked} approved/paid payroll(s) will not be changed automatically.";
        }

        return implode(' ', $notes);
    }

    /**
     * Authorize users who can manage holidays.
     */
    private function authorizeHoliday(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, 401, 'Please log in first.');

        if ($user->hasRole('super-admin')) {
            return;
        }

        $permissions = [
            'hr.manage',
            'payroll.manage',
            'payroll.generate',
            'payroll.approve',
        ];

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return;
            }
        }

        abort(403, 'You are not authorized to manage holidays.');
    }
}
