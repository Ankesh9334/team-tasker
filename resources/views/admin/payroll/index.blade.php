{{-- resources/views/admin/payroll/index.blade.php --}}
@php
use Carbon\Carbon;

$monthDate   = Carbon::createFromFormat('!Y-m', $month);
$prevMonth   = $monthDate->copy()->subMonth()->format('Y-m');
$nextMonth   = $monthDate->copy()->addMonth()->format('Y-m');
$lastDone    = now()->subMonth()->format('Y-m');   // payroll can only be generated for a completed month
$bulkMonth   = $month <= $lastDone ? $month : $lastDone;
$bulkLabel   = Carbon::createFromFormat('!Y-m', $bulkMonth)->format('F Y');
$activeStatus = request('status');

$statusMeta = [
'draft'    => ['Draft',            '#64748b', '#f1f5f9'],
'pending'  => ['Pending approval', '#d97706', '#fef3c7'],
'approved' => ['Approved',         '#0284c7', '#e0f2fe'],
'rejected' => ['Rejected',         '#dc2626', '#fee2e2'],
'paid'     => ['Paid',             '#16a34a', '#dcfce7'],
];

$cards = [
'total'    => ['Total',    'linear-gradient(135deg,#6366f1,#4338ca)'],
'draft'    => ['Draft',    'linear-gradient(135deg,#64748b,#334155)'],
'pending'  => ['Pending',  'linear-gradient(135deg,#f59e0b,#d97706)'],
'approved' => ['Approved', 'linear-gradient(135deg,#0ea5e9,#0369a1)'],
'rejected' => ['Rejected', 'linear-gradient(135deg,#f43f5e,#be123c)'],
'paid'     => ['Paid',     'linear-gradient(135deg,#22c55e,#15803d)'],
];

$avatarColors = ['#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444', '#22c55e'];

$netPayable = (float) ($stats['net_payable'] ?? 0);
$netPaid    = (float) ($stats['net_paid'] ?? 0);
$paidPct    = $netPayable > 0 ? min(100, round($netPaid / $netPayable * 100)) : 0;

$hasBulk    = Route::has('admin.payroll.bulk-generate');
$hasDelete  = Route::has('admin.payroll.destroy');
$hasPayslip = Route::has('admin.payroll.payslip');
$employees  = $employees ?? collect();
@endphp

