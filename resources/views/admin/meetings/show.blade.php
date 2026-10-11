<x-admin title="{{ $meeting->title }} | Meeting Details">
    <div class="top-bar-premium mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.meetings.index') }}"
                class="btn-premium btn-premium-secondary btn-sm p-0 d-inline-flex align-items-center justify-content-center"
                style="width: 36px; height: 36px; border-radius: 50%;">
                <i class="fas fa-arrow-left" style="font-size: 0.9rem;"></i>
            </a>
            <div>
                <h1 class="h3 fw-semibold mb-1 text-high">Meeting Details</h1>
                <p class="text-low mb-0" style="font-size: 0.9rem;">View meeting info, copy join link, and manage
                    participants.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(in_array($meeting->status, ['scheduled', 'active', 'ringing']))
                @can('join', $meeting)
                    <a href="{{ route('admin.meetings.join', $meeting->uuid) }}"
                        class="btn-premium btn-premium-primary py-2 px-4 shadow-sm">
                        <i class="fas fa-video me-2"></i> Join Meeting
                    </a>
                @endcan
            @endif
            <button class="btn-premium btn-premium-secondary py-2 px-3" onclick="copyJoinLink()">
                <i class="fas fa-copy me-1"></i> Copy Join Link
            </button>
            @if(in_array($meeting->status, ['scheduled', 'active', 'ringing']))
                @can('cancel', $meeting)
                    <form action="{{ route('admin.meetings.cancel', $meeting->uuid) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Cancel this meeting?');">
                        @csrf
                        <button type="submit" class="btn-premium py-2 px-3 shadow-sm"
                            style="background: rgba(var(--danger-rgb), 0.1); color: var(--danger); border: 1px solid rgba(var(--danger-rgb), 0.2);">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="data-grid-wrapper p-4 mb-4"
                style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h2 class="mb-0 fw-bold text-high">{{ $meeting->title }}</h2>
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
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge-premium" style="background: var(--bg-input); color: var(--text-high);">
                        {{ $meeting->mode === 'video' ? '📹 Video Call' : '📞 Audio Call' }}
                    </span>
                    <span class="badge-premium" style="background: var(--bg-input); color: var(--text-medium);">
                        {{ str_replace('_', ' ', ucfirst($meeting->type)) }}
                    </span>
                </div>

                <h5 class="fw-semibold text-high mb-2">Description</h5>
                <div class="text-low mb-4" style="line-height: 1.6;">
                    {!! nl2br(e($meeting->description ?? 'No description provided for this meeting.')) !!}
                </div>

                <hr style="border-color: var(--border-main);" class="my-4">

                <div class="row g-4">
                    <div class="col-md-6">
                        <span class="text-low d-block mb-1 small">CREATED BY</span>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-premium" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ substr($meeting->createdBy->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="fw-semibold text-high">{{ $meeting->createdBy->name ?? 'System' }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <span class="text-low d-block mb-1 small">SCHEDULED / CREATED AT</span>
                        <span class="fw-semibold text-high">
                            {{ ($meeting->scheduled_at ?? $meeting->created_at)->format('F d, Y - h:i A') }}
                        </span>
                    </div>

                    @if($meeting->project)
                        <div class="col-md-6">
                            <span class="text-low d-block mb-1 small">ASSOCIATED PROJECT</span>
                            <a href="{{ route('admin.projects.show', $meeting->project->id) }}"
                                class="fw-semibold text-warning text-decoration-none">
                                <i class="fas fa-folder me-1"></i> {{ $meeting->project->name }}
                            </a>
                        </div>
                    @endif

                    @if($meeting->task)
                        <div class="col-md-6">
                            <span class="text-low d-block mb-1 small">ASSOCIATED TASK</span>
                            <a href="{{ route('details', $meeting->task->id) }}"
                                class="fw-semibold text-info text-decoration-none">
                                <i class="fas fa-tasks me-1"></i> {{ $meeting->task->title }}
                            </a>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <span class="text-low d-block mb-1 small">CALL DURATION</span>
                        <span class="fw-semibold text-high">
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
                                —
                            @endif
                        </span>
                    </div>

                    <div class="col-md-6">
                        <span class="text-low d-block mb-1 small">ROOM NAME</span>
                        <code style="color: var(--primary);">{{ $meeting->room_name }}</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="data-grid-wrapper p-4 mb-4"
                style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h5 class="fw-semibold text-high mb-3">Participants ({{ $meeting->participants->count() }})</h5>
                <div class="d-flex flex-column gap-3">
                    @foreach($meeting->participants as $participant)
                        <div class="d-flex align-items-center justify-content-between p-2 rounded"
                            style="background: var(--bg-input); border: 1px solid var(--border-main);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-premium" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    {{ substr($participant->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-high small">{{ $participant->user->name ?? 'User' }}</div>
                                    <small class="text-low"
                                        style="font-size: 0.75rem;">{{ ucfirst($participant->role) }}</small>
                                </div>
                            </div>
                            <span class="badge-premium py-1 px-2"
                                style="font-size: 0.75rem; background: var(--bg-surface); color: var(--text-medium);">
                                {{ ucfirst($participant->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyJoinLink() {
            const input = document.getElementById('shareJoinUrlInput');
            if (input) {
                input.select();
                navigator.clipboard.writeText(input.value).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Join link copied to clipboard!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        }
    </script>
</x-admin>