<x-admin title="Meetings & Calls | Team Tasker">
    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Meetings & Calls</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage scheduled meetings, view active sessions, and
                call history.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create', App\Models\Meeting::class)
                <a href="{{ route('admin.meetings.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> Schedule Meeting
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="data-grid-wrapper mb-5">
        <!-- Header Controls & Search Toolbar -->
        <div class="data-grid-top flex-wrap gap-3">
            <!-- Search & Filters -->
            <div class="data-grid-search" style="min-width: 260px;">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.meetings.index') }}" method="GET" id="searchFormMeetings">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    @if(request('type')) <input type="hidden" name="type" value="{{ request('type') }}"> @endif
                    @if(request('mode')) <input type="hidden" name="mode" value="{{ request('mode') }}"> @endif
                    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                    @if(request('created_by')) <input type="hidden" name="created_by"
                    value="{{ request('created_by') }}"> @endif
                    @if(request('created_at')) <input type="hidden" name="created_at"
                    value="{{ request('created_at') }}"> @endif
                    <input type="text" name="search" placeholder="Search meetings..." value="{{ request('search') }}"
                        onchange="document.getElementById('searchFormMeetings').submit()">
                </form>
            </div>

            <!-- Segmented Tab Pills -->
            <div class="d-flex bg-glass border border-main p-1" style="border-radius: var(--radius-md);">
                <a href="{{ route('admin.meetings.index') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1 {{ $tab === 'all' ? 'active shadow-sm' : '' }}"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm); {{ $tab === 'all' ? 'background: var(--primary); color: #fff;' : '' }}">
                    All
                </a>
                <a href="{{ route('admin.meetings.index', array_merge(request()->query(), ['tab' => 'upcoming'])) }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1 {{ $tab === 'upcoming' ? 'active shadow-sm' : '' }}"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm); {{ $tab === 'upcoming' ? 'background: var(--primary); color: #fff;' : '' }}">
                    Upcoming & Active
                </a>
                <a href="{{ route('admin.meetings.index', array_merge(request()->query(), ['tab' => 'history'])) }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1 {{ $tab === 'history' ? 'active shadow-sm' : '' }}"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm); {{ $tab === 'history' ? 'background: var(--primary); color: #fff;' : '' }}">
                    History
                </a>
            </div>

            <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                <div class="data-grid-results text-low small fw-semibold">
                    {{ $meetings->total() }} Results
                </div>

                <div class="data-grid-actions">
                    {{ $meetings->links() }}
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">MEETING TITLE</th>
                        <th>TYPE & MODE</th>
                        <th>ASSOCIATED WITH</th>
                        <th>CREATOR</th>
                        <th>SCHEDULED / DATE</th>
                        <th>DURATION</th>
                        <th>STATUS</th>
                        <th class="pe-4 text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meetings as $meeting)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-high">{{ $meeting->title }}</div>
                                @if($meeting->description)
                                    <small class="text-low d-block"
                                        style="font-size: 0.75rem;">{{ Str::limit($meeting->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge-premium me-1"
                                    style="background: var(--bg-input); color: var(--text-high);">
                                    {{ str_replace('_', ' ', ucfirst($meeting->type)) }}
                                </span>
                                <span
                                    class="badge-premium {{ $meeting->mode === 'video' ? 'bg-primary text-white' : 'bg-info text-dark' }}">
                                    {{ $meeting->mode === 'video' ? '📹 Video' : '📞 Audio' }}
                                </span>
                            </td>
                            <td>
                                @if($meeting->project)
                                    <div class="small"><i class="fas fa-folder me-1 text-warning"></i>
                                        {{ $meeting->project->name }}
                                    </div>
                                @endif
                                @if($meeting->task)
                                    <div class="small"><i class="fas fa-tasks me-1 text-info"></i> {{ $meeting->task->title }}
                                    </div>
                                @endif
                                @if(!$meeting->project && !$meeting->task)
                                    <span class="text-low small">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-premium" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        {{ substr($meeting->createdBy->name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="text-high small">{{ $meeting->createdBy->name ?? 'System' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($meeting->scheduled_at)
                                    <div class="text-high small">{{ $meeting->scheduled_at->format('M d, Y') }}</div>
                                    <small class="text-low"
                                        style="font-size: 0.75rem;">{{ $meeting->scheduled_at->format('h:i A') }}</small>
                                @else
                                    <div class="text-high small">{{ $meeting->created_at->format('M d, Y') }}</div>
                                    <small class="text-low"
                                        style="font-size: 0.75rem;">{{ $meeting->created_at->format('h:i A') }}</small>
                                @endif
                            </td>
                            <td>
                                @if($meeting->started_at && $meeting->ended_at)
                                    @php
                                        $sec = max(1, $meeting->started_at->diffInSeconds($meeting->ended_at));
                                    @endphp
                                    @if($sec < 60)
                                        {{ $sec }} sec
                                    @else
                                        {{ floor($sec / 60) }}m {{ $sec % 60 }}s
                                    @endif
                                @elseif($meeting->duration)
                                    {{ $meeting->duration }} min
                                @else
                                    <span class="text-low">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'var(--accent)',
                                        'ringing' => '#f59e0b',
                                        'scheduled' => '#0ea5e9',
                                        'completed' => 'var(--text-medium)',
                                        'cancelled' => 'var(--danger)',
                                        'rejected' => 'var(--danger)',
                                        'missed' => 'var(--danger)',
                                    ];
                                    $stColor = $statusColors[$meeting->status] ?? 'var(--text-medium)';
                                @endphp
                                <span class="badge-premium"
                                    style="background: color-mix(in srgb, {{ $stColor }} 15%, transparent); color: {{ $stColor }}; border: 1px solid color-mix(in srgb, {{ $stColor }} 30%, transparent);">
                                    {{ ucfirst($meeting->status) }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    @if(in_array($meeting->status, ['scheduled', 'active', 'ringing']))
                                        @can('join', $meeting)
                                            <a href="{{ route('admin.meetings.join', $meeting->uuid) }}"
                                                class="btn-premium btn-premium-primary btn-sm py-1 px-3">
                                                <i class="fas fa-video me-1"></i> Join
                                            </a>
                                        @endcan
                                    @endif
                                    @can('view', $meeting)
                                        <a href="{{ route('admin.meetings.show', $meeting->uuid) }}"
                                            class="btn-premium btn-premium-secondary btn-sm p-0 d-inline-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan
                                    @if(in_array($meeting->status, ['scheduled', 'active', 'ringing']))
                                        @can('cancel', $meeting)
                                            <form action="{{ route('admin.meetings.cancel', $meeting->uuid) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Cancel this meeting?');">
                                                @csrf
                                                <button type="submit"
                                                    class="btn-premium btn-sm p-0 d-inline-flex align-items-center justify-content-center"
                                                    style="width: 32px; height: 32px; background: rgba(var(--danger-rgb), 0.1); color: var(--danger); border: 1px solid rgba(var(--danger-rgb), 0.2);"
                                                    title="Cancel Meeting">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-low">
                                <i class="fas fa-calendar-times mb-3" style="font-size: 2.5rem; opacity: 0.5;"></i>
                                <p class="mb-0">No meetings found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <!-- Filter Slideover -->
    <div class="filter-slideover" id="filterSlideoverMeetings">
        <form action="{{ route('admin.meetings.index') }}" method="GET" class="h-100 d-flex flex-column">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Meeting Filters</h4>
                <div class="filter-slideover-close"
                    onclick="document.getElementById('filterSlideoverMeetings').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH TITLE / DESCRIPTION</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-premium-control bg-white text-dark border-main" placeholder="Search meetings...">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">MEETING TYPE</label>
                    <x-select name="type" placeholder="All Types" :selected="request('type')">
                        <option value="" class="bg-dark">All Types</option>
                        <option value="direct_call" class="bg-dark">Direct Call</option>
                        <option value="group_call" class="bg-dark">Group Call</option>
                        <option value="scheduled_meeting" class="bg-dark">Scheduled Meeting</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CALL MODE</label>
                    <x-select name="mode" placeholder="All Modes" :selected="request('mode')">
                        <option value="" class="bg-dark">All Modes</option>
                        <option value="video" class="bg-dark">📹 Video</option>
                        <option value="audio" class="bg-dark">📞 Audio</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <x-select name="status" placeholder="All Statuses" :selected="request('status')">
                        <option value="" class="bg-dark">All Statuses</option>
                        <option value="scheduled" class="bg-dark">Scheduled</option>
                        <option value="ringing" class="bg-dark">Ringing</option>
                        <option value="active" class="bg-dark">Active</option>
                        <option value="completed" class="bg-dark">Completed</option>
                        <option value="cancelled" class="bg-dark">Cancelled</option>
                        <option value="rejected" class="bg-dark">Rejected</option>
                        <option value="missed" class="bg-dark">Missed</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATOR</label>
                    <x-select name="created_by" placeholder="All Creators" :selected="request('created_by')">
                        <option value="" class="bg-dark">All Creators</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" class="bg-dark">{{ $u->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATED AT</label>
                    <input type="date" name="created_at" value="{{ request('created_at') }}"
                        class="form-premium-control bg-white text-dark border-main">
                </div>
            </div>
            <div class="filter-slideover-footer">
                <a href="{{ route('admin.meetings.index') }}"
                    class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main text-decoration-none">Reset</a>
                <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center"
                    style="background: #0ea5e9;">Apply Filters</button>
            </div>
        </form>
    </div>
</x-admin>