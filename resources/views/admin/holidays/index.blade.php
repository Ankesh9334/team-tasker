 @extends('admin.layout.app')

 @section('title', 'Company Holidays')

 @section('content')
 <style>
    .holiday-page {
        color: #1e293b;
        font: 14px/1.5 "Segoe UI", system-ui, Arial, sans-serif;
    }

    .holiday-page .holiday-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }

    .holiday-page h1 {
        margin: 0;
        font-size: 26px;
    }

    .holiday-page h2 {
        margin: 0 0 12px;
        font-size: 16px;
    }

    .holiday-page .sub,
    .holiday-page .muted {
        color: #64748b;
    }

    .holiday-page .sub {
        margin: 4px 0 0;
    }

    .holiday-page .holiday-links a {
        display: inline-block;
        margin-left: 8px;
        padding: 8px 14px;
        border: 1px solid #c7d2fe;
        border-radius: 8px;
        color: #3b4fd8;
        text-decoration: none;
        background: #fff;
    }

    .holiday-page .holiday-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px;
        margin-top: 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        overflow-x: auto;
    }

    .holiday-page .holiday-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 12px;
    }

    .holiday-page .full {
        grid-column: 1 / -1;
    }

    .holiday-page label {
        display: block;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .holiday-page input {
        width: 100%;
        padding: 9px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font: inherit;
    }

    .holiday-page .holiday-btn {
        padding: 9px 18px;
        border: 0;
        border-radius: 8px;
        background: #4f46e5;
        color: #fff;
        font: inherit;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .holiday-page .delete-btn {
        background: none;
        color: #dc2626;
        border: 0;
        padding: 0;
        cursor: pointer;
        font: inherit;
    }

    .holiday-page table {
        width: 100%;
        border-collapse: collapse;
    }

    .holiday-page th,
    .holiday-page td {
        text-align: left;
        padding: 10px 8px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .holiday-page th {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .holiday-page .holiday-alert {
        margin-top: 16px;
        padding: 10px 14px;
        border-radius: 8px;
    }

    .holiday-page .success {
        background: #dcfce7;
        color: #166534;
    }

    .holiday-page .error {
        background: #fee2e2;
        color: #991b1b;
    }

    .holiday-page .holiday-year {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .holiday-page .holiday-year input {
        width: 110px;
    }

    @media (max-width: 700px) {
        .holiday-page .holiday-grid {
            grid-template-columns: 1fr;
        }

        .holiday-page .full {
            grid-column: auto;
        }
    }
</style>

<div class="holiday-page">
    <div class="holiday-top">
        <div>
            <h1>Company Holidays</h1>
            <p class="sub">
                Company holidays are paid for every employee.
                No salary is deducted for these days.
            </p>
        </div>

        <div class="holiday-links">
            @if(Route::has('admin.payroll.index'))
            <a href="{{ route('admin.payroll.index') }}">Payroll</a>
            @endif

            @if(Route::has('admin.leaves.index'))
            <a href="{{ route('admin.leaves.index') }}">Leave requests</a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="holiday-alert success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="holiday-alert error">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="holiday-alert error">
        <ul style="margin:0; padding-left:18px">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="holiday-card">
        <h2>Add holiday</h2>

        <form method="POST" action="{{ route('admin.holidays.store') }}">
            @csrf

            <div class="holiday-grid">
                <div>
                    <label for="holiday-name">Holiday name</label>
                    <input
                    id="holiday-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="e.g. Diwali"
                    maxlength="120"
                    required
                    >
                </div>

                <div>
                    <label for="holiday-start">From</label>
                    <input
                    id="holiday-start"
                    type="date"
                    name="start_date"
                    value="{{ old('start_date') }}"
                    required
                    >
                </div>

                <div>
                    <label for="holiday-end">To (optional)</label>
                    <input
                    id="holiday-end"
                    type="date"
                    name="end_date"
                    value="{{ old('end_date') }}"
                    >
                </div>

                <div class="full">
                    <label for="holiday-description">Note (optional)</label>
                    <input
                    id="holiday-description"
                    type="text"
                    name="description"
                    value="{{ old('description') }}"
                    maxlength="500"
                    >
                </div>
            </div>

            <p style="margin:12px 0 0">
                <button class="holiday-btn" type="submit">Add holiday</button>
            </p>
        </form>
    </div>

    <div class="holiday-card">
        <div class="holiday-top" style="align-items:center">
            <h2 style="margin:0">Holidays in {{ $year }}</h2>

            <form method="GET"
            action="{{ route('admin.holidays.index') }}"
            class="holiday-year">
            <input
            type="number"
            name="year"
            value="{{ $year }}"
            min="2000"
            max="2100"
            aria-label="Holiday year"
            >
            <button class="holiday-btn" type="submit">Show</button>
        </form>
    </div>

    <table style="margin-top:8px">
        <thead>
            <tr>
                <th>Holiday</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Note</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($holidays as $holiday)
            <tr>
                <td><strong>{{ $holiday->name }}</strong></td>

                <td>
                    {{ \Carbon\Carbon::parse($holiday->start_date)->format('d M Y') }}

                    @if($holiday->end_date &&
                    !\Carbon\Carbon::parse($holiday->end_date)->isSameDay(
                    \Carbon\Carbon::parse($holiday->start_date)
                    ))
                    - {{ \Carbon\Carbon::parse($holiday->end_date)->format('d M Y') }}
                    @endif
                </td>

                <td>{{ $holiday->days_count }}</td>

                <td class="muted">
                    {{ $holiday->description ?: '-' }}
                </td>

                <td>
                    <form
                    method="POST"
                    action="{{ route('admin.holidays.destroy', $holiday->id) }}"
                    onsubmit="return confirm('Delete this holiday?')"
                    >
                    @csrf
                    @method('DELETE')

                    <button class="delete-btn" type="submit">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="muted">
                No holidays added for {{ $year }}.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
@endsection