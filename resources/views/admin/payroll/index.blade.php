
{{-- resources/views/admin/payroll/index.blade.php --}}
@php
use Carbon\Carbon;

$monthDate = Carbon::createFromFormat('!Y-m', $month);
$prevMonth = $monthDate->copy()->subMonth()->format('Y-m');
$nextMonth = $monthDate->copy()->addMonth()->format('Y-m');
$lastDone = now()->subMonth()->format('Y-m');

$bulkMonth = $month <= $lastDone ? $month : $lastDone;
$bulkLabel = Carbon::createFromFormat('!Y-m', $bulkMonth)->format('F Y');
$activeStatus = request('status');

$statusMeta = [
'draft'    => ['Draft', '#64748b', '#f1f5f9'],
'pending'  => ['Pending approval', '#b45309', '#fef3c7'],
'approved' => ['Approved', '#0369a1', '#e0f2fe'],
'rejected' => ['Rejected', '#b91c1c', '#fee2e2'],
'paid'     => ['Paid', '#15803d', '#dcfce7'],
];

$cards = [
'total'    => ['Total'],
'draft'    => ['Draft'],
'pending'  => ['Pending'],
'approved' => ['Approved'],
'rejected' => ['Rejected'],
'paid'     => ['Paid'],
];

$netPayable = (float) ($stats['net_payable'] ?? 0);
$netPaid = (float) ($stats['net_paid'] ?? 0);
$paidPct = $netPayable > 0
? min(100, round($netPaid / $netPayable * 100))
: 0;

$hasBulk = Route::has('admin.payroll.bulk-generate');
$hasDelete = Route::has('admin.payroll.destroy');
$hasPayslip = Route::has('admin.payroll.payslip');
$employees = $employees ?? collect();
@endphp

