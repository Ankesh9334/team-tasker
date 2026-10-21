{{-- resources/views/admin/payroll/payslip.blade.php --}}
@php
    use Carbon\Carbon;

    $company = config('company') ?? [];
    $cName   = $company['name'] ?? 'CoxFuture Technologies Pvt Ltd';
    $cTag    = $company['tagline'] ?? 'Innovating Future';
    $cAddr   = $company['address'] ?? 'LG-05, H-202, Dallas Business Park 1, Sector 63, Noida, Uttar Pradesh 201309';
    $cMail   = $company['email'] ?? 'info@coxfuture.com';
    $cPhone  = $company['phone'] ?? '+91 92177 90770';
    $cWeb    = $company['website'] ?? 'coxfuture.com';
    $cur     = $company['currency_symbol'] ?? '₹';

    // ---- Logo: config -> images/coxfuture-logo.png -> images/coxfuture-logo.png.png ----
    // Image ko seedha page mein embed karte hain (base64), isliye APP_URL / asset() ka koi issue nahi.
    $logoRel = collect([$company['logo'] ?? null, 'images/coxfuture-logo.png', 'images/coxfuture-logo.png.png'])
        ->first(fn($p) => $p && is_file(public_path($p)));
    $logo = null;
    if ($logoRel) {
        $ext  = strtolower(pathinfo($logoRel, PATHINFO_EXTENSION));
        $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'webp' => 'image/webp'][$ext] ?? 'image/png';
        $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(public_path($logoRel)));
    }

    // ---- Helpers ----
    // ₹ 1,23,456.00 (Indian grouping)
    $fmt = function ($n) use ($cur) {
        $n = round((float) $n, 2);
        $neg = $n < 0;
        [$r, $p] = explode('.', number_format(abs($n), 2, '.', ''));
        if (strlen($r) > 3) {
            $r = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($r, 0, -3)) . ',' . substr($r, -3);
        }
        return ($neg ? '-' : '') . $cur . ' ' . $r . '.' . $p;
    };
    $num = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');

    $monthStart = Carbon::parse($payroll->payroll_month)->startOfMonth();
    $monthLabel = $monthStart->format('F Y');
    $periodText = $monthStart->format('d-M-Y') . ' to ' . $monthStart->copy()->endOfMonth()->format('d-M-Y');

    // ---- Amounts ----
    $monthlySalary   = (float) $payroll->monthly_salary;
    $bonus           = (float) $payroll->bonus_amount;
    $attDeduction    = (float) $payroll->deduction_amount;
    $otherDeductions = (float) $payroll->other_deductions;
    $totalEarnings   = $monthlySalary + $bonus;
    $totalDeductions = $attDeduction + $otherDeductions;
    $netPay          = (float) $payroll->final_salary;

    $words = $amountInWords ?? (class_exists(\App\Support\MoneyWords::class)
        ? \App\Support\MoneyWords::words((int) round($netPay * 100)) : '');

    // ---- Attendance deduction breakdown (payroll_items se) ----
    $items  = $payroll->items;
    $labels = ['absent' => 'Absent days', 'unpaid_leave' => 'Unpaid leave', 'half_day' => 'Half days'];
    $breakdown = [];
    // Joining se pehle / exit ke baad ke din "Unpaid leave" nahi, alag line mein dikhte hain.
    $isNotEmployed = fn ($i) => str_contains((string) $i->remarks, 'not active');
    foreach ($labels as $type => $label) {
        $rows = $items->where('day_type', $type)->reject($isNotEmployed);
        $amt  = (float) $rows->sum('deduction_amount');
        if ($amt > 0) {
            $breakdown[] = ['label' => $label . ' (' . $rows->where('deduction_amount', '>', 0)->count() . ')', 'amount' => $amt];
        }
    }
    $notEmployedRows = $items->filter($isNotEmployed);
    $notEmployedAmt  = (float) $notEmployedRows->sum('deduction_amount');
    if ($notEmployedAmt > 0) {
        $breakdown[] = ['label' => 'Before joining / after exit (' . $notEmployedRows->count() . ' days)', 'amount' => $notEmployedAmt];
    }
    $diff = round($attDeduction - collect($breakdown)->sum('amount'), 2);
    if ($diff > 0 || (empty($breakdown) && $attDeduction > 0)) {
        $breakdown[] = ['label' => empty($breakdown) ? 'Attendance deduction' : 'Other attendance adjustments', 'amount' => $diff > 0 ? $diff : $attDeduction];
    }

    // ---- Employee details (jo columns hain wahi dikhte hain) ----
    $u = $payroll->user;
    $left = [
        'Employee name' => $u->name,
        'Employee ID'   => $u->employee_code ?? ('EMP-' . str_pad($u->id, 4, '0', STR_PAD_LEFT)),
        'Designation'   => ($u->designation ?? null) ?: '-',
        'Department'    => ($u->department ?? null) ?: '-',
    ];
    $joining = $u->joining_date ?? null;
    $right = collect([
        'Date of joining' => $joining ? Carbon::parse($joining)->format('d-M-Y') : null,
        'PAN'             => $u->pan ?? null,
        'UAN'             => $u->uan ?? null,
        'Bank account'    => $u->bank_account ?? null,
        'Email'           => $u->email,
        'Phone'           => $u->phone ?? null,
        'Daily rate'      => $fmt($payroll->daily_rate),
    ])->filter()->take(4)->all();

    // ---- Payment details ----
    $lopDays  = (float) $payroll->unpaid_leave_days + (float) $payroll->absent_days + 0.5 * (float) $payroll->half_days;
    $workDays = (float) $payroll->scheduled_working_days;
    $paidDays = max(0, $workDays - $lopDays);
    $mode     = $payroll->payment_method ? ucwords(str_replace('_', ' ', $payroll->payment_method)) : '-';

    // Salary mein shamil (katauti nahi) din: company holiday, weekly off, paid leave
    $holidayCount   = $items->where('day_type', 'holiday')->count();
    $weeklyOffCount = $items->where('day_type', 'weekly_off')->count();
    $paidLeaveCount = $items->where('day_type', 'paid_leave')->count();

    $isFinal    = in_array($payroll->status, ['approved', 'paid'], true);
    $pillColors = ['draft' => '#64748b', 'pending' => '#d97706', 'approved' => '#2563eb', 'paid' => '#16a34a', 'rejected' => '#dc2626'];
    $pill       = $pillColors[$payroll->status] ?? '#64748b';
    $payslipNo  = $payslipNo ?? ('PS-' . $monthStart->format('Ym') . '-' . str_pad($payroll->id, 5, '0', STR_PAD_LEFT));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip - {{ $u->name }} - {{ $monthLabel }}</title>
    <style>
        :root {
            --brand: #4b1fa0; --ink: #1d1633; --muted: #6b6580; --line: #d9d4e8; --tint: #f3effb;
            --green: #2f7d4a; --green-bg: #e9f5ec; --orange: #c46a1a; --red-bg: #fdeeee;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
            color: var(--ink); background: #e9e7f0; font-size: 12px; line-height: 1.4;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .toolbar { max-width: 210mm; margin: 14px auto 0; display: flex; gap: 8px; justify-content: flex-end; padding: 0 8px; }
        .btn { border: 0; background: var(--brand); color: #fff; padding: 8px 16px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .btn.alt { background: #fff; color: var(--ink); border: 1px solid var(--line); }

        .page {
            position: relative; overflow: hidden; width: 210mm; margin: 10px auto 24px; padding: 10mm 12mm 9mm;
            background: #fff; box-shadow: 0 6px 24px rgba(29, 22, 51, .15);
        }
        .watermark {
            position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 96px; font-weight: 800; letter-spacing: 6px; color: #7a6f99; opacity: .08;
            pointer-events: none; white-space: nowrap;
        }
        .page > * { position: relative; }

        /* header */
        .head { display: flex; align-items: center; gap: 14px; padding-bottom: 10px; border-bottom: 3px solid var(--brand); }
        .head .logo { width: 62px; height: 62px; flex-shrink: 0; object-fit: contain; }
        .co { flex: 1; min-width: 0; }
        .co h1 { font-size: 20px; line-height: 1.2; font-weight: 700; color: var(--brand); }
        .co .tag { font-size: 11px; color: var(--muted); }
        .co .small { font-size: 10px; color: var(--muted); }
        .period { width: 56mm; flex-shrink: 0; background: var(--tint); border: 1px solid var(--line); border-radius: 6px; padding: 7px 10px; }
        .period .t { display: flex; justify-content: space-between; align-items: center; color: var(--brand); font-weight: 700; font-size: 13px; }
        .pill { font-size: 9.5px; font-weight: 700; color: #fff; border-radius: 999px; padding: 1px 8px; }
        .period .m { font-size: 12.5px; font-weight: 600; margin-top: 2px; }
        .period .s { font-size: 10px; color: var(--muted); }

        /* section bars */
        .bar { background: var(--brand); color: #fff; font-weight: 600; font-size: 12px; padding: 5px 10px; margin-top: 12px; }

        /* employee */
        .emp { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid var(--line); border-top: 0; }
        .emp > div { display: flex; gap: 8px; padding: 5px 10px; border-bottom: 1px solid var(--line); }
        .emp > div:nth-last-child(-n+2) { border-bottom: 0; }
        .emp .k { width: 92px; flex-shrink: 0; color: var(--muted); font-size: 11px; }
        .emp .v { font-weight: 600; min-width: 0; word-break: break-word; }

        /* earnings / deductions */
        .cols { display: grid; grid-template-columns: 1fr 1fr; margin-top: 12px; }
        .side { display: flex; flex-direction: column; border: 1px solid var(--line); border-top: 0; }
        .side + .side { border-left: 0; }
        .side h3 { color: #fff; font-size: 12px; font-weight: 600; padding: 5px 10px; margin: 0 -1px; }
        .earn h3 { background: var(--green); } .ded h3 { background: var(--orange); }
        .rows { flex: 1; display: flex; flex-direction: column; padding: 2px 0; min-height: 70px; }
        .row { display: flex; justify-content: space-between; gap: 8px; padding: 4px 10px; }
        .row span:last-child { white-space: nowrap; }
        .row.tot { margin-top: auto; padding: 6px 10px; font-weight: 700; border-top: 1px solid var(--line); }
        .earn .tot { background: var(--green-bg); } .ded .tot { background: var(--red-bg); }
        .none { color: var(--muted); padding: 4px 10px; }

        /* net */
        .net { margin-top: 12px; background: var(--tint); border: 1px solid var(--brand); border-radius: 6px; padding: 9px 12px; }
        .net b { font-size: 16px; color: var(--brand); font-weight: 700; }
        .net div { font-size: 11px; color: var(--muted); margin-top: 1px; }

        /* payment */
        .pay { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--line); border-top: 0; }
        .pay > div { padding: 5px 10px; border-right: 1px solid var(--line); }
        .pay > div:last-child { border-right: 0; }
        .pay small { display: block; font-size: 10px; color: var(--muted); }
        .pay b { font-weight: 600; }
        .paysub { font-size: 10px; color: var(--muted); margin-top: 4px; }

        .sign { margin-top: 22px; display: flex; justify-content: flex-end; }
        .sign div { width: 50mm; text-align: center; border-top: 1px solid #8c86a3; padding-top: 3px; font-size: 10.5px; color: var(--muted); }
        .sign img { height: 38px; display: block; margin: -42px auto 4px; }
        .foot { margin-top: 10px; text-align: center; font-size: 10px; color: var(--muted); }

        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { margin: 0; box-shadow: none; width: 210mm; }
            .side, .net, .emp, .pay { break-inside: avoid; }
        }
        @media screen and (max-width: 800px) {
            .page { width: 100%; padding: 14px; }
            .head { flex-wrap: wrap; }
            .period { width: 100%; }
            .emp, .cols { grid-template-columns: 1fr; }
            .emp > div:nth-last-child(2) { border-bottom: 1px solid var(--line); }
            .pay { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="{{ route('admin.payroll.show', $payroll->id) }}" class="btn alt">Back</a>
    <button class="btn" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="page">
    @unless($isFinal)<div class="watermark">{{ strtoupper($payroll->status) }}</div>@endunless

    <div class="head">
        @if($logo)<img class="logo" src="{{ $logo }}" alt="{{ $cName }}">@endif
        <div class="co">
            <h1>{{ $cName }}</h1>
            <div class="tag">{{ $cTag }}@if(!empty($company['cin'])) | CIN: {{ $company['cin'] }}@endif</div>
            <div class="small">{{ $cAddr }}</div>
            <div class="small">{{ $cMail }} | {{ $cPhone }} | {{ $cWeb }}</div>
        </div>
        <div class="period">
            <div class="t"><span>PAYSLIP</span><span class="pill" style="background:{{ $pill }}">{{ ucfirst($payroll->status) }}</span></div>
            <div class="m">{{ $monthLabel }}</div>
            <div class="s">{{ $periodText }}</div>
            <div class="s">No: {{ $payslipNo }}</div>
        </div>
    </div>

    <div class="bar">Employee details</div>
    <div class="emp">
        @foreach($left as $lk => $lv)
            <div><span class="k">{{ $lk }}</span><span class="v">{{ $lv }}</span></div>
            @php $rk = array_keys($right)[$loop->index] ?? null; @endphp
            @if($rk !== null)
                <div><span class="k">{{ $rk }}</span><span class="v">{{ $right[$rk] }}</span></div>
            @else
                <div></div>
            @endif
        @endforeach
    </div>

    <div class="cols">
        <div class="side earn">
            <h3>Earnings</h3>
            <div class="rows">
                <div class="row"><span>Monthly salary</span><span>{{ $fmt($monthlySalary) }}</span></div>
                @if($bonus > 0)<div class="row"><span>Bonus</span><span>{{ $fmt($bonus) }}</span></div>@endif
                <div class="row tot"><span>Total gross earnings</span><span>{{ $fmt($totalEarnings) }}</span></div>
            </div>
        </div>
        <div class="side ded">
            <h3>Deductions</h3>
            <div class="rows">
                @foreach($breakdown as $b)
                    <div class="row"><span>{{ $b['label'] }}</span><span>{{ $fmt($b['amount']) }}</span></div>
                @endforeach
                @if($otherDeductions > 0)<div class="row"><span>Other deductions</span><span>{{ $fmt($otherDeductions) }}</span></div>@endif
                @if(empty($breakdown) && $otherDeductions <= 0)<div class="none">No deductions this month</div>@endif
                <div class="row tot"><span>Total deductions</span><span>{{ $fmt($totalDeductions) }}</span></div>
            </div>
        </div>
    </div>

    <div class="net">
        <b>Net pay for the month: {{ $fmt($netPay) }}</b>
        @if($words)<div>{{ $words }}</div>@endif
    </div>

    <div class="bar">Payment details</div>
    <div class="pay">
        <div><small>Mode of payment</small><b>{{ $mode }}</b></div>
        <div><small>Working days</small><b>{{ $num($workDays) }}</b></div>
        <div><small>Paid days</small><b>{{ $num($paidDays) }}</b></div>
        <div><small>Loss of pay days</small><b>{{ $num($lopDays) }}</b></div>
    </div>
    <div class="paysub">
        Paid, no deduction: Company holidays {{ $holidayCount }} | Weekly offs {{ $weeklyOffCount }} | Paid leave {{ $paidLeaveCount }}
    </div>
    <div class="paysub">
        Paid on: {{ $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('d-M-Y') : '-' }}
        @if($payroll->payment_reference) | Ref: {{ $payroll->payment_reference }}@endif
        @if(!empty($payroll->approval_notes)) | Remarks: {{ $payroll->approval_notes }}@endif
    </div>

    <div class="sign">
        <div>
            @if(!empty($company['signature']) && is_file(public_path($company['signature'])))
                <img src="{{ asset($company['signature']) }}" alt="">
            @endif
            Authorised signatory
        </div>
    </div>
    <div class="foot">This is a computer-generated payslip and does not require a signature.</div>
</div>

</body>
</html>
