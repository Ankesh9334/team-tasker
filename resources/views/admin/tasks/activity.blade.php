<x-admin>
    <x-slot:title>
        System Activity | Team Tasker
    </x-slot:title>

    @php
        $hasFilters = request('user_id') || request('type') || request('date') || request('search');
        $selectedUserIds = is_array(request('user_id'))
            ? array_values(array_filter(array_map('strval', request('user_id'))))
            : (request('user_id') ? [(string) request('user_id')] : []);

        $selectedUsersList = !empty($selectedUserIds) 
            ? $users->whereIn('id', $selectedUserIds) 
            : collect();
    @endphp

    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary" style="font-size: 1.4rem;"></i>
                System Activity
            </h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Global chronological audit feed of all task interactions, system updates, and client communications.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tasks.board') }}" class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                <i class="fas fa-columns me-1"></i> Kanban Board
            </a>
            <a href="{{ route('index') }}" class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                <i class="fas fa-tasks me-1"></i> All Tasks
            </a>
            <button type="button" class="btn-premium btn-premium-primary px-3 py-2 shadow-sm d-flex align-items-center gap-1"
                onclick="document.getElementById('filterSlideoverActivity').classList.add('show')">
                <i class="fas fa-filter"></i> Filter
                @if($hasFilters)
                    <span class="badge bg-white text-primary rounded-pill ms-1" style="font-size: 0.7rem;">Active</span>
                @endif
            </button>
        </div>
    </div>

    <!-- Metric Summary Cards (Clickable to Filter) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('tasks.activity', array_merge(request()->except(['type', 'page']))) }}"
               class="text-decoration-none d-block h-100">
                <div class="glass-card p-3 h-100 border-main transition-all {{ !request('type') ? 'border-primary shadow-sm' : '' }}"
                     style="{{ !request('type') ? 'background: rgba(var(--primary-rgb), 0.04);' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-low small fw-semibold text-uppercase tracking-wider">Total Activities</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width: 34px; height: 34px; background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                            <i class="fas fa-history" style="font-size: 0.85rem;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <h3 class="fw-bold mb-0 text-high">{{ number_format($activityStats['total']) }}</h3>
                        @if(!request('type'))
                            <span class="badge bg-primary bg-opacity-15 text-primary small">All</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ route('tasks.activity', array_merge(request()->except(['page']), ['type' => 'log'])) }}"
               class="text-decoration-none d-block h-100">
                <div class="glass-card p-3 h-100 border-main transition-all {{ request('type') === 'log' ? 'border-info shadow-sm' : '' }}"
                     style="{{ request('type') === 'log' ? 'background: rgba(6, 182, 212, 0.05);' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-low small fw-semibold text-uppercase tracking-wider">System Updates</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width: 34px; height: 34px; background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                            <i class="fas fa-cogs" style="font-size: 0.85rem;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <h3 class="fw-bold mb-0 text-info">{{ number_format($activityStats['logs']) }}</h3>
                        @if(request('type') === 'log')
                            <span class="badge bg-info bg-opacity-15 text-info small">Filtered</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="{{ route('tasks.activity', array_merge(request()->except(['page']), ['type' => 'message'])) }}"
               class="text-decoration-none d-block h-100">
                <div class="glass-card p-3 h-100 border-main transition-all {{ request('type') === 'message' ? 'border-success shadow-sm' : '' }}"
                     style="{{ request('type') === 'message' ? 'background: rgba(16, 185, 129, 0.05);' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-low small fw-semibold text-uppercase tracking-wider">Client Messages</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                             style="width: 34px; height: 34px; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                            <i class="fas fa-comments" style="font-size: 0.85rem;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <h3 class="fw-bold mb-0 text-success">{{ number_format($activityStats['messages']) }}</h3>
                        @if(request('type') === 'message')
                            <span class="badge bg-success bg-opacity-15 text-success small">Filtered</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Active Users</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                         style="width: 34px; height: 34px; background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <i class="fas fa-users" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-high">{{ number_format($activityStats['users']) }}</h3>
            </div>
        </div>
    </div>

    <!-- Data Grid Wrapper with Feed -->
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" form="activitySearchForm" placeholder="Search activity notes, users, tasks, projects..."
                       value="{{ request('search') }}" onchange="document.getElementById('activitySearchForm').submit()">
                @if(request('search'))
                    <a href="{{ route('tasks.activity', array_merge(request()->except('search'))) }}" class="text-low ms-2 text-decoration-none small" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            <div class="data-grid-results">{{ number_format($activities->total()) }} Activities</div>

            <div class="data-grid-actions d-flex align-items-center gap-2">
                @if($hasFilters)
                    <a href="{{ route('tasks.activity') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1 text-decoration-none">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                @endif
                {{ $activities->links('components.pagination.premium') }}
            </div>
        </div>

        <!-- Active Filter Pills Bar -->
        @if($hasFilters)
            <div class="px-4 py-2 border-bottom border-subtle bg-subtle d-flex flex-wrap align-items-center gap-2">
                <span class="text-low small fw-semibold">Active Filters:</span>

                @if(!empty($selectedUserIds))
                    @foreach($selectedUsersList as $su)
                        @php
                            $remainingUserIds = array_diff($selectedUserIds, [(string) $su->id]);
                            $userFilterUrl = route('tasks.activity', array_merge(request()->except(['page']), ['user_id' => $remainingUserIds]));
                        @endphp
                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small d-inline-flex align-items-center gap-1">
                            <i class="fas fa-user small"></i> {{ $su->name }}
                            <a href="{{ $userFilterUrl }}" class="text-primary text-decoration-none ms-1"><i class="fas fa-times"></i></a>
                        </span>
                    @endforeach
                @endif

                @if(request('type'))
                    <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill small d-inline-flex align-items-center gap-1">
                        Type: {{ request('type') === 'log' ? 'System Updates' : 'Client Messages' }}
                        <a href="{{ route('tasks.activity', array_merge(request()->except(['type', 'page']))) }}" class="text-info text-decoration-none ms-1"><i class="fas fa-times"></i></a>
                    </span>
                @endif

                @if(request('date'))
                    <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill small d-inline-flex align-items-center gap-1">
                        <i class="fas fa-calendar-alt small"></i> {{ \Carbon\Carbon::parse(request('date'))->format('d M Y') }}
                        <a href="{{ route('tasks.activity', array_merge(request()->except(['date', 'page']))) }}" class="text-warning text-decoration-none ms-1"><i class="fas fa-times"></i></a>
                    </span>
                @endif

                @if(request('search'))
                    <span class="badge bg-secondary bg-opacity-15 text-high border border-subtle px-2 py-1 rounded-pill small d-inline-flex align-items-center gap-1">
                        Search: "{{ request('search') }}"
                        <a href="{{ route('tasks.activity', array_merge(request()->except(['search', 'page']))) }}" class="text-high text-decoration-none ms-1"><i class="fas fa-times"></i></a>
                    </span>
                @endif

                <a href="{{ route('tasks.activity') }}" class="text-danger small text-decoration-none ms-2">Clear All</a>
            </div>
        @endif

        <!-- Activity Timeline Feed -->
        <div class="p-4">
            <div class="activity-timeline">
                @forelse($activities as $log)
                    @php
                        $actorName = $log->user ? $log->user->name : ($log->client ? $log->client->name : 'System');
                        $actorEmail = $log->user ? $log->user->email : ($log->client ? $log->client->email : null);
                        $profileImage = $log->user && $log->user->profile_image ? $log->user->profile_image : null;
                        $isMessage = $log->type === 'message';
                    @endphp
                    <div class="d-flex gap-3 mb-4 position-relative">
                        <!-- Avatar & Connector -->
                        <div class="position-relative d-flex flex-column align-items-center" style="width: 38px;">
                            <div class="avatar-premium rounded-circle shadow-sm d-flex align-items-center justify-content-center overflow-hidden"
                                 style="width: 38px; height: 38px; font-size: 0.9rem; font-weight: 700;
                                        background: {{ $isMessage ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.12)' }};
                                        color: {{ $isMessage ? '#10b981' : '#3b82f6' }};
                                        border: 2px solid {{ $isMessage ? '#10b98140' : '#3b82f640' }};">
                                @if($profileImage)
                                    <img src="{{ asset('storage/' . $profileImage) }}" alt="{{ $actorName }}" class="w-100 h-100 object-fit-cover">
                                @else
                                    {{ strtoupper(substr($actorName, 0, 1)) }}
                                @endif
                            </div>
                            @if (!$loop->last)
                                <div class="border-start border-2 border-subtle flex-grow-1 my-2" style="width: 2px; min-height: 28px;"></div>
                            @endif
                        </div>

                        <!-- Content Card -->
                        <div class="flex-grow-1 glass-card p-3 border-main rounded-3 shadow-none" style="background: var(--bg-surface);">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="fw-bold text-high">{{ $actorName }}</span>
                                    @if($actorEmail)
                                        <span class="text-low small font-monospace d-none d-sm-inline">({{ $actorEmail }})</span>
                                    @endif

                                    <span class="text-low small">&bull;</span>

                                    @if($log->task_id && $log->task)
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-0.5 rounded-pill small fw-semibold">
                                            <i class="fas fa-tasks me-1" style="font-size: 0.65rem;"></i> Task
                                        </span>
                                        <a href="{{ route('details', $log->task_id) }}" class="fw-semibold text-primary small text-decoration-none hover-underline text-truncate" style="max-width: 320px;" title="{{ $log->task->title }}">
                                            {{ $log->task->title }}
                                        </a>
                                    @elseif($log->project_id && $log->project)
                                        <span class="badge bg-info bg-opacity-10 text-info px-2 py-0.5 rounded-pill small fw-semibold">
                                            <i class="fas fa-folder me-1" style="font-size: 0.65rem;"></i> Project
                                        </span>
                                        <a href="{{ route('admin.projects.show', $log->project_id) }}" class="fw-semibold text-info small text-decoration-none hover-underline text-truncate" style="max-width: 320px;" title="{{ $log->project->name }}">
                                            {{ $log->project->name }}
                                        </a>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    @if ($isMessage)
                                        <span class="badge-premium d-inline-flex align-items-center gap-1 px-2 py-1 fw-semibold"
                                              style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); font-size: 0.7rem; border-radius: 4px;">
                                            <i class="fas fa-comments"></i> Client Message
                                        </span>
                                    @else
                                        <span class="badge-premium d-inline-flex align-items-center gap-1 px-2 py-1 fw-semibold"
                                              style="background: var(--bg-input); color: var(--text-low); border: 1px solid var(--border-subtle); font-size: 0.7rem; border-radius: 4px;">
                                            <i class="fas fa-history"></i> System Update
                                        </span>
                                    @endif

                                    <span class="text-low small" title="{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '' }}">
                                        <i class="far fa-clock me-1"></i>{{ $log->created_at ? $log->created_at->diffForHumans() : '—' }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-main-50 small ck-content lh-base" style="color: var(--text-high);">
                                {!! $log->note !!}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="text-low">
                            <i class="fas fa-history fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5 class="text-high fw-semibold">No activity logs found</h5>
                            <p class="mb-3">No activity entries match your selected filter criteria.</p>
                            @if($hasFilters)
                                <a href="{{ route('tasks.activity') }}" class="btn-premium btn-premium-primary px-4 py-2 text-decoration-none">
                                    <i class="fas fa-redo me-1"></i> Reset Filters
                                </a>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Standalone search form to submit GET requests while preserving filters --}}
    <form action="{{ route('tasks.activity') }}" method="GET" id="activitySearchForm" class="d-none">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        @if(request('date'))
            <input type="hidden" name="date" value="{{ request('date') }}">
        @endif
        @if(request('user_id'))
            @if(is_array(request('user_id')))
                @foreach(request('user_id') as $uid)
                    <input type="hidden" name="user_id[]" value="{{ $uid }}">
                @endforeach
            @else
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif
        @endif
    </form>

    <!-- Advanced Filter Slideover for System Activity -->
    <div class="filter-slideover" id="filterSlideoverActivity">
        <form action="{{ route('tasks.activity') }}" method="GET" class="h-100 d-flex flex-column">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Filter Activity</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('filterSlideoverActivity').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <div class="filter-slideover-body">
                <!-- EMPLOYEE / USER Filter using x-multiselect -->
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">EMPLOYEE / USER</label>
                    <x-multiselect 
                        id="filter_user_id" 
                        name="user_id[]" 
                        placeholder="Select employees / users..." 
                        :selected="$selectedUserIds"
                        class="w-100"
                    >
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </x-multiselect>
                    <div class="text-low small mt-1">Select one or multiple users to isolate their activity feed.</div>
                </div>

                <!-- ACTIVITY TYPE Filter using x-select -->
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">ACTIVITY TYPE</label>
                    <x-select 
                        id="filter_type" 
                        name="type" 
                        placeholder="All Activity Types" 
                        :selected="request('type')"
                        class="w-100"
                    >
                        <option value="log">System Updates (Logs)</option>
                        <option value="message">Client Messages</option>
                    </x-select>
                </div>

                <!-- DATE Filter -->
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SPECIFIC DATE</label>
                    <input type="date" name="date" class="form-premium-control bg-white text-dark border-main w-100" value="{{ request('date') }}">
                </div>

                <!-- PER PAGE -->
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">ITEMS PER PAGE</label>
                    <x-select 
                        id="filter_per_page" 
                        name="per_page" 
                        placeholder="20 items" 
                        :selected="request('per_page', 20)"
                        class="w-100"
                    >
                        <option value="10">10 items</option>
                        <option value="20">20 items</option>
                        <option value="50">50 items</option>
                        <option value="100">100 items</option>
                    </x-select>
                </div>
            </div>

            <div class="filter-slideover-footer d-flex gap-2">
                <a href="{{ route('tasks.activity') }}" class="btn-premium btn-premium-secondary flex-fill text-center text-decoration-none">
                    Reset
                </a>
                <button type="submit" class="btn-premium btn-premium-primary flex-fill">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('openFilterModal', () => {
            const slideover = document.getElementById('filterSlideoverActivity');
            if (slideover) slideover.classList.add('show');
        });
    </script>
    @endpush
</x-admin>