<x-admin>
    <x-slot:title>Payroll Dashboard | Team Tasker</x-slot:title>

    <style>
        .pr-page {
            font-size: 13px;
            color: var(--text-main, #334155);
        }

        .pr-page .card {
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border-main, #e2e8f0) !important;
            border-radius: var(--radius-md, 8px);
            box-shadow: none !important;
        }

        .pr-page .form-control,
        .pr-page .form-select {
            font-size: .85rem;
            padding: 9px 11px;
            border: 1px solid var(--border-main, #d1d5db);
            border-radius: 6px;
            box-shadow: none;
        }

        .pr-page .form-control:focus,
        .pr-page .form-select:focus {
            border-color: var(--primary, #64748b);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb, 100, 116, 139), .1);
        }

        .pr-page .form-label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-low, #64748b);
            margin-bottom: 5px;
        }

        /* Header and month navigation */
        .pr-page .top-bar-premium {
            margin-bottom: 20px;
        }

        .month-nav {
            display: inline-flex;
            align-items: center;
            padding: 3px;
            border: 1px solid var(--border-main, #e2e8f0);
            border-radius: 6px;
            background: var(--bg-surface, #fff);
        }

        .month-nav a {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-high, #334155);
            text-decoration: none;
            border-radius: 4px;
            font-size: 20px;
        }

        .month-nav a:hover {
            background: var(--bg-surface-hover, #f1f5f9);
        }

        .month-nav span {
            padding: 0 10px;
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-high, #334155);
            white-space: nowrap;
        }

        /* Stats */
        .pr-stat {
            height: 100%;
            display: block;
            padding: 14px;
            border: 1px solid var(--border-main, #e2e8f0);
            border-radius: var(--radius-md, 8px);
            background: var(--bg-surface, #fff);
            text-decoration: none;
            transition: border-color .15s;
        }

        .pr-stat:hover {
            border-color: var(--primary, #94a3b8);
        }

        .pr-stat.active {
            border-color: var(--primary, #64748b);
            box-shadow: inset 0 0 0 1px var(--primary, #64748b);
        }

        .pr-stat-label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-low, #64748b);
            font-size: .75rem;
            font-weight: 600;
        }

        .pr-stat-value {
            display: block;
            color: var(--text-high, #1e293b);
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Salary summary */
        .money-box {
            height: 100%;
            padding: 12px 14px;
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border-main, #e2e8f0);
            border-radius: var(--radius-md, 8px);
        }

        .money-box small {
            display: block;
            margin-bottom: 5px;
            color: var(--text-low, #64748b);
            font-size: .75rem;
        }

        .money-box b {
            color: var(--text-high, #1e293b);
            font-size: 1.05rem;
            overflow-wrap: anywhere;
        }

        .pr-page .progress {
            height: 7px;
            border-radius: 10px;
            background: #e2e8f0;
        }

        .pr-page .progress-bar {
            background: #64748b;
            border-radius: 10px;
        }

        /* Buttons use the admin theme */
        .pr-page .btn-premium {
            font-size: .8rem;
            text-decoration: none;
            cursor: pointer;
        }

        .pr-page .btn-premium:disabled {
            cursor: not-allowed;
            opacity: .65;
        }

        .pr-page .btn-premium-secondary {
            border: 1px solid var(--border-main, #cbd5e1);
        }

        /* Status filters */
        .pills {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border: 1px solid var(--border-main, #e2e8f0);
            border-radius: 6px;
            background: var(--bg-surface, #fff);
            color: var(--text-low, #475569);
            text-decoration: none;
            font-size: .75rem;
            font-weight: 600;
        }

        .pill:hover {
            background: var(--bg-surface-hover, #f1f5f9);
            color: var(--text-high, #334155);
        }

        .pill.on {
            background: var(--primary, #334155) !important;
            border-color: var(--primary, #334155);
            color: #fff !important;
        }

        .pill em {
            margin-left: 5px;
            font-style: normal;
            opacity: .8;
        }

        /* Payroll table */
        .pr-table {
            margin: 0;
            font-size: .8rem;
            min-width: 950px;
        }

        .pr-table thead th {
            padding: 12px;
            background: var(--bg-surface, #f8fafc);
            color: var(--text-low, #64748b);
            border-bottom: 1px solid var(--border-main, #e2e8f0);
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .pr-table td {
            padding: 12px;
            vertical-align: middle;
            border-color: var(--border-main, #e2e8f0);
        }

        .pr-table tbody tr:hover {
            background: var(--bg-surface-hover, #f8fafc);
        }

        .pr-avatar {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--bg-surface-hover, #e2e8f0);
            color: var(--text-high, #334155);
            font-size: .8rem;
            font-weight: 700;
        }

        .pr-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: .7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .pr-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .att {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 3px;
        }

        .att span {
            padding: 3px 5px;
            border-radius: 4px;
            font-size: .7rem;
            font-weight: 600;
        }

        .money-red {
            color: #b91c1c;
        }

        .money-green {
            color: #15803d;
            font-weight: 700;
        }

        .pr-empty {
            padding: 35px 15px !important;
            text-align: center;
            color: var(--text-low, #64748b);
        }

        .pr-empty strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text-high, #334155);
            font-size: .95rem;
        }

        .pr-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
        }

        .pr-action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 31px;
            height: 30px;
            padding: 0 8px;
            border: 1px solid var(--border-main, #e2e8f0);
            border-radius: 5px;
            background: var(--bg-surface, #fff);
            color: var(--text-high, #475569);
            font-size: .75rem;
            text-decoration: none;
            cursor: pointer;
        }

        .pr-action-link:hover {
            background: var(--bg-surface-hover, #f1f5f9);
            color: var(--text-high, #1e293b);
        }

        .pr-page .card-footer {
            background: var(--bg-surface, #fff) !important;
            border-top: 1px solid var(--border-main, #e2e8f0);
        }

        .pr-page .pagination {
            margin-bottom: 0;
        }

        @media (max-width: 767.98px) {
            .pr-page .top-bar-premium {
                align-items: flex-start;
                gap: 12px;
            }

            .pr-page .top-bar-premium > div:last-child {
                flex-wrap: wrap;
            }

            .pr-stat {
                padding: 12px;
            }

            .pr-stat-value {
                font-size: 1.2rem;
            }

            .money-box {
                padding: 10px;
            }

            .money-box b {
                font-size: .95rem;
            }

            .pills {
                margin-top: 5px;
            }
        }
    </style>

    <div class="container-fluid py-3 pr-page">

        {{-- Header --}}
        <div class="top-bar-premium d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 fw-semibold mb-1 text-high">Payroll Dashboard</h1>
                <p class="text-low mb-0" style="font-size:.9rem;">
                    Generate, review and approve employee monthly salaries.
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="month-nav">
                    <a href="{{ request()->fullUrlWithQuery(['month' => $prevMonth, 'page' => null]) }}"
                     title="Previous month" aria-label="Previous month">&lsaquo;</a>
                     <span>{{ $monthDate->format('F Y') }}</span>
                     <a href="{{ request()->fullUrlWithQuery(['month' => $nextMonth, 'page' => null]) }}"
                         title="Next month" aria-label="Next month">&rsaquo;</a>
                     </div>

                     <a href="{{ route('admin.payroll.salaries') }}"
                     class="btn-premium btn-premium-secondary px-3 py-2">
                     <i class="fas fa-users me-1"></i> Manage Salaries
                 </a>
             </div>
         </div>

         {{-- Alerts --}}
         @if(session('success'))
         <div class="alert alert-success py-2 px-3 auto-dismiss">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger py-2 px-3 auto-dismiss">
            {{ session('error') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger py-2 px-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Statistics --}}
        <div class="row g-3 mb-3">
            @foreach($cards as $key => $card)
            @php
            $isActive = ($key === 'total' && !$activeStatus)
            || $key === $activeStatus;
            @endphp

            <div class="col-6 col-md-4 col-xl-2">
                <a class="pr-stat {{ $isActive ? 'active' : '' }}"
                href="{{ request()->fullUrlWithQuery([
                'status' => $key === 'total' ? null : $key,
                'page' => null
                ]) }}">
                <span class="pr-stat-label">{{ $card[0] }}</span>
                <span class="pr-stat-value">{{ $stats[$key] ?? 0 }}</span>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Salary summary --}}
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-6 col-md-3">
                    <div class="money-box">
                        <small>Total payable</small>
                        <b>₹{{ number_format($netPayable, 2) }}</b>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="money-box">
                        <small>Paid so far</small>
                        <b>₹{{ number_format($netPaid, 2) }}</b>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-between small text-low mb-2">
                        <span>Payment progress</span>
                        <strong>{{ $paidPct }}%</strong>
                    </div>

                    <div class="progress">
                        <div class="progress-bar"
                        role="progressbar"
                        style="width:{{ $paidPct }}%"
                        aria-valuenow="{{ $paidPct }}"
                        aria-valuemin="0"
                        aria-valuemax="100"></div>
                    </div>

                    <div class="small text-low mt-2">
                        Remaining: ₹{{ number_format(max(0, $netPayable - $netPaid), 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Generate payroll --}}
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h6 fw-semibold text-high mb-0">Generate Payroll</h2>
                <small class="text-low">
                    Payroll can only be generated for a completed month.
                </small>
            </div>

            <form action="{{ route('admin.payroll.generate') }}"
            method="POST"
            class="js-once">
            @csrf

            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="user_id" class="form-label">Employee</label>

                    @if($employees->isNotEmpty())
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Select employee...</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            @selected(old('user_id') == $emp->id)>
                            {{ $emp->name }} ({{ $emp->email }})
                        </option>
                        @endforeach
                    </select>
                    @else
                    <input type="number"
                    min="1"
                    name="user_id"
                    id="user_id"
                    class="form-control"
                    value="{{ old('user_id') }}"
                    placeholder="Employee user ID"
                    required>
                    @endif
                </div>

                <div class="col-md-3">
                    <label for="generate_month" class="form-label">Payroll month</label>
                    <input type="month"
                    name="month"
                    id="generate_month"
                    class="form-control"
                    value="{{ old('month', $month <= $lastDone ? $month : $lastDone) }}"
                    max="{{ $lastDone }}"
                    required>
                </div>

                <div class="col-md-4">
                    <button type="submit"
                    class="btn-premium btn-premium-primary px-4 py-2 w-100">
                    <i class="fas fa-file-invoice-dollar me-1"></i>
                    Generate Draft
                </button>
            </div>
        </div>
    </form>

    @if($hasBulk)
    <div class="border-top mt-3 pt-3">
        <form action="{{ route('admin.payroll.bulk-generate') }}"
        method="POST"
        class="js-once d-flex flex-wrap align-items-center gap-2"
        onsubmit="return confirm('Generate payroll for all employees for {{ $bulkLabel }}?')">
        @csrf
        <input type="hidden" name="month" value="{{ $bulkMonth }}">

        <button type="submit"
        class="btn-premium btn-premium-secondary px-3 py-2">
        <i class="fas fa-users me-1"></i>
        Generate for All Employees
    </button>

    <small class="text-low">
        {{ $bulkLabel }} — existing payroll records will be skipped.
    </small>
</form>
</div>
@endif
</div>
</div>

{{-- Payroll records --}}
<div class="card mb-4">
    <div class="card-body border-bottom p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-semibold text-high mb-0">
                Payroll Records — {{ $monthDate->format('F Y') }}
            </h2>

            <div class="pills">
                <a class="pill {{ !$activeStatus ? 'on' : '' }}"
                href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}">
                All <em>{{ $stats['total'] ?? 0 }}</em>
            </a>

            @foreach($statusMeta as $key => $meta)
            <a class="pill {{ $activeStatus === $key ? 'on' : '' }}"
            href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}">
            {{ $meta[0] }} <em>{{ $stats[$key] ?? 0 }}</em>
        </a>
        @endforeach
    </div>
</div>

<form method="GET"
action="{{ route('admin.payroll.index') }}"
id="filterForm">
@if($activeStatus)
<input type="hidden" name="status" value="{{ $activeStatus }}">
@endif

<div class="row g-3 align-items-end">
    <div class="col-md-3">
        <label for="month" class="form-label">Month</label>
        <input type="month"
        name="month"
        id="month"
        class="form-control"
        value="{{ $month }}"
        required
        onchange="this.form.submit()">
    </div>

    <div class="col-md-5">
        <label for="search" class="form-label">Search employee</label>
        <input type="text"
        name="search"
        id="search"
        class="form-control"
        value="{{ request('search') }}"
        placeholder="Name or email">
    </div>

    <div class="col-md-4 d-flex gap-2">
        <button type="submit"
        class="btn-premium btn-premium-primary px-3 py-2">
        <i class="fas fa-search me-1"></i> Search
    </button>

    <a href="{{ route('admin.payroll.index', ['month' => $month]) }}"
     class="btn-premium btn-premium-secondary px-3 py-2">
     Reset
 </a>
</div>
</div>
</form>
</div>

<div class="table-responsive">
    <table class="table data-grid-table pr-table">
        <thead>
            <tr>
                <th>EMPLOYEE</th>
                <th>ATTENDANCE</th>
                <th class="text-end">MONTHLY SALARY</th>
                <th class="text-end">DEDUCTION</th>
                <th class="text-end">FINAL SALARY</th>
                <th>STATUS</th>
                <th>APPROVED BY</th>
                <th class="text-end">ACTIONS</th>
            </tr>
        </thead>

        <tbody>
            @forelse($payrolls as $payroll)
            @php
            $name = $payroll->user?->name ?? 'Employee unavailable';
            $init = mb_strtoupper(mb_substr($name, 0, 1));

            [$sLabel, $sColor, $sBg] =
            $statusMeta[$payroll->status]
            ?? ['Unknown', '#64748b', '#f1f5f9'];

            $ded = (float) $payroll->deduction_amount
            + (float) $payroll->other_deductions;

            $half = rtrim(
            rtrim(number_format((float) $payroll->half_days, 2), '0'),
            '.'
            ) ?: '0';
            @endphp

            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="pr-avatar">{{ $init }}</span>
                        <div>
                            <div class="fw-semibold text-high">
                                {{ $name }}
                            </div>
                            <small class="text-low">
                                {{ $payroll->user?->email }}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="att">
                        <span style="background:#dcfce7;color:#166534"
                        title="Present">
                        P {{ $payroll->present_days }}
                    </span>
                    <span style="background:#fef3c7;color:#92400e"
                    title="Half days">
                    H {{ $half }}
                </span>
                <span style="background:#fee2e2;color:#991b1b"
                title="Absent">
                A {{ $payroll->absent_days }}
            </span>
        </div>
        <small class="text-low">
            of {{ $payroll->scheduled_working_days }} days
        </small>
    </td>

    <td class="text-end text-high text-nowrap">
        ₹{{ number_format((float) $payroll->monthly_salary, 2) }}
    </td>

    <td class="text-end text-nowrap {{ $ded > 0 ? 'money-red' : 'text-low' }}">
        {{ $ded > 0 ? '- ₹' . number_format($ded, 2) : '—' }}
    </td>

    <td class="text-end money-green text-nowrap">
        ₹{{ number_format((float) $payroll->final_salary, 2) }}
    </td>

    <td>
        <span class="pr-badge"
        style="background:{{ $sBg }};color:{{ $sColor }}">
        <span class="pr-badge-dot"
        style="background:{{ $sColor }}"></span>
        {{ $sLabel }}
    </span>
</td>

<td class="text-high">
    {{ $payroll->approver?->name ?? '—' }}
</td>

<td class="text-end text-nowrap">
    <div class="pr-actions">
        <a href="{{ route('admin.payroll.show', $payroll->id) }}"
         class="pr-action-link"
         title="View payroll">
         <i class="fas fa-eye me-1"></i> View
     </a>

     @if($hasPayslip && in_array($payroll->status, ['approved', 'paid'], true))
     <a href="{{ route('admin.payroll.payslip', $payroll->id) }}"
         target="_blank"
         rel="noopener"
         class="pr-action-link"
         title="View payslip">
         <i class="fas fa-file-invoice me-1"></i> Payslip
     </a>
     @endif

     @if($hasDelete && in_array($payroll->status, ['draft', 'rejected'], true))
     <form action="{{ route('admin.payroll.destroy', $payroll->id) }}"
      method="POST"
      class="d-inline"
      onsubmit="return confirm('Delete this {{ $payroll->status }} payroll?')">
      @csrf
      @method('DELETE')

      <button type="submit"
      class="pr-action-link"
      title="Delete payroll">
      <i class="fas fa-trash"></i>
  </button>
</form>
@endif
</div>
</td>
</tr>
@empty
<tr>
    <td colspan="8" class="pr-empty">
        <strong>No payroll records found</strong>
        No payroll records found for {{ $monthDate->format('F Y') }}.
        Use Generate Draft to create a record.
    </td>
</tr>
@endforelse
</tbody>
</table>
</div>

<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2 p-3">
    <small class="text-low">
        @if($payrolls->total() > 0)
        Showing {{ $payrolls->firstItem() }}–{{ $payrolls->lastItem() }}
        of {{ $payrolls->total() }} records
        @else
        0 records
        @endif
    </small>

    {{ $payrolls->withQueryString()->links() }}
</div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
            // Prevent accidental double submissions.
        document.querySelectorAll('form.js-once').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) return;

                    // Let browser validation and confirmation finish first.
                if (!form.checkValidity()) return;

                const submitter = event.submitter;
                if (submitter && submitter.disabled) {
                    event.preventDefault();
                    return;
                }

                if (submitter) {
                    setTimeout(function () {
                        submitter.disabled = true;
                        submitter.textContent = 'Please wait...';
                    }, 0);
                }
            });
        });

            // Auto-dismiss feedback alerts.
        document.querySelectorAll('.auto-dismiss').forEach(function (element) {
            setTimeout(function () {
                element.style.transition = 'opacity .5s';
                element.style.opacity = '0';

                setTimeout(function () {
                    element.remove();
                }, 500);
            }, 6000);
        });
    });
</script>
</x-admin>
