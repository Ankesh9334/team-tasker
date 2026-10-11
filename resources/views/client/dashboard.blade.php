<x-client title="Client Dashboard">

    {{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="glass-card h-100" style="border: 1px solid var(--border-main);">
                <div class="stat-icon-premium icon-primary-premium mb-3"><i class="fas fa-ticket-alt"></i></div>
                <h3 class="h2 fw-bold mb-1" style="color: var(--text-high);">{{ $tickets->total() }}</h3>
                <div class="heading-label" style="font-size: 0.75rem;">Total Tickets</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card h-100" style="border: 1px solid var(--border-main);">
                <div class="stat-icon-premium icon-success-premium mb-3"><i class="fas fa-tasks"></i></div>
                <h3 class="h2 fw-bold mb-1" style="color: var(--text-high);">{{ $tasks->total() }}</h3>
                <div class="heading-label" style="font-size: 0.75rem;">Active Tasks</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card h-100" style="border: 1px solid var(--border-main);">
                <div class="stat-icon-premium mb-3" style="background: rgba(var(--danger-rgb), 0.1); color: var(--danger);">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="h2 fw-bold mb-1" style="color: var(--text-high);">
                    {{ $tickets->getCollection()->where('status', 'open')->count() }}</h3>
                <div class="heading-label" style="font-size: 0.75rem;">Open Tickets</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card h-100" style="border: 1px solid var(--border-main);">
                <div class="stat-icon-premium icon-primary-premium mb-3"><i class="fas fa-check-circle"></i></div>
                <h3 class="h2 fw-bold mb-1" style="color: var(--text-high);">
                    {{ $tasks->getCollection()->where('progress', 100)->count() }}</h3>
                <div class="heading-label" style="font-size: 0.75rem;">Completed Tasks</div>
            </div>
        </div>
    </div>

    {{-- ── Tickets Section ──────────────────────────────────────────────────── --}}
    <div id="tickets" class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">My Support Tickets</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Track and manage your submitted support requests.</p>
        </div>
        <a href="{{ route('client.tickets.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> New Ticket
        </a>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('client.dashboard') }}" method="GET" id="ticketSearchForm">
                    <input type="text" name="ticket_search" placeholder="Search tickets..."
                        value="{{ request('ticket_search') }}"
                        onchange="document.getElementById('ticketSearchForm').submit()">
                    @if(request('ticket_status'))   <input type="hidden" name="ticket_status"   value="{{ request('ticket_status') }}">   @endif
                    @if(request('ticket_priority')) <input type="hidden" name="ticket_priority" value="{{ request('ticket_priority') }}"> @endif
                    @if(request('ticket_date'))     <input type="hidden" name="ticket_date"     value="{{ request('ticket_date') }}">     @endif
                </form>
            </div>
            <div class="data-grid-results">{{ $tickets->total() }} Results</div>
            <div class="data-grid-actions">
                @php $ticketFiltered = request()->hasAny(['ticket_search','ticket_status','ticket_priority','ticket_date']); @endphp
                @if($ticketFiltered)
                    <span class="badge rounded-pill" style="background: var(--primary); color: #fff; font-size: 0.6rem; padding: 2px 6px;">ON</span>
                @endif
                {{ $tickets->appends(request()->except('tickets_page'))->links() }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>ID <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>SUBJECT &amp; EXCERPT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>STATUS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>PRIORITY <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>CREATED AT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="text-low" style="color: #64748b !important;">#{{ $ticket->id }}</td>
                            <td>
                                <div class="text-high fw-medium">{{ Str::limit($ticket->subject, 45) }}</div>
                                <div class="text-low" style="font-size: 0.75rem;">{{ Str::limit(strip_tags($ticket->body), 55) }}</div>
                            </td>
                            <td>
                                @php
                                    $sc = match($ticket->status) {
                                        'open','in_progress' => ['#rgba(14,165,233,0.1)', '#0ea5e9'],
                                        'resolved','closed'  => ['rgba(16,185,129,0.1)', '#10b981'],
                                        default              => ['#f1f5f9', '#64748b'],
                                    };
                                @endphp
                                <span class="badge-premium" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                    {{ str_replace('_', ' ', $ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $pc = match($ticket->priority) {
                                        'urgent' => ['rgba(239,68,68,0.1)',  '#ef4444', 'rgba(239,68,68,0.2)'],
                                        'high'   => ['rgba(245,158,11,0.1)', '#f59e0b', 'rgba(245,158,11,0.2)'],
                                        default  => ['#f1f5f9',              '#64748b', 'transparent'],
                                    };
                                @endphp
                                <span class="badge-premium" style="background:{{ $pc[0] }}; color:{{ $pc[1] }}; border:1px solid {{ $pc[2] }}; font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td class="text-high">{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                            <td class="text-end pe-2">
                                <a href="{{ route('client.tickets.show', $ticket->id) }}" class="action-link" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-medium">
                                <i class="fas fa-ticket-alt mb-2 d-block text-low" style="font-size: 2rem;"></i>
                                No tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Tasks Section ────────────────────────────────────────────────────── --}}
    <div id="tasks" class="top-bar-premium mb-4 mt-2">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Related Tasks</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Tasks created for your support tickets.</p>
        </div>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('client.dashboard') }}" method="GET" id="taskSearchForm">
                    <input type="text" name="task_search" placeholder="Search tasks..."
                        value="{{ request('task_search') }}"
                        onchange="document.getElementById('taskSearchForm').submit()">
                    {{-- preserve ticket filters --}}
                    @if(request('ticket_search'))   <input type="hidden" name="ticket_search"   value="{{ request('ticket_search') }}">   @endif
                    @if(request('ticket_status'))   <input type="hidden" name="ticket_status"   value="{{ request('ticket_status') }}">   @endif
                    @if(request('ticket_priority')) <input type="hidden" name="ticket_priority" value="{{ request('ticket_priority') }}"> @endif
                    @if(request('ticket_date'))     <input type="hidden" name="ticket_date"     value="{{ request('ticket_date') }}">     @endif
                    @if(request('tickets_page'))    <input type="hidden" name="tickets_page"    value="{{ request('tickets_page') }}">    @endif
                </form>
            </div>
            <div class="data-grid-results">{{ $tasks->total() }} Results</div>
             <div class="data-grid-actions">
                @php $tasksFiltered = request()->hasAny(['tasks_search','tasks_status','tasks_priority','tasks_date']); @endphp
                @if($tasksFiltered)
                    <span class="badge rounded-pill" style="background: var(--primary); color: #fff; font-size: 0.6rem; padding: 2px 6px;">ON</span>
                @endif
                {{ $tasks->appends(request()->except('tasks_page'))->links() }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>TASK DETAILS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>LINKED TICKET <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>STATUS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>ASSIGNED AGENT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>LAST ACTIVITY <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <div class="text-high fw-medium">{{ Str::limit($task->title, 45) }}</div>
                                <div class="text-low" style="font-size: 0.75rem;">{{ Str::limit(strip_tags($task->description), 55) }}</div>
                            </td>
                            <td>
                                @if ($task->ticket_id)
                                    <a href="{{ route('client.tickets.show', $task->ticket_id) }}"
                                        class="badge-premium d-inline-flex align-items-center gap-1 text-decoration-none"
                                        style="background:rgba(var(--primary-rgb),0.1); color:var(--primary); font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                        <i class="fas fa-ticket-alt" style="font-size:0.7rem;"></i> TKT #{{ $task->ticket_id }}
                                    </a>
                                @else
                                    <span class="text-low italic small">Direct Task</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-premium" style="background:rgba(var(--primary-rgb),0.1); color:var(--primary); font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                    {{ $task->status->name ?? 'Pending' }}
                                </span>
                            </td>
                            <td>
                                @if ($task->assignedTo)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-premium" style="width:28px; height:28px; font-size:0.7rem;">
                                            {{ substr($task->assignedTo->name, 0, 1) }}
                                        </div>
                                        <span class="small" style="color:var(--text-medium);">{{ $task->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-low italic small">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-high" style="font-size:0.85rem;">{{ $task->updated_at->diffForHumans() }}</td>
                            <td class="text-end pe-2">
                                <a href="{{ route('client.tasks.show', $task->id) }}" class="action-link" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-medium">
                                <i class="fas fa-tasks mb-2 d-block text-low" style="font-size: 2rem;"></i>
                                No tasks found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Ticket Filter Slideover ───────────────────────────────────────────── --}}
    <div class="filter-slideover" id="ticketFilterSlideover">
        <form action="{{ route('client.dashboard') }}" method="GET" class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Ticket Filters</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('ticketFilterSlideover').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH QUERY</label>
                    <input type="text" name="ticket_search" value="{{ request('ticket_search') }}"
                        class="form-premium-control" placeholder="Search subject or body...">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <select name="ticket_status" class="form-premium-control">
                        <option value="">All Statuses</option>
                        <option value="open"               {{ request('ticket_status') == 'open'               ? 'selected' : '' }}>Open</option>
                        <option value="in_progress"        {{ request('ticket_status') == 'in_progress'        ? 'selected' : '' }}>In Progress</option>
                        <option value="waiting_for_client" {{ request('ticket_status') == 'waiting_for_client' ? 'selected' : '' }}>Waiting for Reply</option>
                        <option value="resolved"           {{ request('ticket_status') == 'resolved'           ? 'selected' : '' }}>Resolved</option>
                        <option value="closed"             {{ request('ticket_status') == 'closed'             ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">PRIORITY</label>
                    <select name="ticket_priority" class="form-premium-control">
                        <option value="">All Priorities</option>
                        <option value="low"    {{ request('ticket_priority') == 'low'    ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('ticket_priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high"   {{ request('ticket_priority') == 'high'   ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ request('ticket_priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATED DATE</label>
                    <input type="date" name="ticket_date" value="{{ request('ticket_date') }}" class="form-premium-control">
                </div>
                {{-- Preserve task filters --}}
                @if(request('task_search'))   <input type="hidden" name="task_search"   value="{{ request('task_search') }}">   @endif
                @if(request('tasks_page'))    <input type="hidden" name="tasks_page"    value="{{ request('tasks_page') }}">    @endif
            </div>
            <div class="filter-slideover-footer">
                <a href="{{ route('client.dashboard') }}#tickets"
                    class="btn-premium btn-premium-secondary w-50 justify-content-center">Reset</a>
                <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- ── Task Filter Slideover ────────────────────────────────────────────── --}}
    <div class="filter-slideover" id="taskFilterSlideover">
        <form action="{{ route('client.dashboard') }}" method="GET" class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Task Filters</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('taskFilterSlideover').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH QUERY</label>
                    <input type="text" name="task_search" value="{{ request('task_search') }}"
                        class="form-premium-control" placeholder="Search task title or description...">
                </div>
                {{-- Preserve ticket filters --}}
                @if(request('ticket_search'))   <input type="hidden" name="ticket_search"   value="{{ request('ticket_search') }}">   @endif
                @if(request('ticket_status'))   <input type="hidden" name="ticket_status"   value="{{ request('ticket_status') }}">   @endif
                @if(request('ticket_priority')) <input type="hidden" name="ticket_priority" value="{{ request('ticket_priority') }}"> @endif
                @if(request('ticket_date'))     <input type="hidden" name="ticket_date"     value="{{ request('ticket_date') }}">     @endif
                @if(request('tickets_page'))    <input type="hidden" name="tickets_page"    value="{{ request('tickets_page') }}">    @endif
            </div>
            <div class="filter-slideover-footer">
                <a href="{{ route('client.dashboard') }}#tasks"
                    class="btn-premium btn-premium-secondary w-50 justify-content-center">Reset</a>
                <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- ── Overlay (closes any open slideover on click) ─────────────────────── --}}
    <div class="filter-slideover-overlay" id="slideoverOverlay"
        onclick="document.querySelectorAll('.filter-slideover').forEach(el => el.classList.remove('show')); this.classList.remove('show')">
    </div>

    <script>
        // Show overlay whenever any slideover opens
        document.querySelectorAll('.filter-slideover').forEach(function(sl) {
            const observer = new MutationObserver(function() {
                document.getElementById('slideoverOverlay').classList.toggle('show', sl.classList.contains('show'));
            });
            observer.observe(sl, { attributes: true, attributeFilter: ['class'] });
        });
    </script>

</x-client>
