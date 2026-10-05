<x-admin>
    <x-slot:title>
        Monthly Attendance Report | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Monthly Attendance Report</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Aggregated monthly summary, working hours, attendance rates, and employee drilldowns.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.attendance.daily') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-calendar-day me-1"></i> Daily Reports
            </a>
            <a href="{{ route('admin.attendance.reports', array_merge(request()->except('page'), ['export' => 'monthly', 'month' => $month])) }}" 
               class="btn-premium btn-premium-secondary px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <style>
        .stat-filter-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none !important;
        }
        .stat-filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
            border-color: rgba(var(--primary-rgb, 14, 165, 233), 0.5) !important;
        }
        .stat-filter-card.active-filter {
            border-color: var(--primary) !important;
            background: rgba(var(--primary-rgb, 14, 165, 233), 0.08) !important;
            box-shadow: 0 0 0 1px var(--primary), 0 4px 14px rgba(var(--primary-rgb, 14, 165, 233), 0.15) !important;
        }
    </style>

    <!-- Monthly Statistics Overview Cards (Interactive Filters) -->
    @if(isset($teamStats))
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
            <!-- 1. Total Employees -->
            <div class="col">
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['status', 'page']), ['month' => $month])) }}" 
                   class="d-block h-100 text-decoration-none" title="Filter: All Employees">
                    <div class="glass-card p-3 h-100 border-main stat-filter-card {{ !request('status') ? 'active-filter' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-low d-block" style="font-size: 0.75rem;">Total Employees</span>
                            @if(!request('status'))
                                <i class="fas fa-check-circle text-primary" style="font-size: 0.75rem;"></i>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-0 text-high">{{ $teamStats['total_users'] }}</h4>
                    </div>
                </a>
            </div>

            <!-- 2. Total Present -->
            <div class="col">
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => 'present'])) }}" 
                   class="d-block h-100 text-decoration-none" title="Filter: Present">
                    <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('status') === 'present' ? 'active-filter' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-low d-block" style="font-size: 0.75rem;">Total Present</span>
                            @if(request('status') === 'present')
                                <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-0 text-success">{{ $teamStats['present'] }}</h4>
                    </div>
                </a>
            </div>

            <!-- 3. Half Days -->
            <div class="col">
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => 'half_day'])) }}" 
                   class="d-block h-100 text-decoration-none" title="Filter: Half Days">
                    <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('status') === 'half_day' ? 'active-filter' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-low d-block" style="font-size: 0.75rem;">Half Days</span>
                            @if(request('status') === 'half_day')
                                <i class="fas fa-check-circle text-warning" style="font-size: 0.75rem;"></i>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-0 text-warning">{{ $teamStats['half_days'] }}</h4>
                    </div>
                </a>
            </div>

            <!-- 4. Absences -->
            <div class="col">
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => 'absent'])) }}" 
                   class="d-block h-100 text-decoration-none" title="Filter: Absences">
                    <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('status') === 'absent' ? 'active-filter' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-low d-block" style="font-size: 0.75rem;">Absences</span>
                            @if(request('status') === 'absent')
                                <i class="fas fa-check-circle text-danger" style="font-size: 0.75rem;"></i>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-0 text-danger">{{ $teamStats['absent'] }}</h4>
                    </div>
                </a>
            </div>

            <!-- 5. Pending Approvals -->
            <div class="col">
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['status', 'page']), ['month' => $month, 'status' => 'pending'])) }}" 
                   class="d-block h-100 text-decoration-none" title="Filter: Pending Approvals">
                    <div class="glass-card p-3 h-100 border-main stat-filter-card {{ request('status') === 'pending' ? 'active-filter' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-low d-block" style="font-size: 0.75rem;">Pending Approvals</span>
                            @if(request('status') === 'pending')
                                <i class="fas fa-check-circle text-warning" style="font-size: 0.75rem;"></i>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-0 text-warning">{{ $teamStats['pending'] }}</h4>
                    </div>
                </a>
            </div>

            <!-- 6. Approved Hours -->
            <div class="col">
                <div class="glass-card p-3 h-100 border-main">
                    <span class="text-low d-block" style="font-size: 0.75rem;">Approved Hours</span>
                    <h4 class="fw-bold mb-0 text-primary">{{ $teamStats['formatted_hours'] }}</h4>
                </div>
            </div>
        </div>
    @endif

    @if($selectedUser && $selectedUserStats)
        <!-- Selected Employee Detail Drilldown Card -->
        <div class="glass-card p-4 mb-4 border-main border-primary border-opacity-50">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-premium" style="width: 48px; height: 48px; font-size: 1.2rem;">
                        @if ($selectedUser->profile_image)
                            <img src="{{ asset('storage/' . $selectedUser->profile_image) }}" alt="{{ $selectedUser->name }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600;">
                                {{ strtoupper(substr($selectedUser->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-high">{{ $selectedUser->name }}</h4>
                        <div class="text-low small">{{ $selectedUser->email }} &bull; Showing Month: <strong>{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</strong></div>
                    </div>
                </div>
                <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['user_id', 'page']), ['month' => $month])) }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="fas fa-times me-1"></i> Close Details
                </a>
            </div>

            <!-- Employee Metric Cards -->
            <div class="row row-cols-2 row-cols-md-4 row-cols-xl-8 g-2 mb-4 text-center">
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Present</small>
                        <h5 class="fw-bold text-success mb-0">{{ $selectedUserStats['present_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Half Day</small>
                        <h5 class="fw-bold text-warning mb-0">{{ $selectedUserStats['half_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Absent</small>
                        <h5 class="fw-bold text-danger mb-0">{{ $selectedUserStats['absent_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Pending</small>
                        <h5 class="fw-bold text-warning mb-0">{{ $selectedUserStats['pending_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Rejected</small>
                        <h5 class="fw-bold text-danger mb-0">{{ $selectedUserStats['rejected_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Approved</small>
                        <h5 class="fw-bold text-success mb-0">{{ $selectedUserStats['approved_days'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Total Hours</small>
                        <h5 class="fw-bold text-primary mb-0">{{ $selectedUserStats['formatted_working_hours'] }}</h5>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 rounded bg-subtle border-subtle">
                        <small class="text-low d-block">Attendance %</small>
                        <h5 class="fw-bold text-info mb-0">{{ $selectedUserStats['attendance_percentage'] }}%</h5>
                    </div>
                </div>
            </div>

            <!-- Monthly Calendar Matrix -->
            <h6 class="fw-bold text-high mb-2">Monthly Attendance Calendar ({{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }})</h6>
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($selectedUserCalendar as $cDay)
                    <div class="p-2 rounded text-center border {{ $cDay['is_weekend'] ? 'bg-subtle border-subtle' : 'border-main' }}" 
                         style="min-width: 48px; flex: 1 0 45px;"
                         title="{{ $cDay['date'] }}: {{ $cDay['label'] }}">
                        <div class="text-low" style="font-size: 0.65rem;">{{ $cDay['day_name'] }}</div>
                        <div class="fw-bold small">{{ $cDay['day'] }}</div>
                        <span class="badge bg-{{ $cDay['class'] }} d-block mt-1 py-1" style="font-size: 0.7rem;">
                            {{ $cDay['badge'] }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="d-flex align-items-center gap-3 text-low small mt-2">
                <span><span class="badge bg-success me-1">P</span> Present</span>
                <span><span class="badge bg-warning me-1">H</span> Half Day</span>
                <span><span class="badge bg-danger me-1">A</span> Absent</span>
                <span><span class="badge bg-secondary me-1">-</span> No Record</span>
            </div>
        </div>
    @endif

    <!-- Team Summary Table -->
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search" style="min-width: 200px;">
                <i class="fas fa-search" onclick="document.getElementById('monthlyFilterForm').submit()" style="cursor: pointer;"></i>
                <input type="text" name="search" form="monthlyFilterForm" placeholder="Search employee..." value="{{ request('search') }}" onkeydown="if(event.key === 'Enter') { event.preventDefault(); document.getElementById('monthlyFilterForm').submit(); }">
                @if(request('search'))
                    <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['search', 'page']))) }}" 
                       class="text-low ms-2 text-decoration-none small" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            {{-- Hidden form for search submission --}}
            <form id="monthlyFilterForm" action="{{ route('admin.attendance.monthly') }}" method="GET" class="d-none">
                @if(request('user_id'))
                    <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                @endif
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('month'))
                    <input type="hidden" name="month" value="{{ request('month') }}">
                @endif
            </form>

            <div class="data-grid-results">{{ $users->total() }} Employees</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                @if(request('search') || request('user_id') || request('status') || ($month !== date('Y-m') && request()->has('month')))
                    <a href="{{ route('admin.attendance.monthly') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1 text-decoration-none" title="Reset all filters">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                @endif
                {{ $users->links('components.pagination.premium') }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>EMPLOYEE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th class="text-center">PRESENT</th>
                        <th class="text-center">HALF DAY</th>
                        <th class="text-center">ABSENT</th>
                        <th class="text-center">PENDING</th>
                        <th class="text-center">REJECTED</th>
                        <th class="text-center">APPROVED</th>
                        <th class="text-center">TOTAL WORKING HOURS</th>
                        <th class="text-center">ATTENDANCE %</th>
                        <th class="text-end pe-4">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        @php
                            $stats = $u->monthly_stats ?? null;
                            $isSelected = $selectedUser && $selectedUser->id === $u->id;
                        @endphp
                        <tr class="{{ $isSelected ? 'table-primary bg-opacity-25' : '' }}">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-premium" style="width: 32px; height: 32px;">
                                        @if ($u->profile_image)
                                            <img src="{{ asset('storage/' . $u->profile_image) }}" alt="{{ $u->name }}">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                                {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-high">{{ $u->name }}</div>
                                        <div class="text-low extra-small">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center text-success fw-bold">{{ $stats['present_days'] ?? 0 }}</td>
                            <td class="text-center text-warning fw-bold">{{ $stats['half_days'] ?? 0 }}</td>
                            <td class="text-center text-danger fw-bold">{{ $stats['absent_days'] ?? 0 }}</td>
                            <td class="text-center text-warning">{{ $stats['pending_days'] ?? 0 }}</td>
                            <td class="text-center text-danger">{{ $stats['rejected_days'] ?? 0 }}</td>
                            <td class="text-center text-success">{{ $stats['approved_days'] ?? 0 }}</td>
                            <td class="text-center text-high fw-medium">
                                <span class="badge-premium bg-subtle text-high border-subtle">
                                    {{ $stats['formatted_working_hours'] ?? '0h 0m' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $pct = $stats['attendance_percentage'] ?? 0;
                                    $barColor = $pct >= 85 ? 'success' : ($pct >= 70 ? 'warning' : 'danger');
                                @endphp
                                <div class="d-inline-flex align-items-center gap-2" style="min-width: 90px;">
                                    <span class="text-high fw-bold small">{{ $pct }}%</span>
                                    <div class="progress flex-grow-1" style="height: 6px; background: rgba(var(--border-main-rgb, 128,128,128), 0.2);">
                                        <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $pct }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.attendance.monthly', array_merge(request()->except('page'), ['month' => $month, 'user_id' => $u->id])) }}" 
                                       class="action-link border-0 bg-transparent" title="View Details & Calendar">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-low">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">No employee monthly records found for this month.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Advanced Filter Slideover for Monthly Attendance -->
    <div class="filter-slideover" id="filterSlideoverMonthlyAdmin">
        <form action="{{ route('admin.attendance.monthly') }}" method="GET" class="h-100 d-flex flex-column">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Filter Monthly Overview</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('filterSlideoverMonthlyAdmin').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">EMPLOYEE</label>
                    <x-select name="user_id" placeholder="All Employees" :selected="request('user_id')">
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">ATTENDANCE STATUS</label>
                    <x-select name="status" placeholder="All Records" :selected="request('status')">
                        <option value="pending">Pending Approvals</option>
                        <option value="absent">Has Absences</option>
                        <option value="half_day">Has Half Days</option>
                        <option value="present">Has Present Days</option>
                    </x-select>
                </div>

                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">MONTH</label>
                    <input type="month" name="month" value="{{ $month }}" class="form-premium-control bg-white text-dark border-main w-100">
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['month', 'page']), ['month' => date('Y-m')])) }}" 
                           class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month == date('Y-m') ? 'active' : '' }}">
                            This Month
                        </a>
                        <a href="{{ route('admin.attendance.monthly', array_merge(request()->except(['month', 'page']), ['month' => date('Y-m', strtotime('-1 month'))])) }}" 
                           class="btn-premium btn-premium-secondary btn-sm flex-fill text-center text-decoration-none {{ $month == date('Y-m', strtotime('-1 month')) ? 'active' : '' }}">
                            Previous Month
                        </a>
                    </div>
                </div>
            </div>

            <div class="filter-slideover-footer d-flex gap-2">
                <a href="{{ route('admin.attendance.monthly') }}" class="btn-premium btn-premium-secondary flex-fill text-center text-decoration-none bg-white text-dark border-main">
                    Reset
                </a>
                <button type="submit" class="btn-premium btn-premium-primary flex-fill justify-content-center">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('openFilterModal', () => {
            const slideover = document.getElementById('filterSlideoverMonthlyAdmin');
            if (slideover) slideover.classList.add('show');
        });
    </script>
    @endpush
</x-admin>
