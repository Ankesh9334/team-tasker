<x-dynamic-component :component="$layout">
    <x-slot:title>
        Search Results | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h3 class="h4 fw-bold mb-0" style="color: var(--text-high);">
                Search Results
            </h3>
            <p class="text-low small mb-0 mt-1">Showing results for "<strong
                    style="color: var(--primary);">{{ $query }}</strong>"</p>
        </div>
        @php
            $dashboardRoute = Auth::user()->hasRole('client') ? route('client.dashboard') : route('dashboard');
        @endphp
        <a href="{{ $dashboardRoute }}" class="btn-premium btn-premium-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    @if ($tasks->count() > 0)
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-tasks" style="color: var(--primary);"></i>
            <h5 class="fw-bold mb-0" style="color: var(--text-high);">Tasks <span class="badge-premium ms-1"
                    style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">{{ $tasks->count() }}</span>
            </h5>
        </div>
        <div class="row g-4 mb-5">
            @foreach ($tasks as $task)
                <div class="col-md-6 col-lg-4">
                    <div class="data-grid-wrapper h-100 d-flex flex-column" style="border: 1px solid var(--border-main);">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge-premium"
                                style="background: {{ $task->status->color ?? 'var(--primary)' }}1a; color: {{ $task->status->color ?? 'var(--primary)' }}; border: 1px solid {{ $task->status->color ?? 'var(--primary)' }}33;">
                                {{ $task->status->name ?? 'Unknown' }}
                            </span>
                            <div class="dropdown">
                                <button class="btn p-0" style="color: var(--text-low); background: none; border: none;"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $detailsRoute = Auth::user()->hasRole('client')
                                            ? route('client.tasks.show', $task->id)
                                            : route('details', $task->id);
                                    @endphp
                                    <li><a class="dropdown-item" href="{{ $detailsRoute }}">View Details</a></li>
                                    @if (!Auth::user()->hasRole('client'))
                                        <li><a class="dropdown-item" href="{{ route('edit', $task->id) }}">Edit Task</a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <h5 class="mb-2">
                            <a href="{{ $detailsRoute }}" class="text-decoration-none fw-bold"
                                style="color: var(--text-high);">{{ $task->title }}</a>
                        </h5>
                        <p class="text-low small mb-3 flex-grow-1">
                            {{ Str::limit(strip_tags($task->description), 100) }}</p>

                        <div class="mt-auto pt-3 d-flex justify-content-between align-items-center"
                            style="border-top: 1px solid var(--border-subtle);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-premium" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    {{ substr($task->assignedTo->name ?? 'U', 0, 1) }}
                                </div>
                                <span class="small text-low">{{ $task->assignedTo->name ?? 'Unassigned' }}</span>
                            </div>
                            <span class="small text-low">{{ $task->created_at->format('M d') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($query && $tasks->count() === 0)
        <div class="data-grid-wrapper text-center py-5 mb-5" style="border: 1px solid var(--border-main);">
            <i class="fas fa-tasks fa-2x mb-3 d-block" style="color: var(--text-low); opacity: 0.4;"></i>
            <p class="mb-0 text-low">No tasks found matching "{{ $query }}".</p>
        </div>
    @endif

    @if ($tickets->count() > 0)
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-ticket-alt" style="color: var(--accent);"></i>
            <h5 class="fw-bold mb-0" style="color: var(--text-high);">Tickets <span class="badge-premium ms-1"
                    style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent);">{{ $tickets->count() }}</span>
            </h5>
        </div>
        <div class="row g-4 mb-5">
            @foreach ($tickets as $ticket)
                <div class="col-md-6 col-lg-4">
                    <div class="data-grid-wrapper h-100 d-flex flex-column" style="border: 1px solid var(--border-main);">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            @php
                                $ticketColors = [
                                    'open' => 'var(--primary)',
                                    'in_progress' => '#3b82f6',
                                    'resolved' => '#10b981',
                                    'closed' => '#6b7280',
                                ];
                                $statusColor = $ticketColors[$ticket->status] ?? 'var(--primary)';
                                $ticketRoute = Auth::user()->hasRole('client')
                                    ? route('client.tickets.show', $ticket->id)
                                    : route('admin.tickets.show', $ticket->id);
                            @endphp
                            <span class="badge-premium"
                                style="background: {{ $statusColor }}1a; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}33;">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                            <a href="{{ $ticketRoute }}" class="btn-premium btn-premium-secondary py-1"
                                style="font-size: 0.75rem;">
                                View
                            </a>
                        </div>
                        <h6 class="mb-2 fw-bold">
                            <a href="{{ $ticketRoute }}" class="text-decoration-none"
                                style="color: var(--text-high);">#{{ $ticket->id }} - {{ $ticket->subject }}</a>
                        </h6>
                        <p class="text-low small mb-3 flex-grow-1">
                            {{ Str::limit(strip_tags($ticket->body), 80) }}</p>

                        <div class="mt-auto pt-3 d-flex justify-content-between align-items-center"
                            style="border-top: 1px solid var(--border-subtle);">
                            <span class="small text-low">
                                <i class="fas fa-user me-1"></i>
                                {{ $ticket->user->name ?? ($ticket->email_source ?? 'Unknown') }}
                            </span>
                            <span class="small text-low">{{ $ticket->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($query && $tickets->count() === 0)
        <div class="data-grid-wrapper text-center py-5 mb-5" style="border: 1px solid var(--border-main);">
            <i class="fas fa-ticket-alt fa-2x mb-3 d-block" style="color: var(--text-low); opacity: 0.4;"></i>
            <p class="mb-0 text-low">No tickets found matching "{{ $query }}".</p>
        </div>
    @endif

    @if (Auth::user()->hasPermission('users.view') && $users->count() > 0)
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-users" style="color: var(--accent);"></i>
            <h5 class="fw-bold mb-0" style="color: var(--text-high);">Users <span class="badge-premium ms-1"
                    style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent);">{{ $users->count() }}</span>
            </h5>
        </div>
        <div class="row g-4">
            @foreach ($users as $user)
                <div class="col-md-6 col-lg-3">
                    <div class="data-grid-wrapper text-center p-4" style="border: 1px solid var(--border-main);">
                        <div class="avatar-premium mx-auto mb-3"
                            style="width: 56px; height: 56px; font-size: 1.3rem; border: 2px solid var(--border-main);">
                            @if ($user->profile_image)
                                <img alt="team-tasker" src="{{ asset('storage/' . $user->profile_image) }}" alt=""
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                {{ substr($user->name, 0, 1) }}
                            @endif
                        </div>
                        <h6 class="mb-1 fw-bold" style="color: var(--text-high);">{{ $user->name }}</h6>
                        <p class="text-low small mb-3">{{ $user->email }}</p>
                        <span class="badge-premium"
                            style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); border: 1px solid rgba(var(--primary-rgb), 0.2);">
                            {{ $user->role->name ?? 'No Role' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif(Auth::user()->hasPermission('users.view') && $query)
        <div class="data-grid-wrapper text-center py-5" style="border: 1px solid var(--border-main);">
            <i class="fas fa-users fa-2x mb-3 d-block" style="color: var(--text-low); opacity: 0.4;"></i>
            <p class="mb-0 text-low">No users found matching "{{ $query }}".</p>
        </div>
    @endif

</x-dynamic-component>

