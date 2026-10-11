<x-admin>
    <x-slot:title>
        Attendance Settings | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Attendance Settings</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Configure office hours, grace periods, and tracking policies.</p>
        </div>
        <button type="submit" form="attendanceSettingsForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-4 d-flex align-items-center border-0" style="background: rgba(var(--success-rgb), 0.1); color: var(--success);">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-4 border-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i> <strong>Please check the form for errors:</strong>
            </div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-card p-4">
        <form id="attendanceSettingsForm" action="{{ route('admin.attendance.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <label class="form-label text-high fw-semibold">Office Start Time (with Grace Period)</label>
                    <input type="time" name="office_start_time" class="form-premium-control w-100" value="{{ $officeStartTime }}" required>
                    <small class="text-low d-block mt-2">Anyone clocking in after this time will be marked as "Late".</small>
                </div>
                
                <div class="col-md-4 mb-4">
                    <label class="form-label text-high fw-semibold">Office End Time</label>
                    <input type="time" name="office_end_time" class="form-premium-control w-100" value="{{ $officeEndTime }}" required>
                    <small class="text-low d-block mt-2">Standard end of shift time.</small>
                </div>
                
                <div class="col-md-4 mb-4">
                    <label class="form-label text-high fw-semibold">Working Days / Week</label>
                    <select name="working_days" class="form-premium-control w-100 bg-dark text-white" required>
                        <option value="5" {{ isset($workingDays) && $workingDays == '5' ? 'selected' : '' }}>5 Days (Mon-Fri)</option>
                        <option value="6" {{ isset($workingDays) && $workingDays == '6' ? 'selected' : '' }}>6 Days (Mon-Sat)</option>
                    </select>
                    <small class="text-low d-block mt-2">Determines expected attendance schedule.</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <label class="form-label text-high fw-semibold">Allowed IP Addresses (Office WiFi)</label>
                    <input type="text" name="allowed_ips" class="form-premium-control w-100" value="{{ $allowedIps ?? '' }}" placeholder="e.g. 192.168.1.1, 203.0.113.50">
                    <small class="text-low d-block mt-2">Comma-separated list of IP addresses allowed for punch-in. Leave blank to allow all IPs. Your current IP is: <strong>{{ request()->ip() }}</strong></small>
                </div>
            </div>

        </form>
    </div>
</x-admin>
