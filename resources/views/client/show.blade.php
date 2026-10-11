    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('client.dashboard') }}" class="btn-premium btn-premium-secondary btn-sm mb-2 px-3 py-1"
                style="font-size: 0.8rem;">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <h2 class="h4 fw-bold">
                <span class="text-low">#{{ $ticket->id }}</span> - {{ $ticket->subject }}
            </h2>
        </div>
        <div class="d-flex gap-2">
            <span class="badge-premium"
                style="background: {{ $ticket->status == 'open' ? 'rgba(var(--primary-rgb), 0.1)' : 'var(--bg-input)' }};
                       color: {{ $ticket->status == 'open' ? 'var(--primary)' : 'var(--text-medium)' }};
                       border: 1px solid {{ $ticket->status == 'open' ? 'rgba(var(--primary-rgb), 0.2)' : 'var(--border-main)' }};">
                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </span>
        </div>
    </div>

    <div class="glass-card mb-4" style="border: 1px solid var(--border-main);">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="avatar-premium" style="width: 48px; height: 48px;">
                @if ($ticket->user && $ticket->user->profile_image)
                    <img alt="team-tasker" src="{{ asset('storage/' . $ticket->user->profile_image) }}" alt="Avatar">
                @else
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                @endif
            </div>
            <div>
                <div class="heading-label mb-0" style="color: var(--text-high);">Me</div>
                <div style="font-size: 0.75rem; color: var(--text-low);">
                    {{ $ticket->created_at->format('M d, Y • H:i') }}
                </div>
            </div>
        </div>
        <div class="text-main" style="color: var(--text-medium); line-height: 1.6;">
            {!! $ticket->body !!}
        </div>
    </div>

    <!-- Replies -->
    @foreach ($ticket->replies as $reply)
        <div class="glass-card mb-4 {{ $reply->type == 'internal' ? 'border-primary' : '' }}"
            style="{{ $reply->type == 'internal' ? 'background: rgba(var(--primary-rgb), 0.05); border: 1px solid rgba(var(--primary-rgb), 0.2);' : 'border: 1px solid var(--border-subtle);' }}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-premium {{ $reply->type == 'internal' ? 'border-primary' : '' }}"
                        style="width: 40px; height: 40px;">
                        @if ($reply->user && $reply->user->profile_image)
                            <img alt="team-tasker" src="{{ asset('storage/' . $reply->user->profile_image) }}" alt="Avatar">
                        @else
                            {{ substr($reply->user ? $reply->user->name : 'S', 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <div class="fw-bold" style="color: var(--text-high);">
                            {{ $reply->user ? $reply->user->name : 'Support Team' }}
                            @if ($reply->type == 'internal')
                                <span class="badge-premium py-0 px-2 ms-2"
                                    style="font-size: 0.65rem; background: var(--bg-input);">Agent</span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-low);">
                            {{ $reply->created_at->format('M d, Y • H:i') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-main" style="color: var(--text-medium); line-height: 1.6;">
                {!! $reply->body !!}
            </div>
        </div>
    @endforeach

    <!-- Reply Form -->
    <div class="glass-card" style="border: 1px solid var(--border-main);">
        <h5 class="fw-bold mb-4" style="color: var(--text-high);">Post a Reply</h5>
        <form action="{{ route('client.tickets.reply', $ticket->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <x-textarea id="reply-editor" name="body" class="form-premium-control" rows="5"
                    texteditor="true"></x-textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn-premium btn-premium-primary">
                    <i class="fas fa-paper-plane me-2"></i> Send Reply
                </button>
            </div>
        </form>
    </div>

    </x-client>
