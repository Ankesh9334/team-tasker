<x-admin title="Client Profile — {{ $client->name }}">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">{{ $client->name }}</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                {{ $client->company_name ?? $client->company ?? '' }}
                @if($client->client_type) · {{ $client->client_type }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-pencil-alt me-1"></i> Edit
            </a>
            <a href="{{ route('admin.clients.emails.compose', ['client_ids' => $client->id]) }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-paper-plane me-1"></i> Send Email
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left: Client Info & Timeline --}}
        <div class="col-lg-8">
            {{-- Client Details Card --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</div>
                            <div class="text-high fw-medium">{{ $client->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Email</div>
                            <div class="text-high">{{ $client->email }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Phone</div>
                            <div class="text-high">{{ $client->phone ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Company</div>
                            <div class="text-high">{{ $client->company_name ?? $client->company ?? '—' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</div>
                            <div>
                                @if($client->status === 'active')
                                    <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">ACTIVE</span>
                                @elseif($client->status === 'prospect')
                                    <span style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PROSPECT</span>
                                @else
                                    <span style="background: rgba(100,116,139,0.1); color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">INACTIVE</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Website</div>
                            <div class="text-high">
                                @if($client->website)
                                    <a href="{{ $client->website }}" target="_blank" class="text-primary">{{ $client->website }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Communication Timeline --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h5 class="text-high fw-semibold mb-4"><i class="fas fa-stream me-2 text-low"></i>Communication Timeline</h5>

                @if($timeline->isEmpty())
                    <div class="text-center py-5 text-low">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No communication history yet.
                    </div>
                @else
                    <div style="position: relative; padding-left: 30px;">
                        {{-- Timeline line --}}
                        <div style="position: absolute; left: 12px; top: 0; bottom: 0; width: 2px; background: var(--border-main);"></div>

                        @foreach($timeline as $item)
                            <div class="mb-4" style="position: relative;">
                                {{-- Timeline dot --}}
                                <div style="position: absolute; left: -24px; top: 4px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--bg-surface);
                                    @if($item['type'] === 'sent_email') background: #3b82f6;
                                    @elseif($item['type'] === 'received_email') background: #10b981;
                                    @else background: #f59e0b;
                                    @endif
                                "></div>

                                <div style="padding: 12px 16px; background: var(--bg-input); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
                                                @if($item['type'] === 'sent_email') color: #3b82f6;
                                                @elseif($item['type'] === 'received_email') color: #10b981;
                                                @else color: #f59e0b;
                                                @endif
                                            ">
                                                @if($item['type'] === 'sent_email')
                                                    <i class="fas fa-paper-plane me-1"></i> Email Sent
                                                @elseif($item['type'] === 'received_email')
                                                    <i class="fas fa-reply me-1"></i> Reply Received
                                                @else
                                                    <i class="fas fa-ticket-alt me-1"></i> Ticket
                                                @endif
                                            </span>
                                        </div>
                                        <span class="text-low" style="font-size: 0.7rem;">{{ $item['date']->format('d M Y H:i') }}</span>
                                    </div>
                                    <div class="text-high fw-medium" style="font-size: 0.85rem;">{{ $item['title'] }}</div>
                                    @if($item['status'])
                                        <span class="text-low" style="font-size: 0.7rem;">Status: {{ ucfirst($item['status']) }}</span>
                                    @endif

                                    @if($item['type'] === 'sent_email' && isset($item['data']->id))
                                        <a href="{{ route('admin.clients.emails.show', $item['data']->id) }}" class="text-primary d-block mt-1" style="font-size: 0.75rem;">View email →</a>
                                    @elseif($item['type'] === 'ticket' && isset($item['data']->id))
                                        <a href="{{ route('admin.tickets.show', $item['data']->id) }}" class="text-primary d-block mt-1" style="font-size: 0.75rem;">View ticket →</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Quick Stats --}}
        <div class="col-lg-4">
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-chart-bar me-2 text-low"></i>Communication Stats</h6>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low" style="font-size: 0.8rem;">Emails Sent</span>
                        <span class="text-high fw-semibold" style="color: #3b82f6;">{{ $client->emails->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low" style="font-size: 0.8rem;">Replies Received</span>
                        <span class="text-high fw-semibold" style="color: #10b981;">{{ $client->inboundEmails->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-low" style="font-size: 0.8rem;">Tickets</span>
                        <span class="text-high fw-semibold" style="color: #f59e0b;">{{ $client->tickets->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-cog me-2 text-low"></i>Preferences</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-low" style="font-size: 0.8rem;">Email Opt-Out</span>
                    <span class="fw-semibold" style="font-size: 0.8rem; color: {{ $client->email_opt_out ? '#ef4444' : '#10b981' }};">
                        {{ $client->email_opt_out ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-low" style="font-size: 0.8rem;">Client Type</span>
                    <span class="text-high fw-medium" style="font-size: 0.8rem;">{{ $client->client_type ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-low" style="font-size: 0.8rem;">Member Since</span>
                    <span class="text-high" style="font-size: 0.8rem;">{{ $client->created_at->format('d M Y') }}</span>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-bolt me-2 text-low"></i>Quick Actions</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('admin.clients.emails.compose', ['client_ids' => $client->id]) }}" class="btn-premium btn-premium-secondary px-3 py-2 justify-content-center" style="font-size: 0.8rem;">
                        <i class="fas fa-paper-plane me-2"></i> Send Email
                    </a>
                    <a href="{{ route('admin.tickets.create') }}" class="btn-premium btn-premium-secondary px-3 py-2 justify-content-center" style="font-size: 0.8rem;">
                        <i class="fas fa-ticket-alt me-2"></i> Create Ticket
                    </a>
                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn-premium btn-premium-secondary px-3 py-2 justify-content-center" style="font-size: 0.8rem;">
                        <i class="fas fa-pencil-alt me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin>
