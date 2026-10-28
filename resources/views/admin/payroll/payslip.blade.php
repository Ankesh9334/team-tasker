
{{-- resources/views/admin/payroll/payslip.blade.php --}}
@php
use Carbon\Carbon;

$company = config('company') ?? [];

$cName  = $company['name'] ?? 'CoxFuture Technologies Pvt Ltd';
$cTag   = $company['tagline'] ?? 'Innovating Future';
$cAddr  = $company['address'] ?? 'LG-05, H-202, Dallas Business Park 1, Sector 63, Noida, Uttar Pradesh 201309';
$cMail  = $company['email'] ?? 'info@coxfuture.com';
$cPhone = $company['phone'] ?? '+91 92177 90770';
$cWeb   = $company['website'] ?? 'coxfuture.com';
$cur    = $company['currency_symbol'] ?? '₹';

/*
|--------------------------------------------------------------------------
| Logo
|--------------------------------------------------------------------------
*/
$logoCandidates = [
$company['logo'] ?? null,
'images/coxfuture-logo.png',
'images/coxfuture-logo.png.png',
];

$logoRel = collect($logoCandidates)->first(
fn ($path) => is_string($path)
&& $path !== ''
&& is_file(public_path($path))
);

$logo = null;

if ($logoRel) {
    $logoPath = public_path($logoRel);
    $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));

    $mime = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'svg'  => 'image/svg+xml',
    'webp' => 'image/webp',
    ][$ext] ?? 'application/octet-stream';

    $logo = 'data:' . $mime . ';base64,' . base64_encode(
    file_get_contents($logoPath)
    );
}

/*
|--------------------------------------------------------------------------
| Formatting helpers
|--------------------------------------------------------------------------
*/
$fmt = function ($value) use ($cur) {
    $value = round((float) $value, 2);
    $negative = $value < 0;

    [$whole, $decimal] = explode(
    '.',
    number_format(abs($value), 2, '.', '')
    );

    if (strlen($whole) > 3) {
        $lastThree = substr($whole, -3);
        $remaining = substr($whole, 0, -3);

        $whole = preg_replace(
        '/\B(?=(\d{2})+(?!\d))/',
        ',',
        $remaining
        ) . ',' . $lastThree;
    }

    return ($negative ? '-' : '')
    . $cur . ' ' . $whole . '.' . $decimal;
};

$num = fn ($value) => rtrim(
rtrim(number_format((float) $value, 2, '.', ''), '0'),
'.'
);

/*
|--------------------------------------------------------------------------
| Payroll period
|--------------------------------------------------------------------------
*/
$monthStart = Carbon::parse($payroll->payroll_month)->startOfMonth();
$monthLabel = $monthStart->format('F Y');

$periodText = $monthStart->format('d-M-Y')
. ' to '
. $monthStart->copy()->endOfMonth()->format('d-M-Y');

/*
|--------------------------------------------------------------------------
| Salary amounts
|--------------------------------------------------------------------------
*/
$monthlySalary   = (float) $payroll->monthly_salary;
$bonus           = (float) $payroll->bonus_amount;
$attDeduction    = (float) $payroll->deduction_amount;
$otherDeductions = (float) $payroll->other_deductions;

$totalEarnings   = $monthlySalary + $bonus;
$totalDeductions = $attDeduction + $otherDeductions;
$netPay          = (float) $payroll->final_salary;

$words = $amountInWords ?? (
class_exists(\App\Support\MoneyWords::class)
? \App\Support\MoneyWords::words((int) round($netPay * 100))
: ''
);

/*
|--------------------------------------------------------------------------
| Attendance deduction breakdown
|--------------------------------------------------------------------------
*/
$items = $payroll->items;

$labels = [
'absent'      => 'Absent days',
'unpaid_leave' => 'Unpaid leave',
'half_day'    => 'Half days',
];

$breakdown = [];

$isNotEmployed = fn ($item) => str_contains(
strtolower((string) $item->remarks),
'not active'
);

