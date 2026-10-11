<x-client title="Task #{{ $task->id }}">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('client.dashboard') }}" class="btn-premium btn-premium-secondary btn-sm mb-2 px-3 py-1"
                style="font-size: 0.8rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h2 class="h4 fw-bold">
                <span class="text-low">#{{ $task->id }}</span> - {{ $task->title }}
            </h2>
        </div>
        <div class="d-flex gap-2">
            @if ($task->priority)
                @php
                    $pColor = match ($task->priority) {
                        'Critical' => 'var(--danger)',
                        'High' => 'var(--accent)',
                        default => 'var(--text-medium)',
                    };
                    $pBg = match ($task->priority) {
                        'Critical' => 'rgba(var(--danger-rgb), 0.1)',
                        'High' => 'rgba(var(--accent-rgb), 0.1)',
                        default => 'var(--bg-input)',
                    };
                @endphp
                <span class="badge-premium"
                    style="background: {{ $pBg }}; color: {{ $pColor }}; border: 1px solid var(--border-main);">
                    <i class="fas fa-flag me-1"></i> {{ $task->priority }}
                </span>
            @endif
            <span class="badge-premium"
                style="background: var(--bg-surface); color: var(--text-high); border: 1px solid var(--border-main);">
                {{ $task->status->name ?? 'Pending' }}
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Task Progress -->
            <div class="glass-card mb-4" style="border: 1px solid var(--border-main);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="heading-label mb-0">Completion Progress</h6>
                    <span class="fw-bold" style="color: var(--primary);">{{ $task->progress }}%</span>
                </div>
                <div class="progress"
                    style="height: 8px; background: var(--bg-input); border-radius: var(--radius-full); overflow: hidden;">
                    <div class="progress-bar" role="progressbar"
                        style="width: {{ $task->progress }}%; background: var(--primary); transition: width 1s ease-in-out;"
                        aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <!-- Task Description -->
            <div class="glass-card mb-4" style="border: 1px solid var(--border-subtle);">
                <h6 class="heading-label mb-3">Task Description</h6>
                <div class="lh-base" style="color: var(--text-medium);">
                    {!! $task->description !!}
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4">
                <ul class="nav nav-pills glass-pills gap-2" id="taskTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-outfit" id="activity-tab" data-bs-toggle="tab"
                            data-bs-target="#activity" type="button" role="tab">
                            <i class="fas fa-stream me-2"></i>Activity
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-outfit" id="files-tab" data-bs-toggle="tab" data-bs-target="#files"
                            type="button" role="tab">
                            <i class="fas fa-paperclip me-2"></i>Files ({{ $task->attachments->count() }})
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="taskTabsContent">
                <!-- Activity Feed Tab -->
                <div class="tab-pane fade show active" id="activity" role="tabpanel">
                    <div class="activity-timeline">
                        @forelse($task->logs as $log)
                            <div class="d-flex gap-4 mb-4 timeline-item">
                                <div class="position-relative">
                                    <div class="avatar-premium"
                                        style="width: 36px; height: 36px; font-size: 0.85rem; border: 2px solid {{ $log->client_id == Auth::guard('client')->id() ? 'var(--primary)' : 'var(--accent)' }};">
                                        {{ substr($log->user?->name ?? $log->client?->name ?? 'System', 0, 1) }}
                                    </div>
                                    @if (!$loop->last)
                                        <div class="timeline-line"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1 glass-card p-3" style="border: 1px solid var(--border-subtle);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold"
                                                style="color: var(--text-high);">{{ $log->user?->name ?? $log->client?->name ?? 'System' }}</span>
                                            @if ($log->user_id)
                                                <span class="badge-premium"
                                                    style="font-size: 0.65rem; background: rgba(var(--primary-rgb), 0.08); color: var(--primary);">
                                                    <i class="fas fa-headset me-1"></i> Support Agent
                                                </span>
                                            @elseif ($log->client_id == Auth::guard('client')->id())
                                                <span class="badge-premium"
                                                    style="font-size: 0.65rem; background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                                                    <i class="fas fa-user me-1"></i> You
                                                </span>
                                            @elseif ($log->client_id)
                                                <span class="badge-premium"
                                                    style="font-size: 0.65rem; background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                                                    <i class="fas fa-user me-1"></i> Client
                                                </span>
                                            @else
                                                <span class="badge-premium"
                                                    style="font-size: 0.65rem; background: rgba(var(--text-medium-rgb), 0.1); color: var(--text-medium);">
                                                    <i class="fas fa-robot me-1"></i> System
                                                </span>
                                            @endif
                                        </div>
                                        <span
                                            style="font-size: 0.75rem; color: var(--text-low);">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="small" style="color: var(--text-medium); line-height: 1.6;">
                                        {!! $log->note !!}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-low small">
                                <i class="fas fa-stream fa-2x mb-2 d-block" style="opacity: 0.2;"></i>
                                No activity logged yet.
                            </div>
                        @endforelse
                    </div>

                    <!-- Reply Form -->
                    <div class="glass-card mt-4" style="border: 1px solid var(--border-main);">
                        <h6 class="fw-bold mb-4" style="color: var(--text-high);"><i class="fas fa-reply me-2"
                                style="color: var(--primary);"></i>Send a Message</h6>
                        <form action="{{ route('client.tasks.reply', $task->id) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <x-textarea id="reply-editor" name="note" class="form-premium-control" rows="4"
                                    placeholder="Type your message here..." texteditor="true"></x-textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn-premium btn-premium-primary">
                                    <i class="fas fa-paper-plane me-2"></i> Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Files Tab -->
                <div class="tab-pane fade" id="files" role="tabpanel">
                    <div class="row g-3">
                        @forelse($task->attachments as $attachment)
                            <div class="col-md-6">
                                <div class="glass-card p-3 h-100">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stat-icon-premium icon-primary-premium"
                                            style="width: 40px; height: 40px; font-size: 1rem; margin: 0;">
                                            <i class="fas fa-file"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="small fw-medium text-truncate"
                                                style="color: var(--text-high);"
                                                title="{{ $attachment->file_name }}">
                                                {{ $attachment->file_name }}
                                            </div>
                                            <div class="extra-small text-low">
                                                {{ round($attachment->file_size / 1024, 1) }} KB •
                                                {{ $attachment->user?->name ?? $attachment->client?->name ?? 'System' }}
                                            </div>
                                        </div>
                                        <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                            class="btn-premium btn-premium-secondary p-0 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px; border-radius: 50%; color: var(--primary);">
                                            <i class="fas fa-download" style="font-size: 0.8rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-low small glass-card">
                                <i class="fas fa-folder-open fa-3x mb-3 d-block"
                                    style="opacity: 0.2; color: var(--text-low);"></i>
                                <p class="mb-0">No attachments uploaded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card sticky-top" style="top: var(--space-4); border: 1px solid var(--border-main);">
                <h6 class="heading-label mb-4 pb-2 border-bottom border-main">Task Details</h6>

                <div class="mb-4">
                    <label class="heading-label mb-1">Created At</label>
                    <div class="small fw-medium" style="color: var(--text-medium);">
                        <i class="far fa-calendar-alt me-2"
                            style="color: var(--primary);"></i>{{ $task->created_at->format('M d, Y') }}
                    </div>
                </div>

                <div class="mb-4">
                    <label class="heading-label mb-1">Deadline</label>
                    @if ($task->deadline)
                        <div class="small fw-bold"
                            style="color: {{ $task->deadline->isPast() ? 'var(--danger)' : 'var(--text-medium)' }};">
                            <i class="far fa-clock me-2"></i>{{ $task->deadline->format('M d, Y') }}
                        </div>
                    @else
                        <div class="text-low small italic">No deadline set</div>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="heading-label mb-1">Assigned Support</label>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        @if ($task->assignedTo)
                            <div class="avatar-premium" style="width: 40px; height: 40px;">
                                {{ substr($task->assignedTo->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="fw-bold small" style="color: var(--text-high);">
                                    {{ $task->assignedTo->name }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-low);">Support Engineer</div>
                            </div>
                        @else
                            <div class="avatar-premium" style="width: 40px; height: 40px; opacity: 0.5;">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <span class="text-low small italic">Awaiting assignment</span>
                        @endif
                    </div>
                </div>

                @if ($task->ticket_id)
                    <div class="pt-4 mt-4 border-top border-main">
                        <label class="heading-label mb-3">Context</label>
                        <a href="{{ route('client.tickets.show', $task->ticket_id) }}"
                            class="btn-premium btn-premium-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="fas fa-ticket-alt"></i> <span>View Original Ticket</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .timeline-item {
            position: relative;
        }

        .timeline-line {
            position: absolute;
            left: 50%;
            top: 40px;
            bottom: -20px;
            width: 2px;
            background: var(--border-subtle);
            transform: translateX(-50%);
        }

        .nav-pills.glass-pills {
            background: var(--bg-surface);
            padding: var(--space-1);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-main);
            display: inline-flex;
        }

        .nav-pills.glass-pills .nav-link {
            color: var(--text-medium);
            border-radius: var(--radius-sm);
            padding: var(--space-2) var(--space-4);
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition-base);
        }

        .nav-pills.glass-pills .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
        }

        .nav-pills.glass-pills .nav-link:not(.active):hover {
            background: var(--bg-input);
            color: var(--text-high);
        }
    </style>

</x-client>
