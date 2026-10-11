<x-admin title="Email Queue & History">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Email Queue</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Monitor outgoing email delivery status and history.</p>
        </div>
        <a href="{{ route('admin.clients.emails.compose') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
            <i class="fas fa-paper-plane me-1"></i> Compose New
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 16px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-main);">
                <div class="text-low" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Total</div>
                <div class="text-high fw-bold" style="font-size: 1.4rem;">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 16px; background: rgba(245,158,11,0.05); border-radius: var(--radius-md); border: 1px solid rgba(245,158,11,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #f59e0b;">Queued</div>
                <div class="fw-bold" style="font-size: 1.4rem; color: #f59e0b;">{{ $stats['queued'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 16px; background: rgba(59,130,246,0.05); border-radius: var(--radius-md); border: 1px solid rgba(59,130,246,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #3b82f6;">Sending</div>
                <div class="fw-bold" style="font-size: 1.4rem; color: #3b82f6;">{{ $stats['sending'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 16px; background: rgba(16,185,129,0.05); border-radius: var(--radius-md); border: 1px solid rgba(16,185,129,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #10b981;">Sent</div>
                <div class="fw-bold" style="font-size: 1.4rem; color: #10b981;">{{ $stats['sent'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 16px; background: rgba(239,68,68,0.05); border-radius: var(--radius-md); border: 1px solid rgba(239,68,68,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #ef4444;">Failed</div>
                <div class="fw-bold" style="font-size: 1.4rem; color: #ef4444;">{{ $stats['failed'] }}</div>
            </div>
        </div>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.clients.emails.queue') }}" method="GET" id="searchForm" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" placeholder="Search emails..." value="{{ request('search') }}" onchange="document.getElementById('searchForm').submit()">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                </form>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.clients.emails.queue') }}" class="btn-premium btn-premium-secondary px-3 py-1 {{ !request('status') ? 'active' : '' }}" style="font-size: 0.75rem;">All</a>
                    <a href="{{ route('admin.clients.emails.queue', ['status' => 'queued']) }}" class="btn-premium btn-premium-secondary px-3 py-1 {{ request('status') === 'queued' ? 'active' : '' }}" style="font-size: 0.75rem;">Queued</a>
                    <a href="{{ route('admin.clients.emails.queue', ['status' => 'sent']) }}" class="btn-premium btn-premium-secondary px-3 py-1 {{ request('status') === 'sent' ? 'active' : '' }}" style="font-size: 0.75rem;">Sent</a>
                    <a href="{{ route('admin.clients.emails.queue', ['status' => 'failed']) }}" class="btn-premium btn-premium-secondary px-3 py-1 {{ request('status') === 'failed' ? 'active' : '' }}" style="font-size: 0.75rem;">Failed</a>
                </div>
            </div>
            <div class="data-grid-results">{{ $emails->total() }} Results</div>
            <div class="data-grid-actions">{{ $emails->links() }}</div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>RECIPIENT</th>
                        <th>SUBJECT</th>
                        <th>STATUS</th>
                        <th>CAMPAIGN</th>
                        <th>SENT BY</th>
                        <th>DATE</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emails as $email)
                        <tr>
                            <td class="text-low" style="color: #64748b !important;">#{{ $email->id }}</td>
                            <td>
                                <div class="text-high fw-medium" style="font-size: 0.85rem;">{{ $email->to_name }}</div>
                                <div class="text-low" style="font-size: 0.7rem;">{{ $email->to_email }}</div>
                            </td>
                            <td class="text-high" style="max-width: 250px;">{{ \Illuminate\Support\Str::limit($email->subject, 45) }}</td>
                            <td>
                                @if($email->status === 'sent')
                                    <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">SENT</span>
                                @elseif($email->status === 'queued')
                                    <span style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">QUEUED</span>
                                @elseif($email->status === 'sending')
                                    <span style="background: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">SENDING</span>
                                @else
                                    <span style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">FAILED</span>
                                @endif
                            </td>
                            <td class="text-low" style="font-size: 0.8rem;">{{ \Illuminate\Support\Str::limit($email->campaign_name ?? '—', 20) }}</td>
                            <td class="text-high">{{ $email->sender?->name ?? '—' }}</td>
                            <td class="text-high" style="font-size: 0.8rem;">{{ $email->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.clients.emails.show', $email->id) }}" class="action-link" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($email->status === 'failed')
                                    <form action="{{ route('admin.clients.emails.retry', $email->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="action-link border-0 bg-transparent" title="Retry" onclick="return confirm('Retry this email?')">
                                            <i class="fas fa-redo" style="color: #f59e0b;"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 text-medium">No emails found. <a href="{{ route('admin.clients.emails.compose') }}" class="text-primary">Compose one now</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin>
