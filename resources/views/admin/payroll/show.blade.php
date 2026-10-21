{{-- resources/views/admin/payroll/show.blade.php --}}
@php
use Carbon\Carbon;

$monthStart = Carbon::parse($payroll->payroll_month)->startOfMonth();
$status     = $payroll->status;
$items      = $payroll->items;
$fmt        = fn($n) => '₹' . number_format((float) $n, 2);
$trim       = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.') ?: '0';

$name  = $payroll->user?->name ?? 'Employee unavailable';
$avatarColors = ['#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444', '#22c55e'];
$avatarColor  = $avatarColors[abs(crc32($name)) % count($avatarColors)];

$statusMeta = [
'draft'    => ['Draft',            '#64748b'],
'pending'  => ['Pending approval', '#d97706'],
'approved' => ['Approved',         '#0284c7'],
'rejected' => ['Rejected',         '#dc2626'],
'paid'     => ['Paid',             '#16a34a'],
];
[$sLabel, $sColor] = $statusMeta[$status] ?? ['Unknown', '#64748b'];

$editable = in_array($status, ['draft', 'rejected'], true);

// Status stepper
$stage = ['draft' => 0, 'pending' => 1, 'rejected' => 1, 'approved' => 2, 'paid' => 3][$status] ?? 0;
$steps = [
['Generated', $payroll->generated_at, $payroll->generator?->name],
[$status === 'rejected' ? 'Rejected' : 'Submitted', null, null],
['Approved',  $payroll->approved_at, $payroll->approver?->name],
['Paid',      $payroll->paid_at, null],
];

// Day types
$dayMeta = [
'present'      => ['P',  'Present',      '#16a34a', '#dcfce7'],
'half_day'     => ['H',  'Half day',     '#d97706', '#fef3c7'],
'absent'       => ['A',  'Absent',       '#dc2626', '#fee2e2'],
'paid_leave'   => ['PL', 'Paid leave',   '#2563eb', '#dbeafe'],
'unpaid_leave' => ['UL', 'Unpaid leave', '#9333ea', '#f3e8ff'],
'weekly_off'   => ['WO', 'Weekly off',   '#64748b', '#e2e8f0'],
];
$byDate     = $items->keyBy(fn($i) => Carbon::parse($i->work_date)->format('Y-m-d'));
$leadBlanks = $monthStart->dayOfWeekIso - 1;
$typeCounts = $items->groupBy('day_type')->map->count();

// Adjust feature
$adjustTypes   = ['absent', 'paid_leave', 'unpaid_leave'];
$adjustable    = $items->whereIn('day_type', $adjustTypes)->sortBy('work_date');
$fixedDeduction = (float) $items->whereNotIn('day_type', $adjustTypes)->sum('deduction_amount');
$currentAction = function ($it) {
    if ($it->day_type === 'paid_leave')   return 'paid_leave';
    if ($it->day_type === 'unpaid_leave') return 'unpaid_leave';
    return (float) $it->deduction_amount > 0 ? 'deduct' : 'keep';
};
$hasAdjustRoute = Route::has('admin.payroll.adjust');

// Maker-checker
$authUser = request()->user();
$isSuper  = $authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('super-admin');
$cannotApprove = $payroll->generated_by && $authUser && (int) $payroll->generated_by === (int) $authUser->id && !$isSuper;

$paymentMethods = ['bank_transfer' => 'Bank transfer', 'upi' => 'UPI', 'cheque' => 'Cheque', 'cash' => 'Cash'];
@endphp

