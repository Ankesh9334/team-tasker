<x-admin title="Our Clients">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Our Clients</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage and view your Clients.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.export', request()->all()) }}" class="btn-premium btn-premium-secondary px-3 py-2" title="Export CSV">
                <i class="fas fa-file-export me-1"></i> Export
            </a>
            @if (Auth::user()->hasPermission('clients.create'))
                <a href="{{ route('admin.clients.imports.index') }}" class="btn-premium btn-premium-secondary px-3 py-2">
                    <i class="fas fa-file-import me-1"></i> Import
                </a>
                <a href="{{ route('admin.clients.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> Create Client
                </a>
            @endif
        </div>
    </div>

    {{-- Stats Row --}}
    @if(isset($stats))
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 14px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-main);">
                <div class="text-low" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Total</div>
                <div class="text-high fw-bold" style="font-size: 1.3rem;">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 14px; background: rgba(16,185,129,0.05); border-radius: var(--radius-md); border: 1px solid rgba(16,185,129,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #10b981;">Active</div>
                <div class="fw-bold" style="font-size: 1.3rem; color: #10b981;">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 14px; background: rgba(245,158,11,0.05); border-radius: var(--radius-md); border: 1px solid rgba(245,158,11,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #f59e0b;">Prospects</div>
                <div class="fw-bold" style="font-size: 1.3rem; color: #f59e0b;">{{ $stats['prospects'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 14px; background: rgba(59,130,246,0.05); border-radius: var(--radius-md); border: 1px solid rgba(59,130,246,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #3b82f6;">Emails Sent</div>
                <div class="fw-bold" style="font-size: 1.3rem; color: #3b82f6;">{{ $stats['emails_sent'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div style="padding: 14px; background: rgba(168,85,247,0.05); border-radius: var(--radius-md); border: 1px solid rgba(168,85,247,0.2);">
                <div style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #a855f7;">Open Tickets</div>
                <div class="fw-bold" style="font-size: 1.3rem; color: #a855f7;">{{ $stats['open_tickets'] }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.clients.index') }}" method="GET" id="searchForm">
                    <input type="text" name="search" placeholder="Search clients..." value="{{ request('search') }}" onchange="document.getElementById('searchForm').submit()">
                    @if(request('client_type'))<input type="hidden" name="client_type" value="{{ request('client_type') }}">@endif
                    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                </form>
            </div>
            <div class="data-grid-results">{{ $clients->total() }} Results</div>
            <div class="data-grid-actions">
                {{ $clients->links() }}
            </div>
        </div>
        <div class="data-grid-bulk-actions" id="bulkActionBar">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span id="selectedCount">0</span> Items Selected</span>
                <a href="#" id="bulkEmailBtn" class="btn-bulk-outline" onclick="sendBulkEmail()">
                    <i class="fas fa-paper-plane"></i> Send Email
                </a>
                <button type="button" class="btn-bulk-danger" onclick="submitBulkAction('delete')">
                    <i class="fas fa-trash-alt"></i> Bulk Delete
                </button>
            </div>
            <button type="button" class="btn-deselect-all" onclick="document.getElementById('selectAll').click()">
                Deselect All
            </button>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>CLIENT NAME</th>
                        <th>COMPANY</th>
                        <th>EMAIL</th>
                        <th>TYPE</th>
                        <th>STATUS</th>
                        <th>TICKETS</th>
                        <th>EMAILS</th>
                        <th>CREATED AT</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $client->id }}" class="data-grid-checkbox item-checkbox"></td>
                            <td class="text-low" style="color: #64748b !important;">#{{ $client->id }}</td>
                            <td>
                                <a href="{{ route('admin.clients.show', $client->id) }}" class="text-high fw-medium text-decoration-none" style="transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color=''">
                                    {{ $client->name }}
                                </a>
                            </td>
                            <td class="text-high">{{ $client->company_name ?? $client->company ?? '—' }}</td>
                            <td class="text-low" style="font-size: 0.8rem;">{{ $client->email }}</td>
                            <td>
                                @if($client->client_type)
                                    <span style="background: rgba(var(--primary-rgb), 0.08); color: var(--primary); font-size: 0.6rem; font-weight: 600; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">{{ $client->client_type }}</span>
                                @else
                                    <span class="text-low">—</span>
                                @endif
                            </td>
                            <td>
                                @if($client->status === 'active')
                                    <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">ACTIVE</span>
                                @elseif($client->status === 'prospect')
                                    <span style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PROSPECT</span>
                                @else
                                    <span style="background: rgba(100,116,139,0.1); color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-high">{{ $client->tickets_count ?? 0 }}</td>
                            <td class="text-high">{{ $client->emails_count ?? 0 }}</td>
                            <td class="text-high" style="font-size: 0.8rem;">{{ $client->created_at->format('d M Y') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.clients.show', $client->id) }}" class="action-link" title="View Profile"><i class="fas fa-eye"></i></a>
                                @if (Auth::user()->hasPermission('clients.edit'))
                                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="action-link" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                @endif
                                <a href="{{ route('admin.clients.emails.compose', ['client_ids' => $client->id]) }}" class="action-link" title="Send Email"><i class="fas fa-envelope"></i></a>
                                @if (Auth::user()->hasPermission('clients.delete'))
                                    <button onclick="if(confirm('Delete this client?')) document.getElementById('del-{{ $client->id }}').submit()" class="action-link delete border-0 bg-transparent" title="Delete"><i class="fas fa-trash"></i></button>
                                    <form id="del-{{ $client->id }}" action="{{ route('admin.clients.delete', $client->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center py-5 text-medium">No clients found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Filter Slideover -->
    <div class="filter-slideover" id="filterSlideover">
        <form action="{{ route('admin.clients.index') }}" method="GET" class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('filterSlideover').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH QUERY</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-premium-control w-100" placeholder="Search anything...">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CLIENT TYPE</label>
                    <x-select name="client_type" placeholder="All Types" :selected="request('client_type')">
                        <option value="" class="bg-dark">All Types</option>
                        @foreach($clientTypes as $type)
                            <option value="{{ $type }}" {{ request('client_type') === $type ? 'selected' : '' }} class="bg-dark">{{ $type }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <x-select name="status" placeholder="All Status" :selected="request('status')">
                        <option value="" class="bg-dark">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }} class="bg-dark">Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }} class="bg-dark">Inactive</option>
                        <option value="prospect" {{ request('status') === 'prospect' ? 'selected' : '' }} class="bg-dark">Prospect</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATED DATE</label>
                    <input type="date" name="created_at" value="{{ request('created_at') }}" class="form-premium-control w-100">
                </div>
            </div>
            <div class="filter-slideover-footer">
                <a href="{{ route('admin.clients.index') }}" class="btn-premium btn-premium-secondary w-50 justify-content-center">Reset</a>
                <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center" style="background: #0ea5e9;">Apply Filters</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const bulkActionBar = document.getElementById('bulkActionBar');
            const selectedCount = document.getElementById('selectedCount');

            function updateBulkBar() {
                const checked = document.querySelectorAll('.item-checkbox:checked').length;
                selectedCount.textContent = checked;
                if (checked > 0) {
                    bulkActionBar.classList.add('active');
                } else {
                    bulkActionBar.classList.remove('active');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });
        });

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
        }

        function sendBulkEmail() {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            window.location.href = `{{ route('admin.clients.emails.compose') }}?client_ids=${ids.join(',')}`;
        }

        function submitBulkAction(action) {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            if (action === 'delete') {
                if (!confirm(`Delete ${ids.length} selected client(s)?`)) return;
                alert('Bulk delete not yet implemented for clients.');
            }
        }
    </script>
</x-admin>
