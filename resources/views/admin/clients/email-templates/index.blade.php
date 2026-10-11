<x-admin title="Email Templates">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Email Templates</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage reusable email templates for client communication.</p>
        </div>
        <a href="{{ route('admin.clients.email-templates.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Create Template
        </a>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.clients.email-templates.index') }}" method="GET" id="searchForm">
                    <input type="text" name="search" placeholder="Search templates..." value="{{ request('search') }}" onchange="document.getElementById('searchForm').submit()">
                </form>
            </div>
            <div class="data-grid-results">{{ $templates->total() }} Results</div>
            <div class="data-grid-actions">{{ $templates->links() }}</div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>TEMPLATE NAME</th>
                        <th>SUBJECT</th>
                        <th>STATUS</th>
                        <th>CREATED BY</th>
                        <th>CREATED AT</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td class="text-low" style="color: #64748b !important;">#{{ $template->id }}</td>
                            <td class="text-high fw-medium">
                                <i class="fas fa-envelope-open-text me-1" style="color: var(--primary);"></i>
                                {{ $template->name }}
                            </td>
                            <td class="text-high">{{ \Illuminate\Support\Str::limit($template->subject, 50) }}</td>
                            <td>
                                @if($template->status === 'active')
                                    <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">ACTIVE</span>
                                @else
                                    <span style="background: rgba(100,116,139,0.1); color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-high">{{ $template->creator?->name ?? '—' }}</td>
                            <td class="text-high">{{ $template->created_at->format('d M Y') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.clients.email-templates.edit', $template->id) }}" class="action-link" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <button onclick="if(confirm('Delete this template?')) document.getElementById('del-tpl-{{ $template->id }}').submit()" class="action-link delete border-0 bg-transparent" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="del-tpl-{{ $template->id }}" action="{{ route('admin.clients.email-templates.delete', $template->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-medium">No email templates found. <a href="{{ route('admin.clients.email-templates.create') }}" class="text-primary">Create one now</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Available Variables Info --}}
    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <h6 class="text-high fw-semibold mb-3"><i class="fas fa-code me-2 text-low"></i>Available Template Variables</h6>
        <div class="row g-2">
            @foreach([
                '{{client_name}}' => 'Client\'s full name',
                '{{company_name}}' => 'Company name',
                '{{client_email}}' => 'Client email address',
                '{{client_type}}' => 'Client type (ERP, Ecommerce...)',
                '{{client_status}}' => 'Client status',
                '{{current_date}}' => 'Today\'s date',
                '{{sender_name}}' => 'Your name (sender)',
            ] as $var => $desc)
                <div class="col-md-4 col-lg-3">
                    <div style="padding: 8px 12px; background: var(--bg-input); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                        <code style="color: var(--primary); font-size: 0.75rem;">{{ $var }}</code>
                        <div class="text-low" style="font-size: 0.7rem;">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-admin>
