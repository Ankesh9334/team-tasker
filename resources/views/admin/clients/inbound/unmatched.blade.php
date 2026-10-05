<x-admin title="Unmatched Inbound Emails">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Unmatched Emails</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Inbound emails from unknown senders — assign to clients or ignore.</p>
        </div>
        <a href="{{ route('admin.clients.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Clients
        </a>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('admin.clients.unmatched.index') }}" method="GET" id="searchForm">
                    <input type="text" name="search" placeholder="Search by sender..." value="{{ request('search') }}" onchange="document.getElementById('searchForm').submit()">
                </form>
            </div>
            <div class="data-grid-results">{{ $unmatchedEmails->total() }} Results</div>
            <div class="data-grid-actions">{{ $unmatchedEmails->links() }}</div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>FROM</th>
                        <th>SUBJECT</th>
                        <th>RECEIVED AT</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unmatchedEmails as $email)
                        <tr>
                            <td class="text-low" style="color: #64748b !important;">#{{ $email->id }}</td>
                            <td>
                                <div class="text-high fw-medium" style="font-size: 0.85rem;">{{ $email->from_name ?: 'Unknown' }}</div>
                                <div class="text-low" style="font-size: 0.7rem;">{{ $email->from_email }}</div>
                            </td>
                            <td class="text-high" style="max-width: 300px;">{{ \Illuminate\Support\Str::limit($email->subject ?: '(No subject)', 60) }}</td>
                            <td class="text-high" style="font-size: 0.8rem;">{{ $email->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end pe-4">
                                <button type="button" class="action-link border-0 bg-transparent" title="View & Assign" onclick="openAssignModal({{ $email->id }}, '{{ e($email->from_name) }}', '{{ e($email->from_email) }}', '{{ e(\Illuminate\Support\Str::limit($email->subject, 80)) }}')">
                                    <i class="fas fa-user-plus" style="color: var(--primary);"></i>
                                </button>
                                <form action="{{ route('admin.clients.unmatched.ignore', $email->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="action-link border-0 bg-transparent" title="Ignore" onclick="return confirm('Ignore this email?')">
                                        <i class="fas fa-ban" style="color: #64748b;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-medium">
                            <i class="fas fa-check-circle me-2" style="color: #10b981;"></i> No unmatched emails. All caught up!
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Assign Modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-main);">
                    <h5 class="modal-title text-high fw-semibold"><i class="fas fa-user-plus me-2"></i>Assign Email to Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        {{-- Email Info --}}
                        <div class="mb-4 p-3" style="background: var(--bg-input); border-radius: var(--radius-md); border: 1px solid var(--border-main);">
                            <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Inbound Email</div>
                            <div class="text-high fw-medium" id="modalFromName"></div>
                            <div class="text-low" style="font-size: 0.8rem;" id="modalFromEmail"></div>
                            <div class="text-low mt-1" style="font-size: 0.8rem;">Subject: <span class="text-high" id="modalSubject"></span></div>
                        </div>

                        {{-- Action Type --}}
                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Action Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <label class="d-flex align-items-center gap-2 text-high" style="cursor: pointer; font-size: 0.85rem;">
                                    <input type="radio" name="action_type" value="assign_existing" checked onchange="toggleAssignFields()">
                                    Assign to Existing Client
                                </label>
                                <label class="d-flex align-items-center gap-2 text-high" style="cursor: pointer; font-size: 0.85rem;">
                                    <input type="radio" name="action_type" value="create_client" onchange="toggleAssignFields()">
                                    Create New Client
                                </label>
                            </div>
                        </div>

                        {{-- Existing Client Selection --}}
                        <div id="existingClientFields" class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Select Client <span class="text-danger">*</span></label>
                            <select name="client_id" id="assignClientSelect" class="form-premium-control w-100">
                                <option value="">— Select Client —</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }} {{ $client->company_name ? '(' . $client->company_name . ')' : '' }} — {{ $client->email }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- New Client Fields --}}
                        <div id="newClientFields" style="display: none;">
                            <div class="mb-4">
                                <label class="heading-label d-block mb-2 text-high">Client Name <span class="text-danger">*</span></label>
                                <input type="text" name="new_client_name" class="form-premium-control w-100" placeholder="Full name">
                            </div>
                            <div class="mb-4">
                                <label class="heading-label d-block mb-2 text-high">Company Name</label>
                                <input type="text" name="new_client_company" class="form-premium-control w-100" placeholder="Company name (optional)">
                            </div>
                        </div>

                        {{-- Create Ticket Option --}}
                        <div class="mb-4">
                            <label class="d-flex align-items-center gap-2 text-high" style="cursor: pointer; font-size: 0.85rem;">
                                <input type="hidden" name="create_ticket" value="0">
                                <input type="checkbox" name="create_ticket" value="1" checked class="data-grid-checkbox">
                                <span>Create a ticket from this email</span>
                            </label>
                            <div class="text-low mt-1" style="font-size: 0.75rem;">A new support ticket will be created with the email content.</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--border-main);">
                        <button type="button" class="btn-premium btn-premium-secondary px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                            <i class="fas fa-check me-1"></i> Assign & Process
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAssignModal(id, fromName, fromEmail, subject) {
            document.getElementById('modalFromName').textContent = fromName || 'Unknown';
            document.getElementById('modalFromEmail').textContent = fromEmail;
            document.getElementById('modalSubject').textContent = subject || '(No subject)';
            document.getElementById('assignForm').action = `{{ url('admin/clients/unmatched-emails') }}/${id}/assign`;

            const modal = new bootstrap.Modal(document.getElementById('assignModal'));
            modal.show();

            // Initialize TomSelect on the client select if not already
            const sel = document.getElementById('assignClientSelect');
            if (sel && !sel.tomselect && typeof TomSelect !== 'undefined') {
                new TomSelect(sel, { placeholder: 'Search clients...' });
            }
        }

        function toggleAssignFields() {
            const isExisting = document.querySelector('input[name="action_type"][value="assign_existing"]').checked;
            document.getElementById('existingClientFields').style.display = isExisting ? 'block' : 'none';
            document.getElementById('newClientFields').style.display = isExisting ? 'none' : 'block';
        }
    </script>
</x-admin>
