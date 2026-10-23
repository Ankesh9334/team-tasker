
{{-- resources/views/admin/payroll/salaries.blade.php --}}
@php
use Carbon\Carbon;

$weekdays = [
1 => 'Monday',
2 => 'Tuesday',
3 => 'Wednesday',
4 => 'Thursday',
5 => 'Friday',
6 => 'Saturday',
7 => 'Sunday',
];

$short = [
1 => 'Mon',
2 => 'Tue',
3 => 'Wed',
4 => 'Thu',
5 => 'Fri',
6 => 'Sat',
7 => 'Sun',
];

$selectedDays = array_map(
'intval',
(array) old('working_days', [1, 2, 3, 4, 5, 6])
);

$today = now()->startOfDay();
$stats = $stats ?? null;
@endphp

<x-admin>
    <x-slot:title>
        Employee Salaries | Team Tasker
    </x-slot:title>

    <style>
        .sal-page {
            font-size: .875rem;
            color: #334155;
        }

        .sal-page .card {
            background: #fff;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04) !important;
        }

        .sal-page .card-header,
        .sal-page .card-footer {
            background: #fff !important;
            border-color: #e2e8f0;
        }

        .sal-page .form-control,
        .sal-page .form-select,
        .sal-page .input-group-text {
            font-size: .84rem;
            min-height: 38px;
            padding: .4rem .7rem;
            border-color: #cbd5e1;
            border-radius: 6px;
            box-shadow: none;
        }

        .sal-page .form-control:focus,
        .sal-page .form-select:focus {
            border-color: #64748b;
            box-shadow: 0 0 0 3px rgba(100, 116, 139, .12);
        }

        .sal-page .input-group-text {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .sal-page .input-group > .form-control {
            border-radius: 0 6px 6px 0;
        }

        .sal-page .input-group > .input-group-text {
            border-radius: 6px 0 0 6px;
        }

        .sal-page .form-label {
            display: block;
            margin-bottom: .35rem;
            color: #475569;
            font-size: .78rem;
            font-weight: 600;
        }

        .sal-hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .sal-hero h1 {
            margin: 0 0 .25rem;
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sal-hero p {
            margin: 0;
            color: #64748b;
            font-size: .82rem;
        }

        .sal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .45rem .8rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: #334155;
            font-size: .8rem;
            font-weight: 600;
            line-height: 1.4;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }

        .sal-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .sal-btn-primary {
            color: #fff;
            background: #334155;
            border-color: #334155;
        }

        .sal-btn-primary:hover {
            color: #fff;
            background: #1e293b;
            border-color: #1e293b;
        }

        .sal-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .sal-btn-soft {
            padding: .3rem .65rem;
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
            font-size: .74rem;
        }

        .stat-card {
            height: 100%;
            padding: .9rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #fff;
        }

        .stat-label {
            display: block;
            margin-bottom: .4rem;
            color: #64748b;
            font-size: .76rem;
            font-weight: 500;
        }

        .stat-value {
            display: block;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .stat-icon {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            background: #f8fafc;
            color: #64748b;
            font-size: .85rem;
        }

        .sec-title {
            display: flex;
            align-items: center;
            gap: .6rem;
            color: #0f172a;
            font-size: .92rem;
            font-weight: 700;
        }

        .sec-title .dot {
            display: inline-flex;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            background: #f8fafc;
            color: #475569;
            font-size: .8rem;
        }

        /* Working day selection */
        .day-cb {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }

        .day-chip {
            display: inline-block;
            padding: .38rem .8rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: #475569;
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            transition: background .15s, border-color .15s;
        }

        .day-chip:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .day-cb:checked + .day-chip {
            background: #334155;
            border-color: #334155;
            color: #fff;
        }

        .day-cb:focus-visible + .day-chip {
            outline: 2px solid #64748b;
            outline-offset: 3px;
        }

        .sal-help {
            color: #64748b;
            font-size: .76rem;
        }

        .sal-preview {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem 1.25rem;
            padding: .8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-size: .8rem;
        }

        .sal-preview b {
            color: #0f172a;
            font-size: .9rem;
        }

        .sal-note {
            padding: .55rem .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            color: #64748b;
            font-size: .76rem;
        }

        /* Employee records table */
        .sal-table {
            margin: 0;
            color: #334155;
        }

        .sal-table thead th {
            padding: .75rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .sal-table td {
            padding: .75rem;
            border-color: #f1f5f9;
            vertical-align: middle;
            font-size: .81rem;
        }

        .sal-table tbody tr:hover > * {
            background: #fafafa;
        }

        .sal-table tbody tr.is-current > * {
            background: #fcfdfc;
        }

        .sal-table tbody tr.is-current > td:first-child {
            box-shadow: inset 3px 0 0 #64748b;
        }

        .sal-avatar {
            display: inline-flex;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            font-size: .82rem;
            font-weight: 700;
        }

        .sal-employee-name {
            color: #0f172a;
            font-weight: 600;
        }

        .sal-secondary {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: .73rem;
            overflow-wrap: anywhere;
        }

        .sal-days {
            display: flex;
            gap: 3px;
        }

        .sal-days span {
            display: inline-flex;
            width: 25px;
            height: 23px;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: .61rem;
            font-weight: 700;
        }

        .sal-days .on {
            border-color: #64748b;
            background: #64748b;
            color: #fff;
        }

        .sal-days .off {
            background: #f8fafc;
            color: #94a3b8;
        }

        .sal-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .28rem .6rem;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .sal-status i {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .sal-status-active {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .sal-status-upcoming {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .sal-status-ended {
            border-color: #e2e8f0;
            background: #f8fafc;
            color: #64748b;
        }

        .sal-amount {
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
        }

        .sal-empty {
            padding: 2.5rem 1rem !important;
            text-align: center;
            color: #64748b;
        }

        .sal-empty b {
            display: block;
            margin-bottom: .35rem;
            color: #334155;
            font-size: .95rem;
        }

        .sal-page .pagination {
            margin-bottom: 0;
            gap: 3px;
        }

        .sal-page .page-link {
            border-radius: 5px;
            color: #475569;
            font-size: .8rem;
        }

        .sal-page .page-item.active .page-link {
            border-color: #334155;
            background: #334155;
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .sal-page {
                padding-right: .65rem;
                padding-left: .65rem;
            }

            .sal-hero {
                padding: 1rem;
            }

            .sal-hero h1 {
                font-size: 1.1rem;
            }

            .sal-page .card-header,
            .sal-page .card-body {
                padding-right: .85rem !important;
                padding-left: .85rem !important;
            }

            .sal-table td,
            .sal-table thead th {
                padding: .65rem;
            }

            .sal-preview {
                gap: .65rem 1rem;
            }
        }


        
/* ===== Dark / Night Mode ===== */
html[data-bs-theme="dark"] .sal-page,
body.dark-mode .sal-page,
body.dark .sal-page {
    color: #e2e8f0;
}

html[data-bs-theme="dark"] .sal-page .card,
body.dark-mode .sal-page .card,
body.dark .sal-page .card,
html[data-bs-theme="dark"] .sal-hero,
body.dark-mode .sal-hero,
body.dark .sal-hero {
    background: #172033 !important;
    border-color: #334155 !important;
    color: #e2e8f0;
}

html[data-bs-theme="dark"] .sal-page .card-header,
html[data-bs-theme="dark"] .sal-page .card-footer,
body.dark-mode .sal-page .card-header,
body.dark-mode .sal-page .card-footer,
body.dark .sal-page .card-header,
body.dark .sal-page .card-footer {
    background: #172033 !important;
    border-color: #334155 !important;
}

html[data-bs-theme="dark"] .sal-hero h1,
html[data-bs-theme="dark"] .sal-employee-name,
html[data-bs-theme="dark"] .sal-preview b,
html[data-bs-theme="dark"] .stat-value,
html[data-bs-theme="dark"] .sal-empty b,
body.dark-mode .sal-hero h1,
body.dark-mode .sal-employee-name,
body.dark-mode .sal-preview b,
body.dark-mode .stat-value,
body.dark-mode .sal-empty b,
body.dark .sal-hero h1,
body.dark .sal-employee-name,
body.dark .sal-preview b,
body.dark .stat-value,
body.dark .sal-empty b {
    color: #f1f5f9 !important;
}

html[data-bs-theme="dark"] .sal-hero p,
html[data-bs-theme="dark"] .stat-label,
html[data-bs-theme="dark"] .sal-help,
html[data-bs-theme="dark"] .sal-secondary,
body.dark-mode .sal-hero p,
body.dark-mode .stat-label,
body.dark-mode .sal-help,
body.dark-mode .sal-secondary,
body.dark .sal-hero p,
body.dark .stat-label,
body.dark .sal-help,
body.dark .sal-secondary {
    color: #94a3b8 !important;
}

html[data-bs-theme="dark"] .stat-card,
html[data-bs-theme="dark"] .sal-preview,
html[data-bs-theme="dark"] .sal-note,
html[data-bs-theme="dark"] .stat-icon,
html[data-bs-theme="dark"] .sec-title .dot,
body.dark-mode .stat-card,
body.dark-mode .sal-preview,
body.dark-mode .sal-note,
body.dark-mode .stat-icon,
body.dark-mode .sec-title .dot,
body.dark .stat-card,
body.dark .sal-preview,
body.dark .sal-note,
body.dark .stat-icon,
body.dark .sec-title .dot {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

html[data-bs-theme="dark"] .sec-title,
body.dark-mode .sec-title,
body.dark .sec-title {
    color: #f1f5f9;
}

html[data-bs-theme="dark"] .sal-page .form-control,
html[data-bs-theme="dark"] .sal-page .form-select,
html[data-bs-theme="dark"] .sal-page .input-group-text,
body.dark-mode .sal-page .form-control,
body.dark-mode .sal-page .form-select,
body.dark-mode .sal-page .input-group-text,
body.dark .sal-page .form-control,
body.dark .sal-page .form-select,
body.dark .sal-page .input-group-text {
    background-color: #0f172a !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;
}

html[data-bs-theme="dark"] .sal-page .form-control::placeholder,
body.dark-mode .sal-page .form-control::placeholder,
body.dark .sal-page .form-control::placeholder {
    color: #64748b !important;
}

html[data-bs-theme="dark"] .sal-page .form-select option,
body.dark-mode .sal-page .form-select option,
body.dark .sal-page .form-select option {
    background: #0f172a;
    color: #e2e8f0;
}

html[data-bs-theme="dark"] .sal-btn,
body.dark-mode .sal-btn,
body.dark .sal-btn {
    background: #1e293b;
    border-color: #475569;
    color: #e2e8f0;
}

html[data-bs-theme="dark"] .sal-btn:hover,
body.dark-mode .sal-btn:hover,
body.dark .sal-btn:hover {
    background: #334155;
    color: #fff;
}

html[data-bs-theme="dark"] .sal-btn-primary,
body.dark-mode .sal-btn-primary,
body.dark .sal-btn-primary {
    background: #e2e8f0;
    border-color: #e2e8f0;
    color: #0f172a;
}

html[data-bs-theme="dark"] .sal-btn-primary:hover,
body.dark-mode .sal-btn-primary:hover,
body.dark .sal-btn-primary:hover {
    background: #cbd5e1;
    color: #0f172a;
}

html[data-bs-theme="dark"] .day-chip,
body.dark-mode .day-chip,
body.dark .day-chip {
    background: #1e293b;
    border-color: #475569;
    color: #cbd5e1;
}

html[data-bs-theme="dark"] .day-cb:checked + .day-chip,
body.dark-mode .day-cb:checked + .day-chip,
body.dark .day-cb:checked + .day-chip {
    background: #e2e8f0;
    border-color: #e2e8f0;
    color: #0f172a;
}

html[data-bs-theme="dark"] .sal-table,
body.dark-mode .sal-table,
body.dark .sal-table {
    --bs-table-color: #cbd5e1;
    --bs-table-bg: #172033;
    --bs-table-border-color: #334155;
    --bs-table-hover-color: #f1f5f9;
    --bs-table-hover-bg: #1e293b;
    color: #cbd5e1;
}

html[data-bs-theme="dark"] .sal-table thead th,
body.dark-mode .sal-table thead th,
body.dark .sal-table thead th {
    background: #0f172a !important;
    border-color: #334155;
    color: #94a3b8;
}

html[data-bs-theme="dark"] .sal-table td,
body.dark-mode .sal-table td,
body.dark .sal-table td {
    border-color: #334155;
}

html[data-bs-theme="dark"] .sal-table tbody tr.is-current > *,
body.dark-mode .sal-table tbody tr.is-current > *,
body.dark .sal-table tbody tr.is-current > * {
    background: #1b2a27;
}

html[data-bs-theme="dark"] .sal-avatar,
body.dark-mode .sal-avatar,
body.dark .sal-avatar {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
}

html[data-bs-theme="dark"] .sal-days .on,
body.dark-mode .sal-days .on,
body.dark .sal-days .on {
    background: #475569;
    border-color: #64748b;
    color: #f8fafc;
}

html[data-bs-theme="dark"] .sal-days .off,
body.dark-mode .sal-days .off,
body.dark .sal-days .off {
    background: #0f172a;
    border-color: #334155;
    color: #64748b;
}

html[data-bs-theme="dark"] .sal-status-active,
body.dark-mode .sal-status-active,
body.dark .sal-status-active {
    background: #143126;
    border-color: #22543d;
    color: #86efac;
}

html[data-bs-theme="dark"] .sal-status-upcoming,
body.dark-mode .sal-status-upcoming,
body.dark .sal-status-upcoming {
    background: #172b45;
    border-color: #26476d;
    color: #93c5fd;
}

html[data-bs-theme="dark"] .sal-status-ended,
body.dark-mode .sal-status-ended,
body.dark .sal-status-ended {
    background: #1e293b;
    border-color: #475569;
    color: #cbd5e1;
}

html[data-bs-theme="dark"] .sal-amount,
body.dark-mode .sal-amount,
body.dark .sal-amount {
    color: #e2e8f0;
}

html[data-bs-theme="dark"] .sal-page .page-link,
body.dark-mode .sal-page .page-link,
body.dark .sal-page .page-link {
    background: #172033;
    border-color: #334155;
    color: #cbd5e1;
}

html[data-bs-theme="dark"] .sal-page .page-item.active .page-link,
body.dark-mode .sal-page .page-item.active .page-link,
body.dark .sal-page .page-item.active .page-link {
    background: #e2e8f0;
    border-color: #e2e8f0;
    color: #0f172a;
}

</style>

<div class="container-fluid py-3 sal-page">

    {{-- Page header --}}
    <div class="sal-hero mb-3">
        <div>
            <h1>Employee Salaries</h1>
            <p>Manage monthly salaries, working days and effective dates.</p>
        </div>

        <a href="{{ route('admin.payroll.index') }}" class="sal-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Payroll
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div class="alert alert-success py-2 px-3 auto-dismiss" role="alert">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger py-2 px-3 auto-dismiss" role="alert">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger py-2 px-3" role="alert">
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Statistics --}}
    @if($stats)
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="stat-label">Employees with salary</span>
                        <span class="stat-value">{{ number_format($stats['employees'] ?? 0) }}</span>
                    </div>
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="stat-label">Monthly payroll (current)</span>
                        <span class="stat-value">₹{{ number_format((float) ($stats['total'] ?? 0), 2) }}</span>
                    </div>
                    <span class="stat-icon"><i class="fas fa-rupee-sign"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="stat-label">Average salary</span>
                        <span class="stat-value">₹{{ number_format((float) ($stats['average'] ?? 0), 2) }}</span>
                    </div>
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="stat-label">Without salary record</span>
                        <span class="stat-value">{{ number_format($stats['without'] ?? 0) }}</span>
                    </div>
                    <span class="stat-icon"><i class="fas fa-user-times"></i></span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Add employee salary --}}
    <div class="card mb-3">
        <div class="card-header py-3 px-3">
            <div class="sec-title">
                <span class="dot"><i class="fas fa-plus"></i></span>
                Add employee salary
            </div>
        </div>

        <div class="card-body p-3">
            <form
            method="POST"
            action="{{ route('admin.payroll.salaries.store') }}"
            id="salaryForm"
            >
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="user_id" class="form-label">Employee</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Select employee</option>
                        @foreach($users as $user)
                        <option
                        value="{{ $user->id }}"
                        @selected(old('user_id') == $user->id)
                        >
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="monthly_salary" class="form-label">Monthly salary (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input
                    type="number"
                    name="monthly_salary"
                    id="monthly_salary"
                    class="form-control"
                    min="0.01"
                    step="0.01"
                    value="{{ old('monthly_salary') }}"
                    placeholder="e.g. 25000"
                    required
                    >
                </div>
            </div>

            {{-- Working days --}}
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label mb-0">Scheduled working days</label>

                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="sal-btn sal-btn-soft" data-days="1,2,3,4,5">
                            Mon–Fri
                        </button>
                        <button type="button" class="sal-btn sal-btn-soft" data-days="1,2,3,4,5,6">
                            Mon–Sat
                        </button>
                        <button type="button" class="sal-btn sal-btn-soft" data-days="1,2,3,4,5,6,7">
                            All days
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @foreach($weekdays as $n => $name)
                    <span class="position-relative">
                        <input
                        type="checkbox"
                        class="day-cb"
                        id="wd{{ $n }}"
                        name="working_days[]"
                        value="{{ $n }}"
                        @checked(in_array($n, $selectedDays, true))
                        >
                        <label for="wd{{ $n }}" class="day-chip">
                            {{ $name }}
                        </label>
                    </span>
                    @endforeach
                </div>

                <small class="d-block mt-2 sal-help">
                    Selected days are used to calculate the daily rate. Unselected days are weekly offs.
                </small>
            </div>

            {{-- Effective dates --}}
            <div class="col-md-6">
                <label for="effective_from" class="form-label">Effective from</label>
                <input
                type="date"
                name="effective_from"
                id="effective_from"
                class="form-control"
                value="{{ old('effective_from', now()->toDateString()) }}"
                required
                >
            </div>

            <div class="col-md-6">
                <label for="effective_until" class="form-label">Effective until (optional)</label>
                <input
                type="date"
                name="effective_until"
                id="effective_until"
                class="form-control"
                value="{{ old('effective_until') }}"
                >
            </div>

            {{-- Salary preview --}}
            <div class="col-12">
                <div class="sal-preview" id="preview">
                    <span>
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="pvMonth">-</span>
                    </span>

                    <span>Working days: <b id="pvDays">-</b></span>
                    <span>Weekly off: <b id="pvOff">-</b></span>
                    <span>Daily rate: <b id="pvRate">-</b></span>
                </div>
                <small class="d-block mt-2 sal-help">
                    Daily rate preview uses the working days in the month selected by Effective from.
                </small>
            </div>

            {{-- Notes --}}
            <div class="col-12">
                <label for="notes" class="form-label">Notes (optional)</label>
                <textarea
                name="notes"
                id="notes"
                class="form-control"
                rows="2"
                placeholder="e.g. increment, probation completed, etc."
                >{{ old('notes') }}</textarea>
            </div>

            <div class="col-12">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <button type="submit" class="sal-btn sal-btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i>
                        Save salary
                    </button>

                    <div class="sal-note">
                        <i class="fas fa-info-circle me-1"></i>
                        The employee's current active salary will be closed automatically on the day before
                        “Effective from”.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>

{{-- Employee salary records --}}
<div class="card">
    <div class="card-header py-3 px-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="sec-title">
                <span class="dot"><i class="fas fa-list"></i></span>
                Employee salary records
            </div>

            <div style="width: 100%; max-width: 260px;">
                <input
                type="search"
                id="salSearch"
                class="form-control"
                placeholder="Search employees on this page..."
                aria-label="Search salary records on this page"
                >
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle sal-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Monthly salary</th>
                        <th>Working days</th>
                        <th>Effective from</th>
                        <th>Effective until</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody id="salBody">
                    @forelse($salaries as $salary)
                    @php
                    $name = $salary->user?->name ?? 'Deleted user';
                    $email = $salary->user?->email ?? '';

                    $days = $salary->working_days ?? [];

                    if (is_string($days)) {
                        $days = json_decode($days, true) ?? [];
                    }

                    $days = array_map('intval', (array) $days);

                    $from = Carbon::parse($salary->effective_from)->startOfDay();

                    $until = $salary->effective_until
                    ? Carbon::parse($salary->effective_until)->startOfDay()
                    : null;

                    if ($from->gt($today)) {
                        [$statusClass, $statusLabel] = [
                        'sal-status-upcoming',
                        'Upcoming'
                        ];
                    } elseif ($until && $until->lt($today)) {
                        [$statusClass, $statusLabel] = [
                        'sal-status-ended',
                        'Ended'
                        ];
                    } elseif (!$salary->is_active) {
                        [$statusClass, $statusLabel] = [
                        'sal-status-ended',
                        'Replaced'
                        ];
                    } else {
                        [$statusClass, $statusLabel] = [
                        'sal-status-active',
                        'Active'
                        ];
                    }

                    $searchText = strtolower($name . ' ' . $email);
                    @endphp

                    <tr
                    class="{{ $statusLabel === 'Active' ? 'is-current' : '' }}"
                    data-search="{{ $searchText }}"
                    >
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="sal-avatar">
                                {{ strtoupper(mb_substr($name, 0, 1)) }}
                            </span>

                            <div>
                                <div class="sal-employee-name">{{ $name }}</div>

                                @if($email)
                                <small class="sal-secondary">{{ $email }}</small>
                                @endif

                                @if($salary->notes)
                                <small
                                class="sal-secondary"
                                title="{{ $salary->notes }}"
                                >
                                <i class="fas fa-sticky-note me-1"></i>
                                {{ \Illuminate\Support\Str::limit($salary->notes, 40) }}
                            </small>
                            @endif
                        </div>
                    </div>
                </td>

                <td class="text-end sal-amount">
                    ₹{{ number_format((float) $salary->monthly_salary, 2) }}
                </td>

                <td>
                    <div class="sal-days">
                        @foreach($short as $n => $label)
                        <span
                        class="{{ in_array($n, $days, true) ? 'on' : 'off' }}"
                        title="{{ $weekdays[$n] }}"
                        >
                        {{ substr($label, 0, 2) }}
                    </span>
                    @endforeach
                </div>

                <small class="sal-secondary">
                    {{ count(array_unique($days)) }} days / week
                </small>
            </td>

            <td class="text-nowrap">{{ $from->format('d M Y') }}</td>

            <td class="text-nowrap">
                {{ $until ? $until->format('d M Y') : 'Ongoing' }}
            </td>

            <td>
                <span class="sal-status {{ $statusClass }}">
                    <i></i>
                    {{ $statusLabel }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="sal-empty">
                <b>No salary records found</b>
                Add the first employee salary using the form above.
            </td>
        </tr>
        @endforelse

        <tr id="noMatch" style="display: none;">
            <td colspan="6" class="sal-empty">
                <b>No matching employees</b>
                Try another employee name or email.
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>

@if(method_exists($salaries, 'links'))
<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-3">
    <small class="sal-help">
        @if($salaries->total() > 0)
        Showing {{ $salaries->firstItem() }}–{{ $salaries->lastItem() }}
        of {{ $salaries->total() }} records
        @else
        No records to display
        @endif
    </small>

    {{ $salaries->links() }}
</div>
@endif
</div>
</div>

<script>
    (function () {
        var salary = document.getElementById('monthly_salary');
        var from = document.getElementById('effective_from');
        var until = document.getElementById('effective_until');
        var boxes = document.querySelectorAll('.day-cb');
        var form = document.getElementById('salaryForm');
        var saveBtn = document.getElementById('saveBtn');
        var search = document.getElementById('salSearch');
        var noMatch = document.getElementById('noMatch');

        function selectedDays() {
            return Array.prototype.filter.call(boxes, function (box) {
                return box.checked;
            }).map(function (box) {
                return Number(box.value);
            });
        }

            // Calculate the daily rate for the Effective from month.
        function preview() {
            var days = selectedDays();

            var base = from.value
            ? new Date(from.value + 'T00:00:00')
            : new Date();

            var year = base.getFullYear();
            var month = base.getMonth();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var workingDays = 0;

            for (var day = 1; day <= daysInMonth; day++) {
                var weekday = new Date(year, month, day).getDay() || 7;

                if (days.indexOf(weekday) !== -1) {
                    workingDays++;
                }
            }

            var amount = parseFloat(salary.value) || 0;

            document.getElementById('pvMonth').textContent =
            base.toLocaleString('en-US', {
                month: 'long',
                year: 'numeric'
            });

            document.getElementById('pvDays').textContent = workingDays;
            document.getElementById('pvOff').textContent = daysInMonth - workingDays;

            document.getElementById('pvRate').textContent =
            workingDays > 0 && amount > 0
            ? '₹' + (amount / workingDays).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
            : '-';
        }

            // Quick-select working day presets.
        document.querySelectorAll('[data-days]').forEach(function (button) {
            button.addEventListener('click', function () {
                var selected = button.getAttribute('data-days')
                .split(',')
                .map(Number);

                boxes.forEach(function (box) {
                    box.checked = selected.indexOf(Number(box.value)) !== -1;
                });

                preview();
            });
        });

        [salary, from].forEach(function (element) {
            element.addEventListener('input', preview);
            element.addEventListener('change', preview);
        });

        boxes.forEach(function (box) {
            box.addEventListener('change', preview);
        });

            // Effective until must not be earlier than Effective from.
        function syncMinDate() {
            until.min = from.value || '';

            if (until.value && from.value && until.value < from.value) {
                until.setCustomValidity(
                    'Effective until cannot be earlier than Effective from.'
                );
            } else {
                until.setCustomValidity('');
            }
        }

        from.addEventListener('change', syncMinDate);
        until.addEventListener('change', syncMinDate);
        syncMinDate();

            // Validate before submitting the form.
        form.addEventListener('submit', function (event) {
            if (selectedDays().length === 0) {
                event.preventDefault();
                alert('Please select at least one working day.');
                return;
            }

            syncMinDate();

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        });

            // Search records on the currently displayed page.
        search.addEventListener('input', function () {
            var query = search.value.trim().toLowerCase();
            var shown = 0;

            document.querySelectorAll('#salBody tr[data-search]').forEach(function (row) {
                var text = row.getAttribute('data-search') || '';
                var matches = !query || text.indexOf(query) !== -1;

                row.style.display = matches ? '' : 'none';

                if (matches) {
                    shown++;
                }
            });

            if (noMatch) {
                noMatch.style.display = (query && shown === 0) ? '' : 'none';
            }
        });

            // Dismiss success/error notifications after a short delay.
        document.querySelectorAll('.auto-dismiss').forEach(function (element) {
            window.setTimeout(function () {
                element.style.transition = 'opacity .4s';
                element.style.opacity = '0';

                window.setTimeout(function () {
                    element.remove();
                }, 400);
            }, 6000);
        });

        preview();
    })();
</script>
</x-admin>