<x-admin>
    <x-slot:title>Payroll Dashboard | Team Tasker</x-slot:title>

    <style>
        .pr-page { font-size: .86rem; }
        .pr-page .card { border-radius: 14px; }
        .pr-page .form-control, .pr-page .form-select { font-size: .84rem; padding: .38rem .65rem; border-radius: 8px; }
        .pr-page .form-label { font-size: .75rem; font-weight: 600; color: #64748b; margin-bottom: .2rem; }

        /* Header */
        .pr-hero {
            background: linear-gradient(120deg, #4f46e5, #7c3aed 55%, #db2777);
            border-radius: 16px; color: #fff; padding: 1.1rem 1.3rem;
        }
        .pr-hero h1 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .pr-hero p { margin: 0; opacity: .85; font-size: .8rem; }
        .month-nav { display: inline-flex; align-items: center; background: rgba(255,255,255,.16); border-radius: 999px; padding: 3px; }
        .month-nav a { color: #fff; text-decoration: none; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 700; }
        .month-nav a:hover { background: rgba(255,255,255,.25); }
        .month-nav span { padding: 0 .8rem; font-weight: 600; font-size: .85rem; }

        /* Soft colored buttons */
        .btn-x { border: 0; border-radius: 8px; font-size: .78rem; font-weight: 600; padding: .34rem .75rem; line-height: 1.3; transition: transform .12s, box-shadow .12s, filter .12s; }
        .btn-x:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.14); filter: brightness(1.05); }
        .btn-x:active { transform: translateY(0); }
        .btn-indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; }
        .btn-teal   { background: linear-gradient(135deg,#14b8a6,#0f766e); color: #fff; }
        .btn-pink   { background: linear-gradient(135deg,#ec4899,#be185d); color: #fff; }
        .btn-white  { background: #fff; color: #4f46e5; }
        .btn-sky    { background: #e0f2fe; color: #0369a1; }
        .btn-amber  { background: #fef3c7; color: #b45309; }
        .btn-rose   { background: #fee2e2; color: #b91c1c; }
        .btn-slate  { background: #f1f5f9; color: #475569; }

        /* Stat cards */
        .stat-card { border-radius: 14px; color: #fff; padding: .7rem .9rem; height: 100%; position: relative; overflow: hidden; display: block; text-decoration: none; transition: transform .15s, box-shadow .15s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,.18); color: #fff; }
        .stat-card.active { outline: 3px solid rgba(255,255,255,.9); box-shadow: 0 0 0 3px rgba(79,70,229,.5); }
        .stat-card::after { content: ""; position: absolute; right: -18px; top: -18px; width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,.14); }
        .stat-card small { opacity: .9; font-size: .72rem; }
        .stat-card b { display: block; font-size: 1.5rem; line-height: 1.15; }

        /* Money strip */
        .money-box { border-radius: 12px; padding: .6rem .9rem; }
        .money-box small { display: block; font-size: .72rem; opacity: .8; }
        .money-box b { font-size: 1.1rem; }
        .progress { height: 8px; border-radius: 999px; background: #e2e8f0; }
        .progress-bar { background: linear-gradient(90deg,#22c55e,#16a34a); border-radius: 999px; }

        /* Status pills */
        .pills { display: flex; gap: .4rem; flex-wrap: wrap; }
        .pill { text-decoration: none; padding: .22rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid transparent; }
        .pill:hover { filter: brightness(.96); }
        .pill.on { color: #fff; }
        .pill em { font-style: normal; opacity: .75; margin-left: .25rem; }

        /* Table */
        .pr-table { margin: 0; }
        .pr-table thead th { font-size: .72rem; color: #64748b; font-weight: 600; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: .55rem .75rem; white-space: nowrap; }
        .pr-table td { padding: .55rem .75rem; vertical-align: middle; border-color: #f1f5f9; }
        .pr-table tbody tr { transition: background .12s; }
        .pr-table tbody tr:hover { background: #f8faff; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; color: #fff; font-weight: 700; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .badge-s { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-s i { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
        .att { display: flex; gap: .25rem; margin-top: 2px; }
        .att span { font-size: .66rem; font-weight: 700; padding: 0 .35rem; border-radius: 4px; }
        .money-red { color: #dc2626; }
        .money-green { color: #15803d; font-weight: 700; }
        .empty-box { padding: 2.2rem 1rem; text-align: center; color: #94a3b8; }
        .empty-box b { display: block; font-size: 1rem; color: #64748b; margin-bottom: .2rem; }
    </style>

    <div class="container-fluid py-3 pr-page">

        {{-- Hero --}}
        <div class="pr-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1>Payroll Dashboard</h1>
                <p>Generate, review and approve employee monthly salaries.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="month-nav">
                    <a href="{{ request()->fullUrlWithQuery(['month' => $prevMonth, 'page' => null]) }}" title="Previous month">&lsaquo;</a>
                    <span>{{ $monthDate->format('F Y') }}</span>
                    <a href="{{ request()->fullUrlWithQuery(['month' => $nextMonth, 'page' => null]) }}" title="Next month">&rsaquo;</a>
                </div>
                <a href="{{ route('admin.payroll.salaries') }}" class="btn-x btn-white text-decoration-none">Manage employee salaries</a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))<div class="alert alert-success py-2 px-3 auto-dismiss">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2 px-3 auto-dismiss">{{ session('error') }}</div>@endif
        @if($errors->any())
        <div class="alert alert-danger py-2 px-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- Stat cards (click = filter) --}}
        <div class="row g-2 mb-2">
            @foreach($cards as $key => [$label, $gradient])
            @php $isActive = ($key === 'total' && !$activeStatus) || $key === $activeStatus; @endphp
            <div class="col-6 col-md-4 col-xl-2">
                <a class="stat-card {{ $isActive ? 'active' : '' }}" style="background: {{ $gradient }}"
                href="{{ request()->fullUrlWithQuery(['status' => $key === 'total' ? null : $key, 'page' => null]) }}">
                <small>{{ $label }}</small>
                <b>{{ $stats[$key] ?? 0 }}</b>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Money strip --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-6 col-md-3">
                    <div class="money-box" style="background:#eef2ff;color:#4338ca"><small>Total payable</small><b>₹{{ number_format($netPayable, 2) }}</b></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="money-box" style="background:#dcfce7;color:#15803d"><small>Paid so far</small><b>₹{{ number_format($netPaid, 2) }}</b></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Payment progress</span><b>{{ $paidPct }}%</b>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: {{ $paidPct }}%"></div></div>
                    <div class="small text-muted mt-1">Remaining: ₹{{ number_format(max(0, $netPayable - $netPaid), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Generate --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                <div class="fw-bold">Generate payroll</div>
                <small class="text-muted">Payroll can only be generated for a completed month.</small>
            </div>
            <form action="{{ route('admin.payroll.generate') }}" method="POST" class="js-once">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="user_id" class="form-label">Employee</label>
                        @if($employees->isNotEmpty())
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Select employee...</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('user_id') == $emp->id)>{{ $emp->name }} ({{ $emp->email }})</option>
                            @endforeach
                        </select>
                        @else
                        <input type="number" min="1" name="user_id" id="user_id" class="form-control" value="{{ old('user_id') }}" placeholder="Employee user ID" required>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label for="generate_month" class="form-label">Payroll month</label>
                        <input type="month" name="month" id="generate_month" class="form-control"
                        value="{{ old('month', $month <= $lastDone ? $month : $lastDone) }}" max="{{ $lastDone }}" required>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn-x btn-indigo flex-fill">Generate draft</button>
                        </div>
                    </div>
                </form>

                @if($hasBulk)
                <form action="{{ route('admin.payroll.bulk-generate') }}" method="POST" class="js-once mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2"
                onsubmit="return confirm('Generate payroll for all employees for {{ $bulkLabel }}?')">
                @csrf
                <input type="hidden" name="month" value="{{ $bulkMonth }}">
                <button type="submit" class="btn-x btn-teal">Generate for all employees</button>
                <small class="text-muted">{{ $bulkLabel }} - employees whose payroll already exists will be skipped.</small>
            </form>
            @endif
        </div>
    </div>

    {{-- Records --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom py-3 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="fw-bold">Payroll records - {{ $monthDate->format('F Y') }}</div>
                <div class="pills">
                    <a class="pill {{ !$activeStatus ? 'on' : '' }}" style="{{ !$activeStatus ? 'background:#4f46e5' : '' }}"
                    href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}">All<em>{{ $stats['total'] ?? 0 }}</em></a>
                    @foreach($statusMeta as $key => [$label, $color])
                    <a class="pill {{ $activeStatus === $key ? 'on' : '' }}"
                    style="{{ $activeStatus === $key ? "background:$color" : '' }}"
                    href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}">{{ ucfirst($key) }}<em>{{ $stats[$key] ?? 0 }}</em></a>
                    @endforeach
                </div>
            </div>

            <form method="GET" action="{{ route('admin.payroll.index') }}" id="filterForm">
                @if($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="month" class="form-label">Month</label>
                        <input type="month" name="month" id="month" class="form-control" value="{{ $month }}" required onchange="this.form.submit()">
                    </div>
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search employee</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Name or email, then Enter">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn-x btn-sky">Search</button>
                        <a href="{{ route('admin.payroll.index') }}" class="btn-x btn-slate text-decoration-none">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table pr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Attendance</th>
                        <th class="text-end">Monthly salary</th>
                        <th class="text-end">Deduction</th>
                        <th class="text-end">Final salary</th>
                        <th>Status</th>
                        <th>Approved by</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $payroll)
                    @php
                    $name   = $payroll->user?->name ?? 'Employee unavailable';
                    $init   = strtoupper(mb_substr($name, 0, 1));
                    $color  = $avatarColors[abs(crc32($name)) % count($avatarColors)];
                    [$sLabel, $sColor, $sBg] = $statusMeta[$payroll->status] ?? ['Unknown', '#64748b', '#f1f5f9'];
                    $ded    = (float) $payroll->deduction_amount + (float) $payroll->other_deductions;
                    $half   = rtrim(rtrim(number_format((float) $payroll->half_days, 2), '0'), '.') ?: '0';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar" style="background: {{ $color }}">{{ $init }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $name }}</div>
                                    <small class="text-muted">{{ $payroll->user?->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="att">
                                <span style="background:#dcfce7;color:#15803d" title="Present">P {{ $payroll->present_days }}</span>
                                <span style="background:#fef3c7;color:#b45309" title="Half days">H {{ $half }}</span>
                                <span style="background:#fee2e2;color:#b91c1c" title="Absent">A {{ $payroll->absent_days }}</span>
                            </div>
                            <small class="text-muted">of {{ $payroll->scheduled_working_days }} days</small>
                        </td>
                        <td class="text-end">₹{{ number_format((float) $payroll->monthly_salary, 2) }}</td>
                        <td class="text-end {{ $ded > 0 ? 'money-red' : 'text-muted' }}">{{ $ded > 0 ? '- ₹' . number_format($ded, 2) : '-' }}</td>
                        <td class="text-end money-green">₹{{ number_format((float) $payroll->final_salary, 2) }}</td>
                        <td>
                            <span class="badge-s" style="background: {{ $sBg }}; color: {{ $sColor }}">
                                <i style="background: {{ $sColor }}"></i>{{ $sLabel }}
                            </span>
                        </td>
                        <td>{{ $payroll->approver?->name ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.payroll.show', $payroll->id) }}" class="btn-x btn-sky text-decoration-none">View</a>

                            @if($hasPayslip && in_array($payroll->status, ['approved', 'paid'], true))
                            <a href="{{ route('admin.payroll.payslip', $payroll->id) }}" target="_blank" class="btn-x btn-amber text-decoration-none">Payslip</a>
                            @endif

                            @if($hasDelete && in_array($payroll->status, ['draft', 'rejected'], true))
                            <form action="{{ route('admin.payroll.destroy', $payroll->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this {{ $payroll->status }} payroll?')">
                              @csrf @method('DELETE')
                              <button type="submit" class="btn-x btn-rose">Delete</button>
                          </form>
                          @endif
                      </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="8">
                        <div class="empty-box">
                            <b>No payroll records found</b>
                            There is no payroll for {{ $monthDate->format('F Y') }} yet. Use "Generate draft" above.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <small class="text-muted">
            @if($payrolls->total() > 0)
            Showing {{ $payrolls->firstItem() }}-{{ $payrolls->lastItem() }} of {{ $payrolls->total() }}
            @endif
        </small>
        {{ $payrolls->links() }}
    </div>
</div>
</div>

<script>
        // Prevent double submits: disable the button after submit.
    document.querySelectorAll('form.js-once').forEach(function (form) {
        form.addEventListener('submit', function (e) {
                if (e.defaultPrevented) { return; }   // Do nothing if the confirm dialog was cancelled
                var btn = form.querySelector('button[type="submit"]');
                if (btn) { setTimeout(function () { btn.disabled = true; btn.textContent = 'Please wait...'; }, 0); }
            });
    });
        // Auto-dismiss success / error alerts.
    document.querySelectorAll('.auto-dismiss').forEach(function (el) {
        setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 500); }, 6000);
    });
</script>
</x-admin>