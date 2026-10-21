{{-- resources/views/admin/payroll/salaries.blade.php --}}
@php
use Carbon\Carbon;

$weekdays = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
$short    = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
$selectedDays = array_map('intval', (array) old('working_days', [1, 2, 3, 4, 5, 6]));

$avatarColors = ['#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444', '#22c55e'];
$today = now()->startOfDay();
$stats = $stats ?? null;
@endphp

<x-admin>
    <x-slot:title>
        Employee Salaries | Team Tasker
    </x-slot:title>

    <style>
        .sal-page { font-size: .86rem; }
        .sal-page .card { border-radius: 14px; }
        .sal-page .form-control, .sal-page .form-select, .sal-page .input-group-text { font-size: .84rem; padding: .38rem .65rem; border-radius: 8px; }
        .sal-page .input-group > .form-control { border-radius: 0 8px 8px 0; }
        .sal-page .input-group-text { border-radius: 8px 0 0 8px; font-weight: 700; }
        .sal-page .form-label { font-size: .75rem; font-weight: 600; opacity: .75; margin-bottom: .2rem; }

        .sal-hero { background: linear-gradient(120deg, #0f766e, #0891b2 55%, #4f46e5); border-radius: 16px; color: #fff; padding: 1.1rem 1.3rem; }
        .sal-hero h1 { font-size: 1.25rem; font-weight: 700; margin: 0; color: #fff; }
        .sal-hero p { margin: 0; opacity: .88; font-size: .8rem; }

        .btn-x { border: 0; border-radius: 8px; font-size: .78rem; font-weight: 600; padding: .38rem .8rem; line-height: 1.3; transition: transform .12s, box-shadow .12s, filter .12s; text-decoration: none; display: inline-block; }
        .btn-x:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.16); filter: brightness(1.06); }
        .btn-white  { background: #fff; color: #0f766e; }
        .btn-indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; }
        .btn-teal   { background: linear-gradient(135deg,#14b8a6,#0f766e); color: #fff; }
        .btn-soft   { background: rgba(99,102,241,.14); color: #6366f1; }
        .btn-soft:hover { color: #4f46e5; }

        .stat-card { border-radius: 14px; color: #fff; padding: .7rem .9rem; height: 100%; position: relative; overflow: hidden; }
        .stat-card::after { content: ""; position: absolute; right: -18px; top: -18px; width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,.14); }
        .stat-card small { opacity: .92; font-size: .72rem; }
        .stat-card b { display: block; font-size: 1.35rem; line-height: 1.2; }
        .stat-card i.fas { position: absolute; right: 12px; bottom: 8px; opacity: .3; font-size: 1.4rem; }

        .sec-title { font-weight: 700; display: flex; align-items: center; gap: .5rem; }
        .sec-title .dot { width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; }

        /* Working day chips (form) */
        .day-cb { position: absolute; opacity: 0; pointer-events: none; }
        .day-chip { display: inline-block; cursor: pointer; user-select: none; padding: .3rem .85rem; border-radius: 999px; font-size: .78rem; font-weight: 600; background: rgba(100,116,139,.14); border: 1px solid transparent; transition: all .12s; }
        .day-chip:hover { background: rgba(99,102,241,.18); }
        .day-cb:checked + .day-chip { background: linear-gradient(135deg,#14b8a6,#0f766e); color: #fff; box-shadow: 0 3px 8px rgba(15,118,110,.35); }
        .day-cb:focus-visible + .day-chip { outline: 2px solid #6366f1; outline-offset: 2px; }

        .preview { border-radius: 12px; padding: .6rem .9rem; background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(99,102,241,.14)); border: 1px dashed rgba(99,102,241,.45); }
        .preview b { font-size: 1rem; }
        .note { border-radius: 10px; padding: .45rem .75rem; background: rgba(245,158,11,.14); color: #b45309; font-size: .76rem; }

        /* Table */
        .sal-table { margin: 0; }
        .sal-table thead th { font-size: .72rem; font-weight: 600; opacity: .75; padding: .55rem .75rem; white-space: nowrap; }
        .sal-table td { padding: .55rem .75rem; vertical-align: middle; }
        .sal-table tbody tr { transition: background .12s; }
        .sal-table tbody tr.is-current { box-shadow: inset 3px 0 0 #16a34a; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; color: #fff; font-weight: 700; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .wd { display: flex; gap: 3px; }
        .wd span { width: 24px; height: 22px; border-radius: 6px; font-size: .62rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .wd .on { background: #14b8a6; color: #fff; }
        .wd .off { background: rgba(100,116,139,.18); opacity: .6; }
        .badge-s { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-s i { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
        .s-active   { background: rgba(34,197,94,.16);  color: #16a34a; } .s-active i   { background: #16a34a; }
        .s-upcoming { background: rgba(14,165,233,.16); color: #0284c7; } .s-upcoming i { background: #0284c7; }
        .s-ended    { background: rgba(100,116,139,.18); color: #64748b; } .s-ended i    { background: #64748b; }
        .sal-amt { font-weight: 700; color: #15803d; }
        .empty-box { padding: 2rem 1rem; text-align: center; opacity: .7; }
        .empty-box b { display: block; font-size: 1rem; margin-bottom: .2rem; }
    </style>

    <div class="container-fluid py-3 sal-page">

        {{-- Hero --}}
        <div class="sal-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1>Employee Salaries</h1>
                <p>Manage employee monthly salary and working days.</p>
            </div>
            <a href="{{ route('admin.payroll.index') }}" class="btn-x btn-white"><i class="fas fa-arrow-left me-1"></i> Back to Payroll</a>
        </div>

        {{-- Alerts --}}
        @if(session('success'))<div class="alert alert-success py-2 px-3 auto-dismiss">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2 px-3 auto-dismiss">{{ session('error') }}</div>@endif
        @if($errors->any())
        <div class="alert alert-danger py-2 px-3">
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-1 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- Stats --}}
        @if($stats)
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#6366f1,#4338ca)"><small>Employees with salary</small><b>{{ $stats['employees'] }}</b><i class="fas fa-users"></i></div></div>
            <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#22c55e,#15803d)"><small>Monthly payroll (current)</small><b>₹{{ number_format($stats['total'], 0) }}</b><i class="fas fa-rupee-sign"></i></div></div>
            <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,#0ea5e9,#0369a1)"><small>Average salary</small><b>₹{{ number_format($stats['average'], 0) }}</b><i class="fas fa-chart-line"></i></div></div>
            <div class="col-6 col-lg-3"><div class="stat-card" style="background:linear-gradient(135deg,{{ $stats['without'] > 0 ? '#f59e0b,#d97706' : '#64748b,#334155' }})"><small>Without salary record</small><b>{{ $stats['without'] }}</b><i class="fas fa-user-times"></i></div></div>
        </div>
        @endif

        {{-- Add salary --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent py-3 px-3">
                <div class="sec-title"><span class="dot" style="background:linear-gradient(135deg,#14b8a6,#0f766e)"><i class="fas fa-plus"></i></span> Add employee salary</div>
            </div>
            <div class="card-body px-3">
                <form method="POST" action="{{ route('admin.payroll.salaries.store') }}" id="salaryForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="user_id" class="form-label">Employee</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select employee</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="monthly_salary" class="form-label">Monthly salary</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="monthly_salary" id="monthly_salary" class="form-control" min="0.01" step="0.01" value="{{ old('monthly_salary') }}" placeholder="e.g. 25000" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-1 gap-2">
                                <label class="form-label mb-0">Scheduled working days</label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn-x btn-soft" data-days="1,2,3,4,5">Mon-Fri</button>
                                    <button type="button" class="btn-x btn-soft" data-days="1,2,3,4,5,6">Mon-Sat</button>
                                    <button type="button" class="btn-x btn-soft" data-days="1,2,3,4,5,6,7">All days</button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($weekdays as $n => $name)
                                <span class="position-relative">
                                    <input type="checkbox" class="day-cb" id="wd{{ $n }}" name="working_days[]" value="{{ $n }}" @checked(in_array($n, $selectedDays, true))>
                                    <label for="wd{{ $n }}" class="day-chip">{{ $name }}</label>
                                </span>
                                @endforeach
                            </div>
                            <small class="d-block mt-1 opacity-75">Selected days are used to calculate the daily rate. Unselected days are weekly offs.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="effective_from" class="form-label">Effective from</label>
                            <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="effective_until" class="form-label">Effective until (optional)</label>
                            <input type="date" name="effective_until" id="effective_until" class="form-control" value="{{ old('effective_until') }}">
                        </div>

                        <div class="col-12">
                            <div class="preview d-flex flex-wrap gap-3 align-items-center" id="preview">
                                <span><i class="fas fa-calendar-alt me-1"></i><span id="pvMonth">-</span></span>
                                <span>Working days: <b id="pvDays">-</b></span>
                                <span>Weekly off: <b id="pvOff">-</b></span>
                                <span>Daily rate: <b id="pvRate" style="color:#0f766e">-</b></span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="e.g. increment, probation completed, etc.">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                            <button type="submit" class="btn-x btn-indigo" id="saveBtn"><i class="fas fa-save me-1"></i> Save salary</button>
                            <span class="note"><i class="fas fa-info-circle me-1"></i>This employee's current active salary will be closed automatically on the day before &quot;Effective from&quot;.</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Records --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="sec-title"><span class="dot" style="background:linear-gradient(135deg,#6366f1,#4338ca)"><i class="fas fa-list"></i></span> Employee salary records</div>
                <input type="text" id="salSearch" class="form-control" style="max-width: 240px" placeholder="Search on this page...">
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
                            $name  = $salary->user?->name ?? 'Deleted user';
                            $color = $avatarColors[abs(crc32($name)) % count($avatarColors)];
                            $days  = $salary->working_days ?? [];
                            if (is_string($days)) { $days = json_decode($days, true) ?? []; }
                            $days  = array_map('intval', (array) $days);

                            $from  = Carbon::parse($salary->effective_from)->startOfDay();
                            $until = $salary->effective_until ? Carbon::parse($salary->effective_until)->startOfDay() : null;

                            if ($from->gt($today)) {
                                [$sClass, $sLabel] = ['s-upcoming', 'Upcoming'];
                            } elseif ($until && $until->lt($today)) {
                                [$sClass, $sLabel] = ['s-ended', 'Ended'];
                            } elseif (!$salary->is_active) {
                                [$sClass, $sLabel] = ['s-ended', 'Replaced'];
                            } else {
                                [$sClass, $sLabel] = ['s-active', 'Active'];
                            }
                            @endphp
                            <tr class="{{ $sLabel === 'Active' ? 'is-current' : '' }}" data-search="{{ strtolower($name . ' ' . ($salary->user?->email ?? '')) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar" style="background: {{ $color }}">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $name }}</div>
                                            <small class="opacity-75">{{ $salary->user?->email }}</small>
                                            @if($salary->notes)<small class="d-block opacity-75" title="{{ $salary->notes }}"><i class="fas fa-sticky-note me-1"></i>{{ \Illuminate\Support\Str::limit($salary->notes, 40) }}</small>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end sal-amt">₹{{ number_format((float) $salary->monthly_salary, 2) }}</td>
                                <td>
                                    <div class="wd">
                                        @foreach($short as $n => $label)
                                        <span class="{{ in_array($n, $days, true) ? 'on' : 'off' }}" title="{{ $weekdays[$n] }}">{{ substr($label, 0, 2) }}</span>
                                        @endforeach
                                    </div>
                                    <small class="opacity-75">{{ count($days) }} days / week</small>
                                </td>
                                <td>{{ $from->format('d M Y') }}</td>
                                <td>{{ $until ? $until->format('d M Y') : 'Ongoing' }}</td>
                                <td><span class="badge-s {{ $sClass }}"><i></i>{{ $sLabel }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-box"><b>No salary records found</b>Add the first employee salary using the form above.</div>
                                </td>
                            </tr>
                            @endforelse
                            <tr id="noMatch" style="display:none"><td colspan="6"><div class="empty-box"><b>No match</b>No employee found on this page.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if(method_exists($salaries, 'links'))
            <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <small class="opacity-75">
                    @if($salaries->total() > 0)Showing {{ $salaries->firstItem() }}-{{ $salaries->lastItem() }} of {{ $salaries->total() }}@endif
                </small>
                {{ $salaries->links() }}
            </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var salary = document.getElementById('monthly_salary');
            var from   = document.getElementById('effective_from');
            var until  = document.getElementById('effective_until');
            var boxes  = document.querySelectorAll('.day-cb');

            function selectedDays() {
                return Array.prototype.filter.call(boxes, function (b) { return b.checked; }).map(function (b) { return +b.value; });
            }

            // Daily rate preview for the "Effective from" month (same logic as the payroll service).
            function preview() {
                var days = selectedDays();
                var base = from.value ? new Date(from.value + 'T00:00:00') : new Date();
                var y = base.getFullYear(), m = base.getMonth();
                var dim = new Date(y, m + 1, 0).getDate(), working = 0;
                for (var d = 1; d <= dim; d++) {
                    var iso = new Date(y, m, d).getDay() || 7;
                    if (days.indexOf(iso) !== -1) working++;
                }
                var amt = parseFloat(salary.value) || 0;
                document.getElementById('pvMonth').textContent = base.toLocaleString('en-US', { month: 'long', year: 'numeric' });
                document.getElementById('pvDays').textContent = working;
                document.getElementById('pvOff').textContent = dim - working;
                document.getElementById('pvRate').textContent = (working && amt)
                ? '₹' + (amt / working).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '-';
            }

            document.querySelectorAll('[data-days]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var set = btn.getAttribute('data-days').split(',').map(Number);
                    boxes.forEach(function (b) { b.checked = set.indexOf(+b.value) !== -1; });
                    preview();
                });
            });

            [salary, from, until].forEach(function (el) { el.addEventListener('input', preview); });
            boxes.forEach(function (b) { b.addEventListener('change', preview); });

            // "Until" date cannot be earlier than the "From" date.
            function syncMin() { until.min = from.value || ''; }
            from.addEventListener('change', syncMin); syncMin();

            // At least one working day is required.
            var form = document.getElementById('salaryForm');
            form.addEventListener('submit', function (e) {
                if (!selectedDays().length) { e.preventDefault(); alert('Please select at least one working day.'); return; }
                var btn = document.getElementById('saveBtn');
                setTimeout(function () { btn.disabled = true; btn.textContent = 'Saving...'; }, 0);
            });

            // Live search on the current page.
            var search = document.getElementById('salSearch');
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase(), shown = 0;
                document.querySelectorAll('#salBody tr[data-search]').forEach(function (tr) {
                    var ok = !q || tr.getAttribute('data-search').indexOf(q) !== -1;
                    tr.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                var nm = document.getElementById('noMatch');
                if (nm) nm.style.display = (q && !shown) ? '' : 'none';
            });

            document.querySelectorAll('.auto-dismiss').forEach(function (el) {
                setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 500); }, 6000);
            });

            preview();
        })();
    </script>
</x-admin>