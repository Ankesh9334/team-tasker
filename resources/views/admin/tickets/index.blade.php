<x-admin title="Support Tickets">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Support Tickets</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage user support and feature requests.</p>
        </div>
        @if (Auth::user()->hasPermission('tickets.create'))
            <a href="{{ route('admin.tickets.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Create Ticket
            </a>
        @endif
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.tickets.index') }}" method="GET" id="searchFormTickets">
                    <input type="text" name="search" placeholder="Search anything..." value="{{ request('search') }}" onchange="document.getElementById('searchFormTickets').submit()">
                    <!-- Preserve existing filters -->
                    @if(request('priority')) <input type="hidden" name="priority" value="{{ request('priority') }}"> @endif
                    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                    @if(request('created_at')) <input type="hidden" name="created_at" value="{{ request('created_at') }}"> @endif
                    @if(request('updated_at')) <input type="hidden" name="updated_at" value="{{ request('updated_at') }}"> @endif
                </form>
            </div>
            <div class="data-grid-results">{{ $tickets->total() }} Results</div>
            <div class="data-grid-actions">
                {{ $tickets->links() }}
            </div>
        </div>
        <div class="data-grid-bulk-actions" id="bulkActionBar">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span id="selectedCount">0</span> Items Selected</span>
                <button type="button" class="btn-bulk-danger" onclick="submitBulkAction('delete')">
                    <i class="fas fa-trash-alt"></i> Bulk Delete
                </button>
                <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('edit')">
                    <i class="fas fa-edit"></i> Bulk Edit
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
                        <th>ID <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>SUBJECT & EXCERPT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>REQUESTER <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>STATUS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>PRIORITY <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>ASSIGNED AGENT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th>CREATED AT <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $ticket->id }}" class="data-grid-checkbox item-checkbox"></td>
                            <td class="text-low" style="color: #64748b !important;">#{{ $ticket->id }}</td>
                            <td>
                                <div class="text-high fw-medium">{{ Str::limit($ticket->subject, 30) }}</div>
                                <div class="text-low" style="font-size: 0.75rem;">{{ Str::limit(strip_tags($ticket->body), 35) }}</div>
                            </td>
                            <td>
                                @if ($ticket->user || $ticket->client)
                                    <span class="text-high">{{ $ticket->user ? $ticket->user->name : ($ticket->client ? $ticket->client->name : 'Unknown') }}</span>
                                @else
                                    <span class="text-low italic">{{ Str::limit($ticket->email_source, 15) }}</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($ticket->status, ['resolved', 'closed']))
                                    <span class="badge-premium" style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                @elseif(in_array($ticket->status, ['open', 'in_progress']))
                                    <span class="badge-premium" style="background: rgba(14,165,233,0.1); color: #0ea5e9; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                @else
                                    <span class="badge-premium" style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->priority == 'urgent')
                                    <span class="badge-premium" style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ $ticket->priority }}</span>
                                @elseif($ticket->priority == 'high')
                                    <span class="badge-premium" style="background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ $ticket->priority }}</span>
                                @else
                                    <span class="badge-premium" style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">{{ $ticket->priority }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($ticket->assignedTo)
                                    <span class="text-high">{{ $ticket->assignedTo->name }}</span>
                                @else
                                    <span class="text-low italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-high">{{ $ticket->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="action-link"><i class="fas fa-pencil-alt"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5 text-medium">No tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Filter Slideover -->
    <div class="filter-slideover" id="filterSlideover">
        <form action="{{ route('admin.tickets.index') }}" method="GET" class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('filterSlideover').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH QUERY</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-premium-control bg-white text-dark border-main" placeholder="Search anything...">
                </div>
                @if(isset($allUsers) && $allUsers->isNotEmpty())
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-low">ASSIGNED TO / EMPLOYEE</label>
                        @php
                            $selectedAssigneeIds = is_array(request('assigned_to')) 
                                ? array_values(array_filter(array_map('strval', request('assigned_to')))) 
                                : (request('assigned_to') ? [(string) request('assigned_to')] : []);
                        @endphp
                        <x-multiselect 
                            id="filter_assigned_to" 
                            name="assigned_to[]" 
                            placeholder="Select assignees..." 
                            :selected="$selectedAssigneeIds"
                            class="w-100"
                        >
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </x-multiselect>
                    </div>
                @endif
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">PRIORITY</label>
                    <x-select name="priority" placeholder="All Priorities" :selected="request('priority')">
                        <option value="" class="bg-dark">All Priorities</option>
                        <option value="low" class="bg-dark">Low</option>
                        <option value="medium" class="bg-dark">Medium</option>
                        <option value="high" class="bg-dark">High</option>
                        <option value="urgent" class="bg-dark">Urgent</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <x-select name="status" placeholder="All Statuses" :selected="request('status')">
                        <option value="" class="bg-dark">All Statuses</option>
                        <option value="open" class="bg-dark">Open</option>
                        <option value="in_progress" class="bg-dark">In Progress</option>
                        <option value="waiting_for_client" class="bg-dark">Waiting for Client</option>
                        <option value="resolved" class="bg-dark">Resolved</option>
                        <option value="closed" class="bg-dark">Closed</option>
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATED DATE</label>
                    <input type="date" name="created_at" value="{{ request('created_at') }}" class="form-premium-control bg-white text-dark border-main">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">UPDATED DATE</label>
                    <input type="date" name="updated_at" value="{{ request('updated_at') }}" class="form-premium-control bg-white text-dark border-main">
                </div>
            </div>
            <div class="filter-slideover-footer">
                <a href="{{ route('admin.tickets.index') }}" class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</a>
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
            const topBar = document.querySelector('.data-grid-top');

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

        function submitBulkAction(action) {
            alert('Bulk action: ' + action);
        }
    </script>
</x-admin>