foreach ($labels as $type => $label) {
    $rows = $items
    ->where('day_type', $type)
    ->reject($isNotEmployed);

    $amount = (float) $rows->sum('deduction_amount');

    if ($amount > 0) {
        $count = $rows
        ->where('deduction_amount', '>', 0)
        ->count();

        $breakdown[] = [
        'label'  => $label . ' (' . $count . ')',
        'amount' => $amount,
        ];
    }
}

$notEmployedRows = $items->filter($isNotEmployed);
$notEmployedAmt  = (float) $notEmployedRows->sum('deduction_amount');

if ($notEmployedAmt > 0) {
    $breakdown[] = [
    'label'  => 'Before joining / after exit (' . $notEmployedRows->count() . ' days)',
    'amount' => $notEmployedAmt,
    ];
}

$breakdownTotal = round(
collect($breakdown)->sum('amount'),
2
);

$diff = round($attDeduction - $breakdownTotal, 2);

if ($diff > 0) {
    $breakdown[] = [
    'label'  => empty($breakdown)
    ? 'Attendance deduction'
    : 'Other attendance adjustments',
    'amount' => $diff,
    ];
}

/*
|--------------------------------------------------------------------------
| Employee details
|--------------------------------------------------------------------------
*/
$u = $payroll->user;

$employeeCode = $u->employee_code
?: ('EMP-' . str_pad($u->id, 4, '0', STR_PAD_LEFT));

$employeeDetails = [
'Employee name' => $u->name,
'Employee ID'   => $employeeCode,
'Designation'   => $u->designation ?: '-',
'Department'    => $u->department ?: '-',
'Date of joining' => $u->joining_date
? Carbon::parse($u->joining_date)->format('d-M-Y')
: null,
'PAN'           => $u->pan ?: null,
'UAN'           => $u->uan ?: null,
'Bank account'  => $u->bank_account ?: null,
'Email'         => $u->email ?: null,
'Phone'         => $u->phone ?: null,
'Daily rate'    => $fmt($payroll->daily_rate),
];

$employeeDetails = array_filter(
$employeeDetails,
fn ($value) => $value !== null && $value !== ''
);

/*
|--------------------------------------------------------------------------
| Payment and attendance details
|--------------------------------------------------------------------------
*/
$lopDays = (float) $payroll->unpaid_leave_days
+ (float) $payroll->absent_days
+ (0.5 * (float) $payroll->half_days);

$workDays = (float) $payroll->scheduled_working_days;
$paidDays = max(0, $workDays - $lopDays);

$mode = $payroll->payment_method
? ucwords(str_replace('_', ' ', $payroll->payment_method))
: '-';

$holidayCount = $items->where('day_type', 'holiday')->count();
$weeklyOffCount = $items->where('day_type', 'weekly_off')->count();
$paidLeaveCount = $items->where('day_type', 'paid_leave')->count();

$isFinal = in_array(
$payroll->status,
['approved', 'paid'],
true
);

$statusLabels = [
'draft'    => 'Draft',
'pending'  => 'Pending',
'approved' => 'Approved',
'paid'     => 'Paid',
'rejected' => 'Rejected',
];

$statusColors = [
'draft'    => '#64748b',
'pending'  => '#b7791f',
'approved' => '#2563eb',
'paid'     => '#15803d',
'rejected' => '#b91c1c',
];

$status = strtolower((string) $payroll->status);
$statusLabel = $statusLabels[$status] ?? ucfirst($status);
$statusColor = $statusColors[$status] ?? '#64748b';

$payslipNo = $payslipNo ?? (
'PS-' . $monthStart->format('Ym') . '-'
. str_pad($payroll->id, 5, '0', STR_PAD_LEFT)
);

/*
|--------------------------------------------------------------------------
| Signature
|--------------------------------------------------------------------------
*/
$signaturePath = $company['signature'] ?? null;

