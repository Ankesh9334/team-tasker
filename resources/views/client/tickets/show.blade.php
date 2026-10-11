<x-client title="Ticket #{{ $ticket->id }}">
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

    <div class="glass-card mb-4" style="border: 1px solid var(--border-main); position: relative; overflow: hidden;">
        <div class="position-absolute top-0 start-0 h-100 bg-primary opacity-10" style="width: 4px;"></div>
        <div class="mail-container"
            style="background: var(--bg-input); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--border-subtle);">
            <div class="mail-header p-4 border-bottom border-subtle"
                style="background: rgba(var(--primary-rgb), 0.02);">
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <div class="avatar-premium"
                            style="width: 44px; height: 44px; border: 2px solid var(--border-main);">
                            @if ($ticket->user && $ticket->user->profile_image)
                                <img alt="team-tasker" src="{{ asset('storage/' . $ticket->user->profile_image) }}" alt="Avatar">
                            @else
                                <div class="d-flex align-items-center justify-content-center w-100 h-100text-white"
                                    style="background: var(--primary); color: white; font-weight: 700;">
                                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0" style="color: var(--text-high);">Me</h6>
                                <div class="text-low" style="font-size: 0.75rem;">&lt;{{ Auth::user()->email }}&gt;
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-low extra-small mb-1">
                                    {{ $ticket->created_at->format('M d, Y • H:i') }}</div>
                                <span class="badge-premium py-0 px-2"
                                    style="background: var(--bg-surface); font-size: 0.6rem; color: var(--text-low); border: 1px solid var(--border-subtle);">Original
                                    Ticket</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mail-meta d-flex flex-column gap-1 mt-3 pt-3 border-top border-subtle"
                    style="font-size: 0.8rem;">
                    <div class="d-flex align-items-center">
                        <span class="text-low fw-medium" style="width: 60px;">Subject:</span>
                        <span class="text-high fw-bold">{{ $ticket->subject }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="text-low fw-medium" style="width: 60px;">To:</span>
                        <span class="text-medium">{{ config('app.name') }} Support
                            &lt;{{ config('mail.from.address') }}&gt;</span>
                    </div>
                </div>
            </div>
            <div class="mail-body p-4"
                style="line-height: 1.7; color: var(--text-medium); font-size: 0.9rem; background: var(--bg-surface);">
                {!! $ticket->body !!}
            </div>
        </div>
    </div>

    <!-- Replies -->
    @foreach ($ticket->replies as $reply)
        <div class="glass-card mb-4 {{ $reply->type == 'internal' ? 'border-primary' : '' }}"
            style="{{ $reply->type == 'internal' ? 'background: rgba(var(--primary-rgb), 0.05); border: 1px solid rgba(var(--primary-rgb), 0.2);' : 'border: 1px solid var(--border-subtle);' }} position: relative; overflow: hidden;">

            @if ($reply->email_source)
                <div class="mail-container"
                    style="background: var(--bg-input); border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-subtle);">
                    <div class="mail-header p-3 border-bottom border-subtle"
                        style="background: rgba(var(--primary-rgb), 0.01);">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar-premium"
                                    style="width: 36px; height: 36px; border: 1px solid var(--border-main);">
                                    @if ($reply->user && $reply->user->profile_image)
                                        <img alt="team-tasker" src="{{ asset('storage/' . $reply->user->profile_image) }}"
                                            alt="Avatar">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white"
                                            style="background: var(--bg-input); color: var(--text-high); font-weight: 600; font-size: 0.8rem;">
                                            {{ substr($reply->user ? $reply->user->name : $reply->email_source, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold mb-0" style="color: var(--text-high); font-size: 0.85rem;">
                                            {{ $reply->user ? $reply->user->name : 'External Reply' }}</div>
                                        <div class="text-low" style="font-size: 0.75rem;">
                                            &lt;{{ $reply->email_source }}&gt;</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-low extra-small" style="font-size: 0.7rem;">
                                            {{ $reply->created_at->format('M d, Y • H:i') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mail-body p-3"
                        style="line-height: 1.6; color: var(--text-medium); font-size: 0.85rem; background: var(--bg-surface);">
                        {!! $reply->body !!}
                    </div>
                </div>
            @else
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
            @endif
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
