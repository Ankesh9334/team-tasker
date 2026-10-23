@php
$u   = $u ?? null;
$uid = $u?->id ?? 'new';
$cs  = $u?->currentSalary;

$canSalary = auth()->user()->hasRole('super-admin')
|| auth()->user()->hasPermission('payroll.salary_manage');

$days = $cs ? array_map('intval', (array) $cs->working_days) : [1, 2, 3, 4, 5, 6];

$weekdays = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

$val = fn ($field) => old($field, $u?->{$field});
$date = fn ($field) => old($field, $u?->{$field}?->format('Y-m-d'));
@endphp

{{-- Personal --}}
<h6 class="fw-bold text-high mt-2 mb-3"><i class="fas fa-user me-2 text-low"></i>Personal Information</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Phone</label>
        <input type="text" name="phone" value="{{ $val('phone') }}" class="form-premium-control" placeholder="+91 98765 43210">
    </div>
    <div class="col-md-3">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Date of Birth</label>
        <input type="date" name="date_of_birth" value="{{ $date('date_of_birth') }}" class="form-premium-control">
    </div>
    <div class="col-md-3">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Gender</label>
        <select name="gender" class="form-premium-control">
            <option value="">Select</option>
            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $label)
            <option value="{{ $k }}" @selected($val('gender') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Address</label>
        <textarea name="address" rows="2" class="form-premium-control">{{ $val('address') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Emergency Contact Name</label>
        <input type="text" name="emergency_contact_name" value="{{ $val('emergency_contact_name') }}" class="form-premium-control">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Emergency Contact Phone</label>
        <input type="text" name="emergency_contact_phone" value="{{ $val('emergency_contact_phone') }}" class="form-premium-control">
    </div>
</div>

{{-- Job --}}
<h6 class="fw-bold text-high mb-3"><i class="fas fa-briefcase me-2 text-low"></i>Job Information</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Employee Code</label>
        <input type="text" name="employee_code" value="{{ $val('employee_code') }}" class="form-premium-control" placeholder="e.g. EMP-001">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Joining Date</label>
        <input type="date" name="joining_date" value="{{ $date('joining_date') }}" class="form-premium-control">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Designation</label>
        <input type="text" name="designation" value="{{ $val('designation') }}" class="form-premium-control" placeholder="e.g. QA Engineer">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Department</label>
        <input type="text" name="department" value="{{ $val('department') }}" class="form-premium-control">
    </div>
</div>

{{-- Documents --}}
<h6 class="fw-bold text-high mb-3"><i class="fas fa-id-card me-2 text-low"></i>Documents</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">PAN Number</label>
        <input type="text" name="pan_number" value="{{ $val('pan_number') }}" maxlength="10"
        class="form-premium-control text-uppercase" placeholder="ABCDE1234F">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Aadhaar Number</label>
        <input type="text" name="aadhaar_number" value="{{ $val('aadhaar_number') }}" maxlength="12"
        inputmode="numeric" class="form-premium-control" placeholder="12 digits">
    </div>
</div>

{{-- Bank --}}
<h6 class="fw-bold text-high mb-3"><i class="fas fa-university me-2 text-low"></i>Bank Details</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Bank Name</label>
        <input type="text" name="bank_name" value="{{ $val('bank_name') }}" class="form-premium-control">
    </div>
    <div class="col-md-4">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Account Number</label>
        <input type="text" name="bank_account_number" value="{{ $val('bank_account_number') }}"
        inputmode="numeric" class="form-premium-control">
    </div>
    <div class="col-md-4">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">IFSC Code</label>
        <input type="text" name="bank_ifsc" value="{{ $val('bank_ifsc') }}" maxlength="11"
        class="form-premium-control text-uppercase" placeholder="SBIN0001234">
    </div>
</div>

{{-- Salary --}}
@if ($canSalary)
<h6 class="fw-bold text-high mb-3"><i class="fas fa-money-bill-wave me-2 text-low"></i>Salary</h6>
<div class="row g-3 mb-2">
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Monthly Salary (₹)</label>
        <input type="number" step="0.01" min="0.01" name="monthly_salary"
        value="{{ old('monthly_salary', $cs?->monthly_salary) }}"
        class="form-premium-control" placeholder="e.g. 25000">
    </div>
    <div class="col-md-6">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Effective From</label>
        <input type="date" name="effective_from"
        value="{{ old('effective_from', $cs?->effective_from?->format('Y-m-d') ?? now()->toDateString()) }}"
        class="form-premium-control">
    </div>
    <div class="col-12">
        <label class="heading-label mb-2 d-block" style="font-size: 0.7rem;">Scheduled Working Days</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($weekdays as $n => $label)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="working_days[]"
                value="{{ $n }}" id="wd{{ $uid }}_{{ $n }}"
                @checked(in_array($n, (array) old('working_days', $days)))>
                <label class="form-check-label text-high small" for="wd{{ $uid }}_{{ $n }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>
    </div>
    <div class="col-12">
        <label class="heading-label mb-2" style="font-size: 0.7rem;">Salary Notes (optional)</label>
        <input type="text" name="salary_notes" value="{{ old('salary_notes') }}"
        class="form-premium-control" placeholder="e.g. increment, probation completed">
        <small class="text-low d-block mt-2">
            Salary ya working days badalne par purani salary ek din pehle band ho jayegi aur nayi record ban jayegi.
        </small>
    </div>
</div>
@endif