$hasSignature = is_string($signaturePath)
&& $signaturePath !== ''
&& is_file(public_path($signaturePath));
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Payslip - {{ $u->name }} - {{ $monthLabel }}</title>

    <style>
        :root {
            --ink: #202938;
            --muted: #667085;
            --line: #dfe3e8;
            --soft: #f7f8fa;
            --brand: #344054;
            --success: #18794e;
            --success-bg: #eef8f1;
            --danger: #b42318;
            --danger-bg: #fff1f0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #eef0f3;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            width: min(210mm, calc(100% - 24px));
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 8px 14px;
            border: 1px solid #344054;
            border-radius: 5px;
            background: #344054;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.alt {
            background: #fff;
            color: var(--ink);
            border-color: #cbd2da;
        }

        .page {
            position: relative;
            width: 210mm;
            min-height: 275mm;
            margin: 12px auto 24px;
            padding: 10mm 12mm;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 3px 18px rgba(16, 24, 40, .10);
        }

        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            z-index: 0;
            transform: translate(-50%, -50%) rotate(-28deg);
            color: #667085;
            opacity: .07;
            font-size: 78px;
            font-weight: 800;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
        }

        .page > * {
            position: relative;
            z-index: 1;
        }

        .head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #344054;
        }

        .logo {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            object-fit: contain;
        }

        .co {
            flex: 1;
            min-width: 0;
        }

        .co h1 {
            margin: 0 0 3px;
            color: var(--ink);
            font-size: 19px;
            line-height: 1.25;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .tag {
            margin-bottom: 3px;
            color: var(--muted);
            font-size: 10px;
        }

        .small {
            color: var(--muted);
            font-size: 9.5px;
            overflow-wrap: anywhere;
        }

        .period {
            width: 51mm;
            flex-shrink: 0;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 5px;
            background: var(--soft);
        }

        .period-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
        }

        .period-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 20px;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .period-month {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 700;
        }

        .period-sub {
            margin-top: 2px;
            color: var(--muted);
            font-size: 9.5px;
            overflow-wrap: anywhere;
        }

        .bar {
            margin-top: 13px;
            padding: 6px 9px;
            border: 1px solid var(--line);
            background: #f3f4f6;
            color: #344054;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .2px;
            text-transform: uppercase;
        }

        .emp {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 1px solid var(--line);
            border-top: 0;
        }

        .emp-item {
            display: grid;
            grid-template-columns: 100px minmax(0, 1fr);
            gap: 8px;
            min-width: 0;
            padding: 6px 9px;
            border-bottom: 1px solid var(--line);
        }

        .emp-item:nth-child(odd) {
            border-right: 1px solid var(--line);
        }

        .emp-item:last-child,
        .emp-item:nth-last-child(2):nth-child(odd) {
            border-bottom: 0;
        }

        .emp .k {
            color: var(--muted);
            font-size: 10px;
        }

        .emp .v {
            min-width: 0;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .cols {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 13px;
        }

        .side {
            display: flex;
            min-width: 0;
            flex-direction: column;
            border: 1px solid var(--line);
            border-radius: 4px;
            overflow: hidden;
        }

        .side h3 {
            margin: 0;
            padding: 7px 10px;
            background: #f3f4f6;
            color: #344054;
            border-bottom: 1px solid var(--line);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .rows {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-height: 95px;
            padding: 3px 0;
        }

        .row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 9px;
        }

        .row span:first-child {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .row span:last-child {
            flex-shrink: 0;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .row.tot {
            margin-top: auto;
            padding: 8px 9px;
            border-top: 1px solid var(--line);
            background: var(--soft);
            font-weight: 700;
        }

        .earn .row.tot {
            color: var(--success);
            background: var(--success-bg);
        }

        .ded .row.tot {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .none {
            padding: 8px 9px;
            color: var(--muted);
        }

        .net {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 13px;
            padding: 10px 12px;
            border: 1px solid #b7d9c4;
            border-radius: 4px;
            background: #f4faf6;
        }

        .net-label {
            font-size: 12px;
            font-weight: 700;
            color: #344054;
        }

        .net-amount {
            color: var(--success);
            font-size: 17px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .words {
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
            overflow-wrap: anywhere;
        }

        .pay {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border: 1px solid var(--line);
            border-top: 0;
        }

        .pay-item {
            min-width: 0;
            padding: 8px 9px;
            border-right: 1px solid var(--line);
        }

        .pay-item:last-child {
            border-right: 0;
        }

        .pay small {
            display: block;
            margin-bottom: 3px;
            color: var(--muted);
            font-size: 9.5px;
        }

        .pay b {
            font-size: 11px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .paysub {
            margin-top: 6px;
            color: var(--muted);
            font-size: 9.5px;
            overflow-wrap: anywhere;
        }

        .sign {
            display: flex;
            justify-content: flex-end;
            margin-top: 28px;
            break-inside: avoid;
        }

        .sign-box {
            width: 48mm;
            min-height: 36px;
            border-bottom: 1px solid #98a2b3;
            text-align: center;
            padding: 0 6px 4px;
        }

        .sign img {
            display: block;
            max-width: 100%;
            max-height: 35px;
            margin: 0 auto 4px;
            object-fit: contain;
        }

        .sign-label {
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
        }

        .foot {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 9px;
            text-align: center;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            html,
            body {
                width: 210mm;
                min-height: 297mm;
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .page {
                width: 210mm;
                min-height: 0;
                margin: 0;
                padding: 10mm 12mm;
                overflow: visible;
                box-shadow: none;
            }

            .head,
            .emp,
            .cols,
            .net,
            .pay,
            .sign,
            .bar {
                break-inside: avoid;
            }

            a {
                color: inherit;
                text-decoration: none;
            }
        }

        @media screen and (max-width: 800px) {
            .toolbar {
                width: calc(100% - 24px);
            }

            .page {
                width: calc(100% - 24px);
                min-height: auto;
                margin: 12px auto;
                padding: 18px;
            }

            .head {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .co {
                flex-basis: calc(100% - 75px);
            }

            .period {
                width: 100%;
            }

            .emp {
                grid-template-columns: 1fr;
            }

            .emp-item,
            .emp-item:nth-child(odd) {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .emp-item:last-child {
                border-bottom: 0;
            }

            .cols {
                grid-template-columns: 1fr;
            }

            .pay {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pay-item:nth-child(2) {
                border-right: 0;
            }

            .pay-item:nth-child(-n+2) {
                border-bottom: 1px solid var(--line);
            }

            .net {
                align-items: flex-start;
                flex-direction: column;
            }

            .net-amount {
                text-align: left;
            }

            .watermark {
                font-size: 42px;
            }
        }

        @media screen and (max-width: 420px) {
            .toolbar {
                justify-content: stretch;
            }

            .toolbar .btn {
                flex: 1;
                padding: 8px;
                font-size: 11px;
            }

            .page {
                padding: 12px;
            }

            .emp-item {
                grid-template-columns: 95px minmax(0, 1fr);
                gap: 6px;
            }

            .row {
                gap: 6px;
                padding: 6px;
            }

            .row span:last-child {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('admin.payroll.show', $payroll->id) }}"
         class="btn alt">
         Back to Payroll
     </a>

     <button type="button" class="btn" onclick="window.print()">
        Print / Save as PDF
    </button>
</div>

<main class="page">
    @unless($isFinal)
    <div class="watermark">{{ strtoupper($statusLabel) }}</div>
    @endunless

    {{-- Company header --}}
    <header class="head">
        @if($logo)
        <img class="logo" src="{{ $logo }}" alt="{{ $cName }}">
        @endif

        <div class="co">
            <h1>{{ $cName }}</h1>

            <div class="tag">
                {{ $cTag }}
                @if(!empty($company['cin']))
                | CIN: {{ $company['cin'] }}
                @endif
            </div>

            <div class="small">{{ $cAddr }}</div>
            <div class="small">{{ $cMail }} | {{ $cPhone }} | {{ $cWeb }}</div>
        </div>

        <div class="period">
            <div class="period-top">
                <span class="period-title">PAYSLIP</span>

                <span class="pill" style="background: {{ $statusColor }};">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="period-month">{{ $monthLabel }}</div>
            <div class="period-sub">{{ $periodText }}</div>
            <div class="period-sub">Payslip No: {{ $payslipNo }}</div>
        </div>
    </header>

    {{-- Employee details --}}
    <section>
        <div class="bar">Employee Details</div>

        <div class="emp">
            @foreach($employeeDetails as $label => $value)
            <div class="emp-item">
                <span class="k">{{ $label }}</span>
                <span class="v">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </section>

    {{-- Earnings and deductions --}}
    <section class="cols">
        <div class="side earn">
            <h3>Earnings</h3>

            <div class="rows">
                <div class="row">
                    <span>Monthly salary</span>
                    <span>{{ $fmt($monthlySalary) }}</span>
                </div>

                @if($bonus > 0)
                <div class="row">
                    <span>Bonus / Incentive</span>
                    <span>{{ $fmt($bonus) }}</span>
                </div>
                @endif

                <div class="row tot">
                    <span>Total gross earnings</span>
                    <span>{{ $fmt($totalEarnings) }}</span>
                </div>
            </div>
        </div>

        <div class="side ded">
            <h3>Deductions</h3>

            <div class="rows">
                @foreach($breakdown as $item)
                <div class="row">
                    <span>{{ $item['label'] }}</span>
                    <span>{{ $fmt($item['amount']) }}</span>
                </div>
                @endforeach

                @if($otherDeductions > 0)
                <div class="row">
                    <span>Other deductions</span>
                    <span>{{ $fmt($otherDeductions) }}</span>
                </div>
                @endif

                @if(empty($breakdown) && $otherDeductions <= 0)
                <div class="none">No deductions this month</div>
                @endif

                <div class="row tot">
                    <span>Total deductions</span>
                    <span>{{ $fmt($totalDeductions) }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Net salary --}}
    <section class="net">
        <div>
            <div class="net-label">Net Pay for {{ $monthLabel }}</div>

            @if($words)
            <div class="words">
                Amount in words: {{ $words }}
            </div>
            @endif
        </div>

        <div class="net-amount">{{ $fmt($netPay) }}</div>
    </section>

    {{-- Payment details --}}
    <section>
        <div class="bar">Payment & Attendance Details</div>

        <div class="pay">
            <div class="pay-item">
                <small>Mode of payment</small>
                <b>{{ $mode }}</b>
            </div>

            <div class="pay-item">
                <small>Scheduled working days</small>
                <b>{{ $num($workDays) }}</b>
            </div>

            <div class="pay-item">
                <small>Paid days</small>
                <b>{{ $num($paidDays) }}</b>
            </div>

            <div class="pay-item">
                <small>Loss of pay days</small>
                <b>{{ $num($lopDays) }}</b>
            </div>
        </div>

        <div class="paysub">
            <strong>Paid days without deduction:</strong>
            Company holidays: {{ $holidayCount }}
            &nbsp;|&nbsp;
            Weekly offs: {{ $weeklyOffCount }}
            &nbsp;|&nbsp;
            Paid leaves: {{ $paidLeaveCount }}
        </div>

        <div class="paysub">
            <strong>Payment date:</strong>
            {{ $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('d-M-Y') : '-' }}

            @if($payroll->payment_reference)
            &nbsp;|&nbsp;
            <strong>Payment reference:</strong>
            {{ $payroll->payment_reference }}
            @endif
        </div>

        @if(!empty($payroll->approval_notes))
        <div class="paysub">
            <strong>Remarks:</strong>
            {{ $payroll->approval_notes }}
        </div>
        @endif
    </section>

    {{-- Authorised signatory --}}
    <div class="sign">
        <div class="sign-box">
            @if($hasSignature)
            <img src="{{ asset($signaturePath) }}" alt="Authorised signature">
            @endif

            <div class="sign-label">Authorised Signatory</div>
        </div>
    </div>

    <footer class="foot">
        This is a computer-generated payslip and does not require a physical signature.
    </footer>
</main>

</body>
</html>
