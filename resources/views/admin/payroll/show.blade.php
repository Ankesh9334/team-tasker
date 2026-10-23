{{-- resources/views/admin/payroll/show.blade.php --}}
@php
use Carbon\Carbon;

$monthStart = Carbon::parse($payroll->payroll_month)->startOfMonth();
$status = $payroll->status;
$items = $payroll->items;

$fmt = fn ($n) => '₹' . number_format((float) $n, 2);
$trim = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.') ?: '0';

$name = $payroll->user?->name ?? 'Employee unavailable';
$initial = strtoupper(mb_substr($name, 0, 1));

$statusMeta = [
'draft'    => ['Draft', 'secondary'],
'pending'  => ['Pending approval', 'warning'],
'approved' => ['Approved', 'primary'],
'rejected' => ['Rejected', 'danger'],
'paid'     => ['Paid', 'success'],
];

[$statusLabel, $statusClass] = $statusMeta[$status] ?? ['Unknown', 'secondary'];

$editable = in_array($status, ['draft', 'rejected'], true);

$stage = [
'draft' => 0,
'pending' => 1,
'rejected' => 1,
'approved' => 2,
'paid' => 3,
][$status] ?? 0;

$steps = [
['Generated', $payroll->generated_at, $payroll->generator?->name],
[$status === 'rejected' ? 'Rejected' : 'Submitted', null, null],
['Approved', $payroll->approved_at, $payroll->approver?->name],
['Paid', $payroll->paid_at, null],
];

$dayMeta = [
'present'      => ['P', 'Present', 'success'],
'half_day'     => ['H', 'Half day', 'warning'],
'absent'       => ['A', 'Absent', 'danger'],
'paid_leave'   => ['PL', 'Paid leave', 'primary'],
'unpaid_leave' => ['UL', 'Unpaid leave', 'purple'],
'weekly_off'   => ['WO', 'Weekly off', 'secondary'],
];

$byDate = $items->keyBy(
fn ($item) => Carbon::parse($item->work_date)->format('Y-m-d')
);

$leadBlanks = $monthStart->dayOfWeekIso - 1;
$typeCounts = $items->groupBy('day_type')->map->count();

$adjustTypes = ['absent', 'paid_leave', 'unpaid_leave'];
$adjustable = $items->whereIn('day_type', $adjustTypes)->sortBy('work_date');

$fixedDeduction = (float) $items
->whereNotIn('day_type', $adjustTypes)
->sum('deduction_amount');

$currentAction = function ($item) {
    if ($item->day_type === 'paid_leave') {
        return 'paid_leave';
    }

    if ($item->day_type === 'unpaid_leave') {
        return 'unpaid_leave';
    }

    return (float) $item->deduction_amount > 0 ? 'deduct' : 'keep';
};

$hasAdjustRoute = Route::has('admin.payroll.adjust');

$authUser = request()->user();
$isSuper = $authUser
&& method_exists($authUser, 'hasRole')
&& $authUser->hasRole('super-admin');

$cannotApprove = $payroll->generated_by
&& $authUser
&& (int) $payroll->generated_by === (int) $authUser->id
&& !$isSuper;

$paymentMethods = [
'bank_transfer' => 'Bank transfer',
'upi' => 'UPI',
'cheque' => 'Cheque',
'cash' => 'Cash',
];
@endphp

