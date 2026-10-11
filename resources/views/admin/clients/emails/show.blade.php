<x-admin title="Email Details">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Email Details</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Viewing email #{{ $email->id }} to {{ $email->to_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.emails.queue') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Queue
            </a>
            @if($email->status === 'failed')
                <form action="{{ route('admin.clients.emails.retry', $email->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" onclick="return confirm('Retry?')">
                        <i class="fas fa-redo me-1"></i> Retry
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Email Content --}}
        <div class="col-lg-8">
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                {{-- Email Header --}}
                <div class="mb-4 pb-3" style="border-bottom: 1px solid var(--border-main);">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="text-high fw-semibold mb-1">{{ $email->subject }}</h4>
                            <div class="text-low" style="font-size: 0.8rem;">
                                <strong>From:</strong> {{ $email->from_name }} &lt;{{ $email->from_email }}&gt;
                            </div>
                            <div class="text-low" style="font-size: 0.8rem;">
                                <strong>To:</strong> {{ $email->to_name }} &lt;{{ $email->to_email }}&gt;
                            </div>
                            <div class="text-low" style="font-size: 0.8rem;">
                                <strong>Date:</strong> {{ $email->created_at->format('d M Y H:i:s') }}
                            </div>
                        </div>
                        <div>
                            @if($email->status === 'sent')
                                <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.7rem; font-weight: 700; padding: 6px 14px; border-radius: 6px; text-transform: uppercase;">SENT</span>
                            @elseif($email->status === 'queued')
                                <span style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.7rem; font-weight: 700; padding: 6px 14px; border-radius: 6px; text-transform: uppercase;">QUEUED</span>
                            @elseif($email->status === 'sending')
                                <span style="background: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.7rem; font-weight: 700; padding: 6px 14px; border-radius: 6px; text-transform: uppercase;">SENDING</span>
                            @else
                                <span style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.7rem; font-weight: 700; padding: 6px 14px; border-radius: 6px; text-transform: uppercase;">FAILED</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Email Body --}}
                <div class="text-high" style="line-height: 1.7; font-size: 0.9rem;">
                    {!! $email->body !!}
                </div>

                {{-- Attachments --}}
                @if(!empty($email->attachments) && is_array($email->attachments))
                    <div class="mt-4 pt-3" style="border-top: 1px solid var(--border-main);">
                        <h6 class="text-high fw-semibold mb-2"><i class="fas fa-paperclip me-1 text-low"></i> Attachments</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($email->attachments as $attachment)
                                <div style="padding: 8px 14px; background: var(--bg-input); border-radius: var(--radius-sm); border: 1px solid var(--border-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-file text-low"></i>
                                    <span class="text-high" style="font-size: 0.8rem;">{{ $attachment['name'] ?? 'Attachment' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Meta Sidebar --}}
        <div class="col-lg-4">
            {{-- Client Info --}}
            @if($email->client)
                <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                    <h6 class="text-high fw-semibold mb-3"><i class="fas fa-user-tie me-2 text-low"></i>Client</h6>
                    <div class="text-high fw-medium" style="font-size: 0.9rem;">{{ $email->client->name }}</div>
                    <div class="text-low" style="font-size: 0.8rem;">{{ $email->client->company_name ?? $email->client->company }}</div>
                    <div class="text-low" style="font-size: 0.8rem;">{{ $email->client->email }}</div>
                    <div class="mt-2">
                        <a href="{{ route('admin.clients.show', $email->client->id) }}" class="text-primary" style="font-size: 0.8rem;">View Profile →</a>
                    </div>
                </div>
            @endif

            {{-- Delivery Details --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-truck me-2 text-low"></i>Delivery Details</h6>
                <div style="font-size: 0.8rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low">Status</span>
                        <span class="text-high fw-medium">{{ ucfirst($email->status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low">Campaign</span>
                        <span class="text-high">{{ $email->campaign_name ?? '—' }}</span>
                    </div>
                    @if($email->template)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-low">Template</span>
                            <span class="text-high">{{ $email->template->name }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low">Sent By</span>
                        <span class="text-high">{{ $email->sender?->name ?? '—' }}</span>
                    </div>
                    @if($email->sent_at)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-low">Sent At</span>
                            <span class="text-high">{{ \Carbon\Carbon::parse($email->sent_at)->format('d M Y H:i') }}</span>
                        </div>
                    @endif
                    @if($email->message_id)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-low">Message ID</span>
                            <span class="text-low" style="font-size: 0.7rem; word-break: break-all;">{{ \Illuminate\Support\Str::limit($email->message_id, 30) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Failure Info --}}
            @if($email->status === 'failed' && $email->failure_reason)
                <div class="data-grid-wrapper p-4 mb-4" style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: var(--radius-lg);">
                    <h6 class="fw-semibold mb-2" style="color: #ef4444;"><i class="fas fa-exclamation-triangle me-1"></i> Failure Reason</h6>
                    <div class="text-high" style="font-size: 0.8rem; word-break: break-word;">{{ $email->failure_reason }}</div>
                    @if($email->failed_at)
                        <div class="text-low mt-2" style="font-size: 0.7rem;">Failed at: {{ \Carbon\Carbon::parse($email->failed_at)->format('d M Y H:i:s') }}</div>
                    @endif
                </div>
            @endif

            {{-- Linked Ticket --}}
            @if($email->ticket)
                <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                    <h6 class="text-high fw-semibold mb-3"><i class="fas fa-ticket-alt me-2 text-low"></i>Linked Ticket</h6>
                    <div class="text-high fw-medium">{{ $email->ticket->subject }}</div>
                    <div class="text-low" style="font-size: 0.8rem;">Ticket #{{ $email->ticket->ticket_number ?? $email->ticket->id }}</div>
                    <a href="{{ route('admin.tickets.show', $email->ticket->id) }}" class="text-primary" style="font-size: 0.8rem;">View Ticket →</a>
                </div>
            @endif
        </div>
    </div>
</x-admin>