<x-admin>
    <x-slot:title>Payroll Details | Team Tasker</x-slot:title>

    <style>
        .ps-page { font-size: .86rem; }
        .ps-page .card { border-radius: 14px; }
        .ps-page .form-control, .ps-page .form-select, .ps-page .input-group-text { font-size: .84rem; padding: .38rem .65rem; border-radius: 8px; }
        .ps-page .form-label { font-size: .75rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; }

        .ps-hero { background: linear-gradient(120deg, #4f46e5, #7c3aed 55%, #db2777); border-radius: 16px; color: #fff; padding: 1.1rem 1.3rem; }
        .ps-hero h1 { font-size: 1.2rem; font-weight: 700; margin: 0; color: #fff; }
        .ps-hero p { margin: 0; opacity: .88; font-size: .8rem; }
        .avatar { width: 46px; height: 46px; border-radius: 50%; color: #fff; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid rgba(255,255,255,.7); }
        .hero-pill { display: inline-block; background: #fff; font-weight: 700; font-size: .72rem; padding: .12rem .7rem; border-radius: 999px; }
        .hero-net { text-align: right; }
        .hero-net small { opacity: .85; display: block; font-size: .72rem; }
        .hero-net b { font-size: 1.7rem; line-height: 1.1; }

        .btn-x { border: 0; border-radius: 8px; font-size: .78rem; font-weight: 600; padding: .4rem .85rem; line-height: 1.3; transition: transform .12s, box-shadow .12s, filter .12s; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn-x:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.16); filter: brightness(1.06); }
        .btn-x:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-white  { background: #fff; color: #4f46e5; }
        .btn-glass  { background: rgba(255,255,255,.2); color: #fff; }
        .btn-indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; }
        .btn-green  { background: linear-gradient(135deg,#22c55e,#15803d); color: #fff; }
        .btn-red    { background: linear-gradient(135deg,#f43f5e,#be123c); color: #fff; }
        .btn-teal   { background: linear-gradient(135deg,#14b8a6,#0f766e); color: #fff; }
        .btn-amber  { background: linear-gradient(135deg,#fbbf24,#d97706); color: #fff; }
        .btn-soft   { background: rgba(99,102,241,.14); color: #6366f1; }

        /* Stepper */
        .stepper { display: flex; align-items: flex-start; }
        .step { flex: 1; text-align: center; position: relative; }
        .step::before { content: ""; position: absolute; top: 14px; left: -50%; width: 100%; height: 3px; background: rgba(100,116,139,.25); z-index: 0; }
        .step:first-child::before { display: none; }
        .step.done::before, .step.cur::before { background: #16a34a; }
        .step .dot { width: 30px; height: 30px; border-radius: 50%; margin: 0 auto .3rem; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; background: rgba(100,116,139,.25); color: #64748b; position: relative; z-index: 1; }
        .step.done .dot { background: #16a34a; color: #fff; }
        .step.cur .dot { color: #fff; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
        .step b { display: block; font-size: .78rem; }
        .step small { font-size: .68rem; opacity: .7; display: block; }

        /* Formula */
        .formula { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .formula .op { font-weight: 800; opacity: .5; }
        .fbox { border-radius: 12px; padding: .5rem .85rem; min-width: 120px; }
        .fbox small { display: block; font-size: .7rem; opacity: .85; }
        .fbox b { font-size: 1.02rem; }

        /* Tiles */
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: .5rem; }
        .tile { border-radius: 12px; padding: .55rem .8rem; color: #fff; position: relative; overflow: hidden; }
        .tile::after { content: ""; position: absolute; right: -14px; top: -14px; width: 54px; height: 54px; border-radius: 50%; background: rgba(255,255,255,.15); }
        .tile b { display: block; font-size: 1.3rem; line-height: 1.2; }
        .tile small { font-size: .7rem; opacity: .92; }

        .sec-title { font-weight: 700; display: flex; align-items: center; gap: .5rem; }
        .sec-title .ico { width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; }

        /* Calendar */
        .cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .cal .dow { text-align: center; font-size: .68rem; opacity: .7; font-weight: 600; }
        .cal .d { border-radius: 7px; padding: 2px 5px; height: 38px; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid transparent; }
        .cal .d i { font-style: normal; font-size: .62rem; opacity: .7; }
        .cal .d b { font-size: .72rem; text-align: right; }
        .cal .blank { visibility: hidden; }
        .legend { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: .5rem; font-size: .72rem; opacity: .85; }
        .legend span { display: inline-flex; align-items: center; gap: .3rem; }
        .legend em { width: 11px; height: 11px; border-radius: 3px; display: inline-block; }

        /* Tables */
        .ps-table { margin: 0; }
        .ps-table thead th { font-size: .72rem; font-weight: 600; opacity: .75; padding: .5rem .75rem; white-space: nowrap; }
        .ps-table td { padding: .45rem .75rem; vertical-align: middle; }
        .tag { display: inline-block; padding: .12rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .chips { display: flex; flex-wrap: wrap; gap: .35rem; }
        .chip { border: 0; padding: .2rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 600; background: rgba(100,116,139,.16); cursor: pointer; }
        .chip.on { background: #4f46e5; color: #fff; }
        .scroll-box { max-height: 360px; overflow-y: auto; }

        .live { border-radius: 12px; padding: .6rem .9rem; background: linear-gradient(135deg, rgba(34,197,94,.15), rgba(99,102,241,.15)); border: 1px dashed rgba(99,102,241,.45); }
        .live b { font-size: 1.05rem; }
        .note-box { border-radius: 10px; padding: .45rem .75rem; font-size: .76rem; background: rgba(245,158,11,.14); color: #b45309; }
        .ok-box { border-radius: 10px; padding: .6rem .9rem; background: rgba(34,197,94,.14); color: #15803d; font-weight: 600; }
        .action-head { height: 5px; border-radius: 14px 14px 0 0; }
    </style>

    <div class="container-fluid py-3 ps-page">

        {{-- Hero --}}
        <div class="ps-hero mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar" style="background: {{ $avatarColor }}">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                    <div>
                        <h1>{{ $name }}</h1>
                        <p>{{ $payroll->user?->email }} &nbsp;|&nbsp; {{ $monthStart->format('F Y') }} &nbsp;|&nbsp; {{ $payroll->payslip_no }}</p>
                        <span class="hero-pill mt-1" style="color: {{ $sColor }}">{{ $sLabel }}</span>
                    </div>
                </div>
                <div class="hero-net">
                    <small>Final payable salary</small>
                    <b>{{ $fmt($payroll->final_salary) }}</b>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('admin.payroll.index', ['month' => $monthStart->format('Y-m')]) }}" class="btn-x btn-glass">&larr; Back to Payroll</a>
                @if(in_array($status, ['approved', 'paid'], true))
                <a href="{{ route('admin.payroll.payslip', $payroll->id) }}" target="_blank" class="btn-x btn-white">View / Print Payslip</a>
                @endif
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))<div class="alert alert-success py-2 px-3 auto-dismiss">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2 px-3 auto-dismiss">{{ session('error') }}</div>@endif
        @if($errors->any())
        <div class="alert alert-danger py-2 px-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if($payroll->rejection_reason)
        <div class="alert alert-danger py-2 px-3"><strong>Rejection reason:</strong> {{ $payroll->rejection_reason }}</div>
        @endif
        @if($payroll->approval_notes)
        <div class="alert alert-info py-2 px-3"><strong>Approval notes:</strong> {{ $payroll->approval_notes }}</div>
        @endif

        {{-- Progress --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-3">
                <div class="stepper">
                    @foreach($steps as $i => [$label, $time, $who])
                    @php
                    $cls = $i < $stage ? 'done' : ($i === $stage ? 'cur' : '');
                    $dotStyle = $i === $stage ? 'background:' . $sColor : '';
                    @endphp
                    <div class="step {{ $cls }}">
                        <div class="dot" style="{{ $dotStyle }}">{{ $i < $stage ? '✓' : $i + 1 }}</div>
                        <b>{{ $label }}</b>
                        <small>{{ $time ? Carbon::parse($time)->format('d M, h:i A') : '' }}</small>
                        @if($who)<small>by {{ $who }}</small>@endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Salary formula --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-3">
                <div class="sec-title mb-2"><span class="ico" style="background:linear-gradient(135deg,#22c55e,#15803d)">₹</span> Salary calculation <small class="opacity-75 fw-normal ms-2">Daily rate: {{ $fmt($payroll->daily_rate) }}</small></div>
                <div class="formula">
                    <div class="fbox" style="background:rgba(99,102,241,.14);color:#4f46e5"><small>Monthly salary</small><b>{{ $fmt($payroll->monthly_salary) }}</b></div>
                    <span class="op">-</span>
                    <div class="fbox" style="background:rgba(239,68,68,.14);color:#dc2626"><small>Attendance deduction</small><b>{{ $fmt($payroll->deduction_amount) }}</b></div>
                    <span class="op">-</span>
                    <div class="fbox" style="background:rgba(239,68,68,.14);color:#dc2626"><small>Other deductions</small><b>{{ $fmt($payroll->other_deductions) }}</b></div>
                    <span class="op">+</span>
                    <div class="fbox" style="background:rgba(34,197,94,.16);color:#15803d"><small>Bonus</small><b>{{ $fmt($payroll->bonus_amount) }}</b></div>
                    <span class="op">=</span>
                    <div class="fbox" style="background:linear-gradient(135deg,#22c55e,#15803d);color:#fff"><small>Final salary</small><b>{{ $fmt($payroll->final_salary) }}</b></div>
                </div>
            </div>
        </div>

        {{-- Attendance summary --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-3">
                <div class="sec-title mb-2"><span class="ico" style="background:linear-gradient(135deg,#0ea5e9,#0369a1)">A</span> Attendance summary</div>
                <div class="tiles">
                    <div class="tile" style="background:#0ea5e9"><b>{{ $payroll->scheduled_working_days }}</b><small>Working days</small></div>
                    <div class="tile" style="background:#16a34a"><b>{{ $payroll->present_days }}</b><small>Present</small></div>
                    <div class="tile" style="background:#d97706"><b>{{ $trim($payroll->half_days) }}</b><small>Half days</small></div>
                    <div class="tile" style="background:#ef4444"><b>{{ $payroll->absent_days }}</b><small>Absent</small></div>
                    <div class="tile" style="background:#2563eb"><b>{{ $payroll->paid_leave_days }}</b><small>Paid leave</small></div>
                    <div class="tile" style="background:#9333ea"><b>{{ $payroll->unpaid_leave_days }}</b><small>Unpaid leave</small></div>
                    <div class="tile" style="background:#0f766e"><b>{{ $trim($payroll->overtime_hours) }}</b><small>Overtime hrs</small></div>
                </div>
                <small class="d-block mt-2 opacity-75">Overtime is recorded for reporting only; it is not added to salary.</small>
            </div>
        </div>

        {{-- Actions --}}
        @if($editable)
        @if($hasAdjustRoute)
        <div class="card border-0 shadow-sm mb-3">
            <div class="action-head" style="background:linear-gradient(90deg,#f59e0b,#ef4444)"></div>
            <div class="card-body px-3">
                <div class="sec-title mb-1"><span class="ico" style="background:linear-gradient(135deg,#f59e0b,#d97706)">&#9998;</span> Review &amp; adjust</div>
                <p class="opacity-75 mb-3">Decide how absent days are treated, and add a bonus or other deductions. The final salary updates live below.</p>

                <form method="POST" action="{{ route('admin.payroll.adjust', $payroll->id) }}" id="adjustForm" class="js-once"
                  data-monthly="{{ (float) $payroll->monthly_salary }}" data-fixed="{{ $fixedDeduction }}">
                  @csrf
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="bonus_amount">Bonus</label>
                        <div class="input-group"><span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" name="bonus_amount" id="bonus_amount" class="form-control" value="{{ old('bonus_amount', (float) $payroll->bonus_amount) }}" required></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="other_deductions">Other deductions</label>
                            <div class="input-group"><span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="other_deductions" id="other_deductions" class="form-control" value="{{ old('other_deductions', (float) $payroll->other_deductions) }}" required></div>
                            </div>
                        </div>

                        @if($adjustable->isEmpty())
                        <div class="ok-box mb-3">No absent or leave days in this month. Nothing to review.</div>
                        @else
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="fw-semibold">Days to review ({{ $adjustable->count() }})</div>
                            <div class="d-flex gap-1 align-items-center">
                                <select id="bulkAct" class="form-select" style="width:auto">
                                    <option value="keep">Absent - no deduction</option>
                                    <option value="deduct">Absent - deduct full day</option>
                                    <option value="paid_leave">Paid leave</option>
                                    <option value="unpaid_leave">Unpaid leave - deduct</option>
                                </select>
                                <button type="button" class="btn-x btn-soft" id="bulkApply">Apply to all</button>
                            </div>
                        </div>
                        <div class="table-responsive scroll-box border rounded mb-3">
                            <table class="table ps-table table-hover">
                                <thead><tr><th>Date</th><th>Current</th><th class="text-end">Daily rate</th><th>Treat as</th></tr></thead>
                                <tbody>
                                    @foreach($adjustable as $it)
                                    @php $m = $dayMeta[$it->day_type]; $cur = $currentAction($it); @endphp
                                    <tr>
                                        <td>{{ Carbon::parse($it->work_date)->format('D, d M Y') }}
                                            <small class="d-block opacity-75">{{ $it->attendance_id ? 'Admin-approved record' : 'No attendance found' }}</small></td>
                                            <td><span class="tag" style="background:{{ $m[3] }};color:{{ $m[2] }}">{{ $m[1] }}</span></td>
                                            <td class="text-end">{{ $fmt($it->daily_rate) }}</td>
                                            <td>
                                                <select name="days[{{ $it->id }}]" class="form-select day-act" data-rate="{{ (float) $it->daily_rate }}">
                                                    <option value="keep" @selected($cur === 'keep')>Absent - no deduction</option>
                                                    <option value="deduct" @selected($cur === 'deduct')>Absent - deduct full day</option>
                                                    <option value="paid_leave" @selected($cur === 'paid_leave')>Paid leave</option>
                                                    <option value="unpaid_leave" @selected($cur === 'unpaid_leave')>Unpaid leave - deduct</option>
                                                </select>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            <div class="live d-flex flex-wrap gap-4 align-items-center mb-3">
                                <span>Attendance deduction: <b id="lvDed" class="text-danger">-</b></span>
                                <span>New final salary: <b id="lvFinal" style="color:#15803d">-</b></span>
                            </div>

                            <button type="submit" class="btn-x btn-amber" id="adjustBtn">Save adjustments</button>
                        </form>
                    </div>
                </div>
                @else
                <div class="note-box mb-3">The adjust route (<code>admin.payroll.adjust</code>) is not registered yet, so adjustments are unavailable.</div>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="action-head" style="background:linear-gradient(90deg,#6366f1,#4f46e5)"></div>
                            <div class="card-body px-3">
                                <div class="fw-bold mb-1">Submit for approval</div>
                                <p class="opacity-75">Review the calculations, then send this payroll to the approver.</p>
                                <form method="POST" action="{{ route('admin.payroll.submit', $payroll->id) }}" class="js-once" onsubmit="return confirm('Submit this payroll for approval?');">
                                    @csrf
                                    <button class="btn-x btn-indigo" type="submit">Submit for approval</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="action-head" style="background:linear-gradient(90deg,#14b8a6,#0f766e)"></div>
                            <div class="card-body px-3">
                                <div class="fw-bold mb-1">Regenerate from attendance</div>
                                <p class="opacity-75">Recalculates from approved attendance. Bonus and other deductions are kept, but day-level adjustments are reset.</p>
                                <form method="POST" action="{{ route('admin.payroll.generate') }}" class="js-once" onsubmit="return confirm('Regenerate this payroll? Day-level adjustments will be reset.');">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $payroll->user_id }}">
                                    <input type="hidden" name="month" value="{{ $monthStart->format('Y-m') }}">
                                    <button class="btn-x btn-teal" type="submit">Regenerate</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($status === 'pending')
                @if($cannotApprove)
                <div class="note-box mb-3">You generated this payroll, so another approver must approve or reject it.</div>
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="action-head" style="background:linear-gradient(90deg,#22c55e,#15803d)"></div>
                            <div class="card-body px-3">
                                <div class="fw-bold mb-2">Approve payroll</div>
                                <form method="POST" action="{{ route('admin.payroll.approve', $payroll->id) }}" class="js-once" onsubmit="return confirm('Approve this final salary?');">
                                    @csrf
                                    <label for="approval_notes" class="form-label">Approval notes (optional)</label>
                                    <textarea id="approval_notes" name="approval_notes" class="form-control mb-3" rows="3" maxlength="2000">{{ old('approval_notes') }}</textarea>
                                    <button type="submit" class="btn-x btn-green" @disabled($cannotApprove)>Approve final salary</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="action-head" style="background:linear-gradient(90deg,#f43f5e,#be123c)"></div>
                            <div class="card-body px-3">
                                <div class="fw-bold mb-2">Reject payroll</div>
                                <form method="POST" action="{{ route('admin.payroll.reject', $payroll->id) }}" class="js-once" onsubmit="return confirm('Reject this payroll for correction?');">
                                    @csrf
                                    <label for="rejection_reason" class="form-label">Reason for rejection</label>
                                    <textarea id="rejection_reason" name="rejection_reason" class="form-control mb-3" rows="3" maxlength="2000" required>{{ old('rejection_reason') }}</textarea>
                                    <button type="submit" class="btn-x btn-red">Reject payroll</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($status === 'approved')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="action-head" style="background:linear-gradient(90deg,#0ea5e9,#6366f1)"></div>
                    <div class="card-body px-3">
                        <div class="fw-bold mb-1">Record salary payment</div>
                        <p class="opacity-75">Use this only after {{ $fmt($payroll->final_salary) }} has actually been paid to {{ $name }}.</p>
                        <form method="POST" action="{{ route('admin.payroll.markPaid', $payroll->id) }}" class="js-once" onsubmit="return confirm('Mark this approved payroll as paid?');">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label" for="payment_method">Payment method</label>
                                    <select name="payment_method" id="payment_method" class="form-select" required>
                                        <option value="">Select...</option>
                                        @foreach($paymentMethods as $val => $label)
                                        <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payment_reference">Reference / transaction ID (optional)</label>
                                    <input type="text" name="payment_reference" id="payment_reference" class="form-control" maxlength="100" value="{{ old('payment_reference') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="paid_at">Paid on</label>
                                    <input type="date" name="paid_at" id="paid_at" class="form-control" max="{{ now()->toDateString() }}" value="{{ old('paid_at', now()->toDateString()) }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn-x btn-indigo w-100">Mark as paid</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                @if($status === 'paid')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="action-head" style="background:linear-gradient(90deg,#22c55e,#15803d)"></div>
                    <div class="card-body px-3">
                        <div class="fw-bold mb-2">Payment receipt</div>
                        <div class="tiles">
                            <div class="tile" style="background:#16a34a"><b>{{ $fmt($payroll->final_salary) }}</b><small>Amount paid</small></div>
                            <div class="tile" style="background:#0ea5e9"><b>{{ $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('d M Y') : '-' }}</b><small>Paid on</small></div>
                            <div class="tile" style="background:#6366f1"><b>{{ $payroll->payment_method ? $paymentMethods[$payroll->payment_method] ?? ucfirst($payroll->payment_method) : '-' }}</b><small>Method</small></div>
                            <div class="tile" style="background:#8b5cf6"><b style="font-size:.95rem">{{ $payroll->payment_reference ?: '-' }}</b><small>Reference</small></div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Calendar + daily table --}}
                <div class="row g-3">
                    <div class="col-xl-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body px-3">
                                <div class="sec-title mb-2"><span class="ico" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">C</span> Attendance calendar - {{ $monthStart->format('F Y') }}</div>
                                @if($items->isEmpty())
                                <div class="opacity-75 py-3 text-center">No daily breakdown available.</div>
                                @else
                                <div class="cal">
                                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dn)<div class="dow">{{ $dn }}</div>@endforeach
                                    @for($i = 0; $i < $leadBlanks; $i++)<div class="d blank"></div>@endfor
                                    @for($day = 1; $day <= $monthStart->daysInMonth; $day++)
                                        @php
                                        $it   = $byDate->get($monthStart->copy()->day($day)->format('Y-m-d'));
                                        $meta = $it ? ($dayMeta[$it->day_type] ?? null) : null;
                                        @endphp
                                        <div class="d" @if($meta) style="background:{{ $meta[3] }};border-color:{{ $meta[2] }}44" title="{{ $meta[1] }}" @else style="border-color:rgba(100,116,139,.25)" @endif>
                                            <i @if($meta) style="color:{{ $meta[2] }}" @endif>{{ $day }}</i>
                                            <b @if($meta) style="color:{{ $meta[2] }}" @endif>{{ $meta[0] ?? '' }}</b>
                                        </div>
                                        @endfor
                                    </div>
                                    <div class="legend">
                                        @foreach($dayMeta as $m)<span><em style="background:{{ $m[2] }}"></em>{{ $m[1] }}</span>@endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body px-3 pb-2">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="sec-title"><span class="ico" style="background:linear-gradient(135deg,#6366f1,#4338ca)">D</span> Daily breakdown</div>
                                        <div class="chips" id="typeChips">
                                            <button type="button" class="chip on" data-type="all">All {{ $items->count() }}</button>
                                            @foreach($dayMeta as $key => $m)
                                            @if(($typeCounts[$key] ?? 0) > 0)
                                            <button type="button" class="chip" data-type="{{ $key }}">{{ $m[1] }} {{ $typeCounts[$key] }}</button>
                                            @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive scroll-box" style="max-height: 440px">
                                    <table class="table ps-table table-hover">
                                        <thead>
                                            <tr><th>Date</th><th>Day type</th><th class="text-end">Minutes</th><th class="text-end">OT hrs</th><th class="text-end">Deduction</th><th>Remarks</th></tr>
                                        </thead>
                                        <tbody>
                                            @forelse($items->sortBy('work_date') as $item)
                                            @php $m = $dayMeta[$item->day_type] ?? ['?', ucwords(str_replace('_', ' ', $item->day_type)), '#64748b', '#e2e8f0']; @endphp
                                            <tr data-type="{{ $item->day_type }}">
                                                <td>{{ Carbon::parse($item->work_date)->format('D, d M') }}</td>
                                                <td><span class="tag" style="background:{{ $m[3] }};color:{{ $m[2] }}">{{ $m[1] }}</span></td>
                                                <td class="text-end">{{ $item->working_minutes }}</td>
                                                <td class="text-end">{{ $trim($item->overtime_hours) }}</td>
                                                <td class="text-end {{ (float) $item->deduction_amount > 0 ? 'text-danger fw-semibold' : 'opacity-50' }}">{{ (float) $item->deduction_amount > 0 ? '- ' . $fmt($item->deduction_amount) : '-' }}</td>
                                                <td><small class="opacity-75">{{ $item->remarks ?: '-' }}</small></td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="6" class="text-center opacity-75 py-4">No daily breakdown available.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    (function () {
            // Prevent double submits: disable the submit button after submit.
                        document.querySelectorAll('form.js-once').forEach(function (form) {
                            form.addEventListener('submit', function (e) {
                    if (e.defaultPrevented) { return; }   // confirm dialog was cancelled
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) { setTimeout(function () { btn.disabled = true; btn.textContent = 'Please wait...'; }, 0); }
                });
                        });

            // Auto-dismiss success / error alerts.
                        document.querySelectorAll('.auto-dismiss').forEach(function (el) {
                            setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 500); }, 6000);
                        });

            // Daily breakdown filter chips.
                        var chips = document.querySelectorAll('#typeChips .chip');
                        chips.forEach(function (chip) {
                            chip.addEventListener('click', function () {
                                chips.forEach(function (c) { c.classList.remove('on'); });
                                chip.classList.add('on');
                                var t = chip.getAttribute('data-type');
                                document.querySelectorAll('tr[data-type]').forEach(function (tr) {
                                    tr.style.display = (t === 'all' || tr.getAttribute('data-type') === t) ? '' : 'none';
                                });
                            });
                        });

            // Live preview for the adjust form (same formula as the payroll service).
                        var form = document.getElementById('adjustForm');
                        if (form) {
                            var monthly = parseFloat(form.dataset.monthly) || 0;
                            var fixed   = parseFloat(form.dataset.fixed) || 0;
                            var selects = form.querySelectorAll('.day-act');
                            var money = function (n) { return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

                            function recalc() {
                                var ded = fixed;
                                selects.forEach(function (s) {
                                    if (s.value === 'deduct' || s.value === 'unpaid_leave') { ded += parseFloat(s.dataset.rate) || 0; }
                                });
                                ded = Math.round(ded * 100) / 100;
                                var other = parseFloat(document.getElementById('other_deductions').value) || 0;
                                var bonus = parseFloat(document.getElementById('bonus_amount').value) || 0;
                                var final = Math.max(0, Math.round((monthly - ded - other + bonus) * 100) / 100);
                                document.getElementById('lvDed').textContent = money(ded);
                                document.getElementById('lvFinal').textContent = money(final);
                            }

                            selects.forEach(function (s) { s.addEventListener('change', recalc); });
                            ['other_deductions', 'bonus_amount'].forEach(function (id) { document.getElementById(id).addEventListener('input', recalc); });

                            var bulkApply = document.getElementById('bulkApply');
                            if (bulkApply) {
                                bulkApply.addEventListener('click', function () {
                                    var v = document.getElementById('bulkAct').value;
                                    selects.forEach(function (s) { s.value = v; });
                                    recalc();
                                });
                            }
                            recalc();
                        }
                    })();
                </script>
            </x-admin>