<x-admin>
    <x-slot:title>Payroll Details | Team Tasker</x-slot:title>

    <style>
        /* =========================================================
           PAYROLL DETAILS — PROFESSIONAL ADMIN DESIGN
           Respects the Bootstrap light/dark theme variables.
        ========================================================= */

        .ps-page {
            --ps-radius: 10px;
            --ps-gap: 1rem;
            color: var(--bs-body-color);
            font-size: .875rem;
            min-width: 0;
        }

        .ps-page,
        .ps-page * {
            box-sizing: border-box;
        }

        .ps-page .card {
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            border: 1px solid var(--bs-border-color) !important;
            border-radius: var(--ps-radius);
            box-shadow: none !important;
            min-width: 0;
        }

        .ps-page .card-header,
        .ps-page .card-footer {
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
        }

        .ps-page .card-body {
            min-width: 0;
        }

        .ps-page .text-muted,
        .ps-page .opacity-75,
        .ps-page .opacity-50 {
            color: var(--bs-secondary-color) !important;
        }

        .ps-page .text-danger {
            color: var(--bs-danger-text-emphasis, var(--bs-danger)) !important;
        }

        .ps-page .page-heading {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.4;
            margin: 0;
            color: var(--bs-emphasis-color);
        }

        .ps-page .page-subtitle {
            font-size: .8rem;
            color: var(--bs-secondary-color);
            margin: .2rem 0 0;
        }

        /* Employee header */

        .ps-page .ps-hero {
            padding: 1.1rem 1.25rem;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--ps-radius);
        }

        .ps-page .avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 46px;
            height: 46px;
            border-radius: 9px;
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            color: var(--bs-emphasis-color);
            font-size: 1rem;
            font-weight: 700;
        }

        .ps-page .hero-meta {
            font-size: .8rem;
            color: var(--bs-secondary-color);
            overflow-wrap: anywhere;
        }

        .ps-page .hero-net {
            text-align: right;
            min-width: 170px;
        }

        .ps-page .hero-net small {
            display: block;
            color: var(--bs-secondary-color);
            font-size: .76rem;
            margin-bottom: .15rem;
        }

        .ps-page .hero-net strong {
            color: var(--bs-emphasis-color);
            font-size: 1.45rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        /* Status badges: color is used only for status meaning. */

        .ps-page .status-badge,
        .ps-page .day-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border: 1px solid transparent;
            border-radius: 5px;
            padding: .22rem .5rem;
            font-size: .72rem;
            line-height: 1.35;
            font-weight: 600;
            white-space: nowrap;
        }

        .ps-page .status-draft,
        .ps-page .status-secondary {
            color: var(--bs-secondary-color);
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }

        .ps-page .status-pending {
            color: var(--bs-warning-text-emphasis);
            background: var(--bs-warning-bg-subtle);
            border-color: var(--bs-warning-border-subtle);
        }

        .ps-page .status-approved {
            color: var(--bs-primary-text-emphasis);
            background: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary-border-subtle);
        }

        .ps-page .status-rejected {
            color: var(--bs-danger-text-emphasis);
            background: var(--bs-danger-bg-subtle);
            border-color: var(--bs-danger-border-subtle);
        }

        .ps-page .status-paid {
            color: var(--bs-success-text-emphasis);
            background: var(--bs-success-bg-subtle);
            border-color: var(--bs-success-border-subtle);
        }

        /* Buttons */

        .ps-page .btn-x {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 34px;
            padding: .42rem .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 7px;
            font-size: .79rem;
            line-height: 1.35;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color .15s, border-color .15s;
        }

        .ps-page .btn-x:focus-visible,
        .ps-page .chip:focus-visible {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }

        .ps-page .btn-x:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .ps-page .btn-neutral {
            color: var(--bs-body-color);
            background: var(--bs-body-bg);
        }

        .ps-page .btn-neutral:hover {
            background: var(--bs-tertiary-bg);
            color: var(--bs-emphasis-color);
        }

        .ps-page .btn-primary-x {
            color: #fff;
            background: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .ps-page .btn-primary-x:hover {
            color: #fff;
            filter: brightness(.94);
        }

        .ps-page .btn-success-x {
            color: #fff;
            background: var(--bs-success);
            border-color: var(--bs-success);
        }

        .ps-page .btn-success-x:hover {
            color: #fff;
            filter: brightness(.94);
        }

        .ps-page .btn-danger-x {
            color: #fff;
            background: var(--bs-danger);
            border-color: var(--bs-danger);
        }

        .ps-page .btn-danger-x:hover {
            color: #fff;
            filter: brightness(.94);
        }

        /* Section headings */

        .ps-page .sec-title {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-width: 0;
            margin: 0;
            color: var(--bs-emphasis-color);
            font-size: .92rem;
            font-weight: 700;
        }

        .ps-page .sec-title .ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
            font-size: .78rem;
            font-weight: 700;
        }

        .ps-page .section-note {
            font-size: .77rem;
            color: var(--bs-secondary-color);
        }

        /* Approval progress */

        .ps-page .stepper {
            display: flex;
            align-items: flex-start;
            width: 100%;
        }

        .ps-page .step {
            position: relative;
            flex: 1;
            min-width: 0;
            text-align: center;
        }

        .ps-page .step::before {
            content: "";
            position: absolute;
            top: 14px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: var(--bs-border-color);
            z-index: 0;
        }

        .ps-page .step:first-child::before {
            display: none;
        }

        .ps-page .step.done::before {
            background: var(--bs-success);
        }

        .ps-page .step .dot {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            margin: 0 auto .45rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 50%;
            background: var(--bs-body-bg);
            color: var(--bs-secondary-color);
            font-size: .75rem;
            font-weight: 700;
        }

        .ps-page .step.done .dot {
            color: #fff;
            background: var(--bs-success);
            border-color: var(--bs-success);
        }

        .ps-page .step.cur .dot {
            color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary);
        }

        .ps-page .step b {
            display: block;
            color: var(--bs-body-color);
            font-size: .77rem;
        }

        .ps-page .step small {
            display: block;
            margin-top: .12rem;
            color: var(--bs-secondary-color);
            font-size: .67rem;
            overflow-wrap: anywhere;
        }

        /* Salary calculation */

        .ps-page .formula {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: .55rem;
        }

        .ps-page .formula .op {
            align-self: center;
            color: var(--bs-secondary-color);
            font-weight: 700;
        }

        .ps-page .fbox {
            flex: 1 1 130px;
            min-width: 0;
            padding: .7rem .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: var(--bs-tertiary-bg);
        }

        .ps-page .fbox small {
            display: block;
            margin-bottom: .25rem;
            color: var(--bs-secondary-color);
            font-size: .71rem;
        }

        .ps-page .fbox b {
            display: block;
            color: var(--bs-emphasis-color);
            font-size: .94rem;
            overflow-wrap: anywhere;
        }

        .ps-page .fbox.final-box {
            border-color: var(--bs-primary-border-subtle);
            background: var(--bs-primary-bg-subtle);
        }

        .ps-page .fbox.final-box b {
            color: var(--bs-primary-text-emphasis);
        }

        /* Summary tiles */

        .ps-page .tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(115px, 1fr));
            gap: .65rem;
        }

        .ps-page .tile {
            min-width: 0;
            padding: .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: var(--bs-tertiary-bg);
            overflow-wrap: anywhere;
        }

        .ps-page .tile b {
            display: block;
            margin-bottom: .18rem;
            color: var(--bs-emphasis-color);
            font-size: 1.05rem;
            line-height: 1.4;
        }

        .ps-page .tile small {
            display: block;
            color: var(--bs-secondary-color);
            font-size: .72rem;
        }

        /* Forms */

        .ps-page .form-label {
            margin-bottom: .32rem;
            color: var(--bs-body-color);
            font-size: .77rem;
            font-weight: 600;
        }

        .ps-page .form-control,
        .ps-page .form-select,
        .ps-page .input-group-text {
            min-height: 36px;
            padding: .45rem .65rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 7px;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-size: .82rem;
        }

        .ps-page .input-group-text {
            background: var(--bs-tertiary-bg);
        }

        .ps-page .form-control::placeholder {
            color: var(--bs-secondary-color);
            opacity: .8;
        }

        .ps-page .form-control:focus,
        .ps-page .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 .15rem var(--bs-primary-bg-subtle);
        }

        .ps-page textarea.form-control {
            min-height: 75px;
        }

        .ps-page .action-panel {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--ps-radius);
            background: var(--bs-body-bg);
        }

        .ps-page .action-panel h3 {
            margin: 0 0 .4rem;
            color: var(--bs-emphasis-color);
            font-size: .9rem;
            font-weight: 700;
        }

        .ps-page .action-panel p {
            color: var(--bs-secondary-color);
            font-size: .79rem;
        }

        .ps-page .note-box,
        .ps-page .ok-box,
        .ps-page .live {
            padding: .75rem .85rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
        }

        .ps-page .live {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .ps-page .live b {
            font-size: .92rem;
        }

        /* Tables */

        .ps-page .ps-table {
            width: 100%;
            margin: 0;
            color: var(--bs-body-color);
            --bs-table-bg: var(--bs-body-bg);
            --bs-table-color: var(--bs-body-color);
            --bs-table-hover-bg: var(--bs-tertiary-bg);
            --bs-table-hover-color: var(--bs-body-color);
            --bs-table-border-color: var(--bs-border-color);
        }

        .ps-page .ps-table thead th {
            padding: .7rem .75rem;
            border-bottom: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            font-size: .71rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ps-page .ps-table td {
            padding: .62rem .75rem;
            border-color: var(--bs-border-color);
            vertical-align: middle;
            font-size: .8rem;
        }

        .ps-page .ps-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .ps-page .scroll-box {
            max-height: 400px;
            overflow: auto;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
        }

        .ps-page .scroll-box .ps-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .ps-page .scroll-box .ps-table thead th {
            box-shadow: inset 0 -1px var(--bs-border-color);
        }

        .ps-page .table-responsive {
            scrollbar-width: thin;
        }

        /* Filters */

        .ps-page .chips {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .ps-page .chip {
            padding: .3rem .55rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-size: .7rem;
            font-weight: 600;
            cursor: pointer;
        }

        .ps-page .chip:hover {
            background: var(--bs-tertiary-bg);
        }

        .ps-page .chip.on {
            border-color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
        }

        /* Day-type badges */

        .ps-page .day-success {
            background: var(--bs-success-bg-subtle);
            color: var(--bs-success-text-emphasis);
            border-color: var(--bs-success-border-subtle);
        }

        .ps-page .day-warning {
            background: var(--bs-warning-bg-subtle);
            color: var(--bs-warning-text-emphasis);
            border-color: var(--bs-warning-border-subtle);
        }

        .ps-page .day-danger {
            background: var(--bs-danger-bg-subtle);
            color: var(--bs-danger-text-emphasis);
            border-color: var(--bs-danger-border-subtle);
        }

        .ps-page .day-primary {
            background: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
            border-color: var(--bs-primary-border-subtle);
        }

        .ps-page .day-secondary {
            background: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            border-color: var(--bs-border-color);
        }

        .ps-page .day-purple {
            background: #f3e8ff;
            color: #6b21a8;
            border-color: #e9d5ff;
        }

        [data-bs-theme="dark"] .ps-page .day-purple,
        .dark-mode .ps-page .day-purple {
            background: #3b2450;
            color: #e9d5ff;
            border-color: #674080;
        }

        /* Attendance calendar */

        .ps-page .cal {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 5px;
        }

        .ps-page .cal .dow {
            padding: .25rem 0;
            text-align: center;
            color: var(--bs-secondary-color);
            font-size: .69rem;
            font-weight: 700;
        }

        .ps-page .cal .d {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
            height: 43px;
            padding: 4px 5px;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .ps-page .cal .d i {
            color: var(--bs-secondary-color);
            font-size: .63rem;
            font-style: normal;
        }

        .ps-page .cal .d b {
            color: var(--bs-body-color);
            font-size: .72rem;
            text-align: right;
        }

        .ps-page .cal .blank {
            visibility: hidden;
        }

        .ps-page .cal .d.day-success {
            background: var(--bs-success-bg-subtle);
            border-color: var(--bs-success-border-subtle);
        }

        .ps-page .cal .d.day-warning {
            background: var(--bs-warning-bg-subtle);
            border-color: var(--bs-warning-border-subtle);
        }

        .ps-page .cal .d.day-danger {
            background: var(--bs-danger-bg-subtle);
            border-color: var(--bs-danger-border-subtle);
        }

        .ps-page .cal .d.day-primary {
            background: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary-border-subtle);
        }

        .ps-page .cal .d.day-purple {
            background: #f3e8ff;
            border-color: #e9d5ff;
        }

        [data-bs-theme="dark"] .ps-page .cal .d.day-purple,
        .dark-mode .ps-page .cal .d.day-purple {
            background: #3b2450;
            border-color: #674080;
        }

        .ps-page .legend {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin-top: .8rem;
            color: var(--bs-secondary-color);
            font-size: .72rem;
        }

        .ps-page .legend span {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .ps-page .legend em {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid var(--bs-border-color);
            border-radius: 3px;
        }

        .ps-page .legend .day-success { background: var(--bs-success-bg-subtle); }
        .ps-page .legend .day-warning { background: var(--bs-warning-bg-subtle); }
        .ps-page .legend .day-danger { background: var(--bs-danger-bg-subtle); }
        .ps-page .legend .day-primary { background: var(--bs-primary-bg-subtle); }
        .ps-page .legend .day-secondary { background: var(--bs-tertiary-bg); }
        .ps-page .legend .day-purple { background: #f3e8ff; }

        .ps-page .alert {
            border-radius: 8px;
            font-size: .82rem;
        }

        .ps-page .alert-info {
            color: var(--bs-info-text-emphasis);
            background: var(--bs-info-bg-subtle);
            border-color: var(--bs-info-border-subtle);
        }

        @media (max-width: 767.98px) {
            .ps-page .ps-hero {
                padding: .9rem;
            }

            .ps-page .hero-net {
                width: 100%;
                min-width: 0;
                text-align: left;
            }

            .ps-page .hero-net strong {
                font-size: 1.25rem;
            }

            .ps-page .formula {
                display: grid;
                grid-template-columns: 1fr;
            }

            .ps-page .formula .op {
                display: none;
            }

            .ps-page .fbox {
                min-width: 0;
            }

            .ps-page .tiles {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ps-page .step b {
                font-size: .67rem;
            }

            .ps-page .step small {
                font-size: .6rem;
            }

            .ps-page .step .dot {
                width: 26px;
                height: 26px;
            }

            .ps-page .step::before {
                top: 12px;
            }

            .ps-page .section-top {
                align-items: flex-start !important;
            }
        }

        @media (max-width: 420px) {
            .ps-page .cal {
                gap: 3px;
            }

            .ps-page .cal .d {
                height: 36px;
                padding: 3px;
            }

            .ps-page .cal .d i,
            .ps-page .cal .d b {
                font-size: .61rem;
            }
        }
    </style>

    <div class="container-fluid py-3 ps-page">

        {{-- Employee and payroll header --}}
        <div class="ps-hero mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar">{{ $initial }}</span>

                    <div class="min-w-0">
                        <h1 class="page-heading">{{ $name }}</h1>

                        <div class="hero-meta mt-1">
                            {{ $payroll->user?->email ?: 'Email unavailable' }}
                            <span class="mx-1">·</span>
                            {{ $monthStart->format('F Y') }}
                            @if($payroll->payslip_no)
                            <span class="mx-1">·</span>
                            Payslip: {{ $payroll->payslip_no }}
                            @endif
                        </div>

                        <span class="status-badge status-{{ $statusClass }} mt-2">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <div class="hero-net">
                    <small>Final payable salary</small>
                    <strong>{{ $fmt($payroll->final_salary) }}</strong>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <a
                href="{{ route('admin.payroll.index', ['month' => $monthStart->format('Y-m')]) }}"
                class="btn-x btn-neutral"
                >
                &larr; Back to Payroll
            </a>

            @if(in_array($status, ['approved', 'paid'], true))
            <a
            href="{{ route('admin.payroll.payslip', $payroll->id) }}"
            target="_blank"
            rel="noopener"
            class="btn-x btn-primary-x"
            >
            View / Print Payslip
        </a>
        @endif
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

@if($payroll->rejection_reason)
<div class="alert alert-danger py-2 px-3">
    <strong>Rejection reason:</strong>
    {{ $payroll->rejection_reason }}
</div>
@endif

@if($payroll->approval_notes)
<div class="alert alert-info py-2 px-3">
    <strong>Approval notes:</strong>
    {{ $payroll->approval_notes }}
</div>
@endif

{{-- Approval progress --}}
<div class="card mb-3">
    <div class="card-body py-3 px-3">
        <div class="sec-title mb-3">Payroll approval progress</div>

        <div class="stepper">
            @foreach($steps as $i => [$label, $time, $who])
            @php
            $cls = $i < $stage
            ? 'done'
            : ($i === $stage ? 'cur' : '');
            @endphp

            <div class="step {{ $cls }}">
                <div class="dot">
                    {{ $i < $stage ? '✓' : $i + 1 }}
                </div>

                <b>{{ $label }}</b>

                <small>
                    {{ $time ? Carbon::parse($time)->format('d M, h:i A') : '' }}
                </small>

                @if($who)
                <small>by {{ $who }}</small>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Salary calculation --}}
<div class="card mb-3">
    <div class="card-body py-3 px-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="sec-title">
                <span class="ico">₹</span>
                Salary calculation
            </h2>

            <span class="section-note">
                Daily rate: {{ $fmt($payroll->daily_rate) }}
            </span>
        </div>

        <div class="formula">
            <div class="fbox">
                <small>Monthly salary</small>
                <b>{{ $fmt($payroll->monthly_salary) }}</b>
            </div>

            <span class="op">−</span>

            <div class="fbox">
                <small>Attendance deduction</small>
                <b>{{ $fmt($payroll->deduction_amount) }}</b>
            </div>

            <span class="op">−</span>

            <div class="fbox">
                <small>Other deductions</small>
                <b>{{ $fmt($payroll->other_deductions) }}</b>
            </div>

            <span class="op">+</span>

            <div class="fbox">
                <small>Bonus</small>
                <b>{{ $fmt($payroll->bonus_amount) }}</b>
            </div>

            <span class="op">=</span>

            <div class="fbox final-box">
                <small>Final salary</small>
                <b>{{ $fmt($payroll->final_salary) }}</b>
            </div>
        </div>
    </div>
</div>

{{-- Attendance summary --}}
<div class="card mb-3">
    <div class="card-body py-3 px-3">
        <h2 class="sec-title mb-3">
            <span class="ico">A</span>
            Attendance summary
        </h2>

        <div class="tiles">
            <div class="tile">
                <b>{{ $payroll->scheduled_working_days }}</b>
                <small>Working days</small>
            </div>

            <div class="tile">
                <b>{{ $payroll->present_days }}</b>
                <small>Present</small>
            </div>

            <div class="tile">
                <b>{{ $trim($payroll->half_days) }}</b>
                <small>Half days</small>
            </div>

            <div class="tile">
                <b>{{ $payroll->absent_days }}</b>
                <small>Absent</small>
            </div>

            <div class="tile">
                <b>{{ $payroll->paid_leave_days }}</b>
                <small>Paid leave</small>
            </div>

            <div class="tile">
                <b>{{ $payroll->unpaid_leave_days }}</b>
                <small>Unpaid leave</small>
            </div>

            <div class="tile">
                <b>{{ $trim($payroll->overtime_hours) }}</b>
                <small>Overtime hours</small>
            </div>
        </div>

        <small class="d-block mt-3 text-muted">
            Overtime is recorded for reporting only; it is not added to salary.
        </small>
    </div>
</div>

{{-- Editable payroll actions --}}
@if($editable)

@if($hasAdjustRoute)
<div class="card mb-3">
    <div class="card-body px-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="sec-title mb-1">
                    <span class="ico">✎</span>
                    Review &amp; adjust
                </h2>

                <p class="section-note mb-0">
                    Review attendance deductions, bonus and other deductions before submitting.
                </p>
            </div>
        </div>

        <form
        method="POST"
        action="{{ route('admin.payroll.adjust', $payroll->id) }}"
        id="adjustForm"
        class="js-once"
        data-monthly="{{ (float) $payroll->monthly_salary }}"
        data-fixed="{{ $fixedDeduction }}"
        >
        @csrf

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="bonus_amount">Bonus</label>

                <div class="input-group">
                    <span class="input-group-text">₹</span>

                    <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="bonus_amount"
                    id="bonus_amount"
                    class="form-control"
                    value="{{ old('bonus_amount', (float) $payroll->bonus_amount) }}"
                    required
                    >
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="other_deductions">
                    Other deductions
                </label>

                <div class="input-group">
                    <span class="input-group-text">₹</span>

                    <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="other_deductions"
                    id="other_deductions"
                    class="form-control"
                    value="{{ old('other_deductions', (float) $payroll->other_deductions) }}"
                    required
                    >
                </div>
            </div>
        </div>

        @if($adjustable->isEmpty())
        <div class="ok-box mb-3">
            No absent or leave days in this month. Nothing to review.
        </div>
        @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="fw-semibold">
                Days to review ({{ $adjustable->count() }})
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select id="bulkAct" class="form-select" style="width:auto">
                    <option value="keep">Absent - no deduction</option>
                    <option value="deduct">Absent - deduct full day</option>
                    <option value="paid_leave">Paid leave</option>
                    <option value="unpaid_leave">Unpaid leave - deduct</option>
                </select>

                <button type="button" class="btn-x btn-neutral" id="bulkApply">
                    Apply to all
                </button>
            </div>
        </div>

        <div class="table-responsive scroll-box mb-3">
            <table class="table ps-table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Current</th>
                        <th class="text-end">Daily rate</th>
                        <th>Treat as</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($adjustable as $it)
                    @php
                    $m = $dayMeta[$it->day_type] ?? [
                    '?',
                    ucwords(str_replace('_', ' ', $it->day_type)),
                    'secondary'
                    ];

                    $cur = $currentAction($it);
                    @endphp

                    <tr>
                        <td>
                            {{ Carbon::parse($it->work_date)->format('D, d M Y') }}

                            <small class="d-block text-muted">
                                {{ $it->attendance_id ? 'Admin-approved record' : 'No attendance found' }}
                            </small>
                        </td>

                        <td>
                            <span class="day-badge day-{{ $m[2] }}">
                                {{ $m[1] }}
                            </span>
                        </td>

                        <td class="text-end">
                            {{ $fmt($it->daily_rate) }}
                        </td>

                        <td>
                            <select
                            name="days[{{ $it->id }}]"
                            class="form-select day-act"
                            data-rate="{{ (float) $it->daily_rate }}"
                            >
                            <option value="keep" @selected($cur === 'keep')>
                                Absent - no deduction
                            </option>

                            <option value="deduct" @selected($cur === 'deduct')>
                                Absent - deduct full day
                            </option>

                            <option value="paid_leave" @selected($cur === 'paid_leave')>
                                Paid leave
                            </option>

                            <option value="unpaid_leave" @selected($cur === 'unpaid_leave')>
                                Unpaid leave - deduct
                            </option>
                        </select>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="live mb-3">
        <span>
            Attendance deduction:
            <b id="lvDed">-</b>
        </span>

        <span>
            New final salary:
            <b id="lvFinal">-</b>
        </span>
    </div>

    <button type="submit" class="btn-x btn-primary-x" id="adjustBtn">
        Save adjustments
    </button>
</form>
</div>
</div>
@else
<div class="note-box mb-3">
    The adjust route (<code>admin.payroll.adjust</code>) is not registered yet,
    so adjustments are unavailable.
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="action-panel">
            <h3>Submit for approval</h3>

            <p>
                Review the calculations, then send this payroll to the approver.
            </p>

            <form
            method="POST"
            action="{{ route('admin.payroll.submit', $payroll->id) }}"
            class="js-once"
            onsubmit="return confirm('Submit this payroll for approval?');"
            >
            @csrf

            <button class="btn-x btn-primary-x" type="submit">
                Submit for approval
            </button>
        </form>
    </div>
</div>

<div class="col-lg-6">
    <div class="action-panel">
        <h3>Regenerate from attendance</h3>

        <p>
            Recalculates from approved attendance. Bonus and other deductions are kept,
            but day-level adjustments are reset.
        </p>

        <form
        method="POST"
        action="{{ route('admin.payroll.generate') }}"
        class="js-once"
        onsubmit="return confirm('Regenerate this payroll? Day-level adjustments will be reset.');"
        >
        @csrf

        <input type="hidden" name="user_id" value="{{ $payroll->user_id }}">
        <input type="hidden" name="month" value="{{ $monthStart->format('Y-m') }}">

        <button class="btn-x btn-neutral" type="submit">
            Regenerate
        </button>
    </form>
</div>
</div>
</div>
@endif

{{-- Approval / rejection --}}
@if($status === 'pending')

@if($cannotApprove)
<div class="note-box mb-3">
    You generated this payroll, so another approver must approve or reject it.
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="action-panel">
            <h3>Approve payroll</h3>

            <form
            method="POST"
            action="{{ route('admin.payroll.approve', $payroll->id) }}"
            class="js-once"
            onsubmit="return confirm('Approve this final salary?');"
            >
            @csrf

            <label for="approval_notes" class="form-label">
                Approval notes (optional)
            </label>

            <textarea
            id="approval_notes"
            name="approval_notes"
            class="form-control mb-3"
            rows="3"
            maxlength="2000"
            >{{ old('approval_notes') }}</textarea>

            <button
            type="submit"
            class="btn-x btn-success-x"
            @disabled($cannotApprove)
            >
            Approve final salary
        </button>
    </form>
</div>
</div>

<div class="col-lg-6">
    <div class="action-panel">
        <h3>Reject payroll</h3>

        <form
        method="POST"
        action="{{ route('admin.payroll.reject', $payroll->id) }}"
        class="js-once"
        onsubmit="return confirm('Reject this payroll for correction?');"
        >
        @csrf

        <label for="rejection_reason" class="form-label">
            Reason for rejection
        </label>

        <textarea
        id="rejection_reason"
        name="rejection_reason"
        class="form-control mb-3"
        rows="3"
        maxlength="2000"
        required
        >{{ old('rejection_reason') }}</textarea>

        <button type="submit" class="btn-x btn-danger-x">
            Reject payroll
        </button>
    </form>
</div>
</div>
</div>
@endif

{{-- Record payment --}}
@if($status === 'approved')
<div class="card mb-3">
    <div class="card-body px-3">
        <h2 class="sec-title mb-1">
            <span class="ico">₹</span>
            Record salary payment
        </h2>

        <p class="section-note mb-3">
            Use this only after {{ $fmt($payroll->final_salary) }}
            has actually been paid to {{ $name }}.
        </p>

        <form
        method="POST"
        action="{{ route('admin.payroll.markPaid', $payroll->id) }}"
        class="js-once"
        onsubmit="return confirm('Mark this approved payroll as paid?');"
        >
        @csrf

        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="payment_method">
                    Payment method
                </label>

                <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="">Select...</option>

                    @foreach($paymentMethods as $val => $label)
                    <option
                    value="{{ $val }}"
                    @selected(old('payment_method') === $val)
                    >
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="payment_reference">
                Reference / transaction ID (optional)
            </label>

            <input
            type="text"
            name="payment_reference"
            id="payment_reference"
            class="form-control"
            maxlength="100"
            value="{{ old('payment_reference') }}"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label" for="paid_at">Paid on</label>

            <input
            type="date"
            name="paid_at"
            id="paid_at"
            class="form-control"
            max="{{ now()->toDateString() }}"
            value="{{ old('paid_at', now()->toDateString()) }}"
            >
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn-x btn-primary-x w-100">
                Mark as paid
            </button>
        </div>
    </div>
</form>
</div>
</div>
@endif

{{-- Payment receipt --}}
@if($status === 'paid')
<div class="card mb-3">
    <div class="card-body px-3">
        <h2 class="sec-title mb-3">
            <span class="ico">✓</span>
            Payment receipt
        </h2>

        <div class="tiles">
            <div class="tile">
                <b>{{ $fmt($payroll->final_salary) }}</b>
                <small>Amount paid</small>
            </div>

            <div class="tile">
                <b>
                    {{ $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('d M Y') : '-' }}
                </b>
                <small>Paid on</small>
            </div>

            <div class="tile">
                <b>
                    {{ $payroll->payment_method
                    ? ($paymentMethods[$payroll->payment_method] ?? ucfirst($payroll->payment_method))
                    : '-' }}
                </b>
                <small>Payment method</small>
            </div>

            <div class="tile">
                <b style="font-size:.9rem">
                    {{ $payroll->payment_reference ?: '-' }}
                </b>
                <small>Reference</small>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Calendar and daily breakdown: two sibling columns --}}
<div class="row g-3 mb-3">

    {{-- Attendance calendar --}}
    <div class="col-xl-5 col-lg-5">
        <div class="card h-100">
            <div class="card-body px-3">
                <h2 class="sec-title mb-3">
                    <span class="ico">C</span>
                    Attendance calendar
                </h2>

                <div class="section-note mb-3">
                    {{ $monthStart->format('F Y') }}
                </div>

                @if($items->isEmpty())
                <div class="text-muted py-4 text-center">
                    No daily breakdown available.
                </div>
                @else
                <div class="cal">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dn)
                    <div class="dow">{{ $dn }}</div>
                    @endforeach

                    @for($i = 0; $i < $leadBlanks; $i++)
                    <div class="d blank"></div>
                    @endfor

                    @for($day = 1; $day <= $monthStart->daysInMonth; $day++)
                        @php
                        $dateKey = $monthStart->copy()->day($day)->format('Y-m-d');
                        $it = $byDate->get($dateKey);
                        $meta = $it ? ($dayMeta[$it->day_type] ?? null) : null;
                        @endphp

                        <div
                        class="d {{ $meta ? 'day-' . $meta[2] : '' }}"
                        title="{{ $meta ? $meta[1] : 'No payroll item' }}"
                        >
                        <i>{{ $day }}</i>
                        <b>{{ $meta[0] ?? '' }}</b>
                    </div>
                    @endfor
                </div>

                <div class="legend">
                    @foreach($dayMeta as $meta)
                    <span>
                        <em class="day-{{ $meta[2] }}"></em>
                        {{ $meta[1] }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Daily breakdown --}}
    <div class="col-xl-7 col-lg-7">
        <div class="card h-100">
            <div class="card-body px-3 pb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 section-top">
                    <h2 class="sec-title">
                        <span class="ico">D</span>
                        Daily breakdown
                    </h2>

                    <div class="chips" id="typeChips">
                        <button type="button" class="chip on" data-type="all">
                            All {{ $items->count() }}
                        </button>

                        @foreach($dayMeta as $key => $meta)
                        @if(($typeCounts[$key] ?? 0) > 0)
                        <button type="button" class="chip" data-type="{{ $key }}">
                            {{ $meta[1] }} {{ $typeCounts[$key] }}
                        </button>
                        @endif
                        @endforeach
                    </div>
                </div>

                <div class="table-responsive scroll-box" style="max-height:440px">
                    <table class="table ps-table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day type</th>
                                <th class="text-end">Minutes</th>
                                <th class="text-end">OT hrs</th>
                                <th class="text-end">Deduction</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($items->sortBy('work_date') as $item)
                            @php
                            $meta = $dayMeta[$item->day_type] ?? [
                            '?',
                            ucwords(str_replace('_', ' ', $item->day_type)),
                            'secondary'
                            ];
                            @endphp

                            <tr data-type="{{ $item->day_type }}">
                                <td>
                                    {{ Carbon::parse($item->work_date)->format('D, d M') }}
                                </td>

                                <td>
                                    <span class="day-badge day-{{ $meta[2] }}">
                                        {{ $meta[1] }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    {{ $item->working_minutes }}
                                </td>

                                <td class="text-end">
                                    {{ $trim($item->overtime_hours) }}
                                </td>

                                <td class="text-end {{ (float) $item->deduction_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ (float) $item->deduction_amount > 0
                                    ? '- ' . $fmt($item->deduction_amount)
                                    : '-' }}
                                </td>

                                <td>
                                    <small class="text-muted">
                                        {{ $item->remarks ?: '-' }}
                                    </small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No daily breakdown available.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    (function () {
        'use strict';

            // Prevent duplicate form submissions.
        document.querySelectorAll('.ps-page form.js-once').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                var submitButton = form.querySelector('button[type="submit"]');

                if (submitButton) {
                    setTimeout(function () {
                        submitButton.disabled = true;
                        submitButton.textContent = 'Please wait...';
                    }, 0);
                }
            });
        });

            // Auto-dismiss success/error messages.
        document.querySelectorAll('.ps-page .auto-dismiss').forEach(function (element) {
            setTimeout(function () {
                element.style.transition = 'opacity .5s';
                element.style.opacity = '0';

                setTimeout(function () {
                    element.remove();
                }, 500);
            }, 6000);
        });

            // Filter the daily breakdown by day type.
        var chips = document.querySelectorAll('#typeChips .chip');

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (button) {
                    button.classList.remove('on');
                });

                chip.classList.add('on');

                var selectedType = chip.getAttribute('data-type');

                document.querySelectorAll('.ps-page tr[data-type]').forEach(function (row) {
                    row.style.display = (
                        selectedType === 'all' ||
                        row.getAttribute('data-type') === selectedType
                    ) ? '' : 'none';
                });
            });
        });

            // Live preview of attendance deduction and final salary.
        var form = document.getElementById('adjustForm');

        if (!form) {
            return;
        }

        var monthly = parseFloat(form.dataset.monthly) || 0;
        var fixed = parseFloat(form.dataset.fixed) || 0;
        var selects = form.querySelectorAll('.day-act');

        var deductionOutput = document.getElementById('lvDed');
        var finalOutput = document.getElementById('lvFinal');
        var bonusInput = document.getElementById('bonus_amount');
        var otherInput = document.getElementById('other_deductions');

        var money = function (amount) {
            return '₹' + amount.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        function recalculate() {
            var deduction = fixed;

            selects.forEach(function (select) {
                if (select.value === 'deduct' || select.value === 'unpaid_leave') {
                    deduction += parseFloat(select.dataset.rate) || 0;
                }
            });

            deduction = Math.round(deduction * 100) / 100;

            var other = parseFloat(otherInput.value) || 0;
            var bonus = parseFloat(bonusInput.value) || 0;

            var finalSalary = Math.max(
                0,
                Math.round((monthly - deduction - other + bonus) * 100) / 100
            );

            deductionOutput.textContent = money(deduction);
            finalOutput.textContent = money(finalSalary);
        }

        selects.forEach(function (select) {
            select.addEventListener('change', recalculate);
        });

        [bonusInput, otherInput].forEach(function (input) {
            input.addEventListener('input', recalculate);
        });

        var bulkApply = document.getElementById('bulkApply');
        var bulkAction = document.getElementById('bulkAct');

        if (bulkApply && bulkAction) {
            bulkApply.addEventListener('click', function () {
                var selectedAction = bulkAction.value;

                selects.forEach(function (select) {
                    select.value = selectedAction;
                });

                recalculate();
            });
        }

        recalculate();
    })();
</script>
</x-admin>