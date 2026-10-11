<x-admin>
    <x-slot:title>
        HR & Staff Settings | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high d-flex align-items-center gap-2">
                <i class="fas fa-user-gear text-primary" style="font-size: 1.5rem;"></i>
                HR & Staff Settings
            </h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Centralized management for staff, roles & permissions, attendance rules, shift cutoffs, and approvals.
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('users.view'))
                <a href="{{ route('admin.users.index') }}" class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                    <i class="fas fa-users me-1"></i> Staff Directory
                </a>
            @endif
            @if(Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('attendance.settings') || Auth::user()->hasPermission('attendance.manage'))
                <a href="{{ route('admin.attendance.settings') }}" class="btn-premium btn-premium-primary px-3 py-2 text-decoration-none">
                    <i class="fas fa-sliders-h me-1"></i> Attendance Policies
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-4 d-flex align-items-center border-0" style="background: rgba(var(--success-rgb), 0.1); color: var(--success);">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Quick Stats Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="glass-card p-3 h-100" style="border: 1px solid var(--border-main);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Total Staff</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-high">{{ $totalUsers }}</h3>
                <a href="{{ route('admin.users.index') }}" class="text-decoration-none text-primary small d-inline-flex align-items-center gap-1">
                    Manage users <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="glass-card p-3 h-100" style="border: 1px solid var(--border-main);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Access Roles</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-high">{{ $totalRoles }}</h3>
                <a href="{{ route('admin.roles.index') }}" class="text-decoration-none text-primary small d-inline-flex align-items-center gap-1">
                    Configure roles <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="glass-card p-3 h-100" style="border: 1px solid var(--border-main);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Work Hours</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-high" style="font-size: 1.25rem;">{{ $officeStartTime }} - {{ $officeEndTime }}</h4>
                <div class="text-low small">{{ $workingDays }} Days / Week</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="glass-card p-3 h-100" style="border: 1px solid var(--border-main);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Pending Approvals</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-high">{{ $pendingApprovals }}</h3>
                <a href="{{ route('admin.attendance.requests') }}" class="text-decoration-none text-warning small d-inline-flex align-items-center gap-1">
                    Review requests <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- HR Modules Cards -->
    <h5 class="fw-bold mb-3 text-high">HR Modules & Settings</h5>
    <div class="row g-4">
        <!-- 1. Users & Staff -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; width: 48px; height: 48px;">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Staff & Users</h5>
                            <span class="badge bg-primary bg-opacity-25 text-primary small">{{ $totalUsers }} Registered</span>
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Add, edit, deactivate, and manage employee accounts, contact details, assigned workspaces, and profiles.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.users.index') }}" class="btn-premium btn-premium-secondary w-100 justify-content-center text-decoration-none">
                        Manage Staff <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Roles & Permissions -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(168, 85, 247, 0.15); color: #a855f7; width: 48px; height: 48px;">
                            <i class="fas fa-shield-halved fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Roles & Access</h5>
                            <span class="badge bg-purple bg-opacity-25 text-white small" style="background: rgba(168, 85, 247, 0.2) !important;">{{ $totalRoles }} Custom Roles</span>
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Define role-based access control, attendance viewing/punch permissions, management rights, and admin privileges.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.roles.index') }}" class="btn-premium btn-premium-secondary flex-grow-1 justify-content-center text-decoration-none">
                        All Roles <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    @php
                        $hrRole = \App\Models\Role::where('slug', 'hr-manager')->orWhere('slug', 'like', '%hr%')->first();
                    @endphp
                    @if($hrRole)
                        <a href="{{ route('admin.roles.index', ['search' => $hrRole->slug]) }}" class="btn-premium btn-premium-primary text-decoration-none px-3 text-nowrap" title="Edit HR Role Permissions">
                            <i class="fas fa-user-shield me-1"></i> Edit HR Role
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Attendance Policies -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(16, 185, 129, 0.15); color: #10b981; width: 48px; height: 48px;">
                            <i class="fas fa-business-time fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Attendance Rules</h5>
                            <span class="badge bg-success bg-opacity-25 text-success small">{{ $workingDays }}-Day Week</span>
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Set standard office hours, late-entry grace periods, working days per week, and office IP restrictions.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.attendance.settings') }}" class="btn-premium btn-premium-secondary w-100 justify-content-center text-decoration-none">
                        Attendance Settings <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Shift & Timer Rules -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; width: 48px; height: 48px;">
                            <i class="fas fa-stopwatch fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Shift & Auto-Stop</h5>
                            <span class="badge {{ $autoStopTimers == 'yes' ? 'bg-success bg-opacity-25 text-success' : 'bg-secondary bg-opacity-25 text-low' }} small">
                                {{ $autoStopTimers == 'yes' ? 'Active at ' . $officeCloseTime : 'Manual Closure' }}
                            </span>
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Automatically halt active task timers and force day closure at the end of the workday to prevent runaway hours.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.settings.autostop') }}" class="btn-premium btn-premium-secondary w-100 justify-content-center text-decoration-none">
                        Timer Rules <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 5. Leave & Punch Approvals -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; width: 48px; height: 48px;">
                            <i class="fas fa-envelope-open-text fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Leave & Approvals</h5>
                            @if($pendingApprovals > 0)
                                <span class="badge bg-warning text-dark small">{{ $pendingApprovals }} Pending</span>
                            @else
                                <span class="badge bg-success bg-opacity-25 text-success small">All Settled</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Review leave applications, manual attendance regularizations, and daily end-of-day work reports submitted by staff.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.attendance.requests') }}" class="btn-premium btn-premium-secondary w-100 justify-content-center text-decoration-none">
                        Review Requests <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 6. Monthly Timesheets & Reports -->
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-main); border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; width: 48px; height: 48px;">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-0 text-high">Monthly Timesheets</h5>
                            <span class="badge bg-info bg-opacity-25 text-info small">Audit & Export</span>
                        </div>
                    </div>
                    <p class="text-low small mb-4">
                        Review consolidated monthly employee reports, approve timesheets, and export attendance audits to Excel / CSV.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.attendance.monthly') }}" class="btn-premium btn-premium-secondary w-100 justify-content-center text-decoration-none">
                        Timesheets & Reports <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin>
