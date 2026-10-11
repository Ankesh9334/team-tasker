<x-admin>
    <x-slot:title>
        Attendance Requests | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Leave & Regularization Requests</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage employee leave, overtime, and regularization requests.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.attendance.calendar') }}" class="btn-premium btn-premium-secondary px-3 py-2 shadow-sm d-flex align-items-center gap-1">
                <i class="fas fa-calendar-alt me-1"></i> Calendar View
            </a>
            <button class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                <i class="fas fa-plus-circle me-1"></i> New Request
            </button>
        </div>
    </div>

    <form id="bulkRequestsForm" action="{{ route('admin.attendance.requests.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkRequestActionType">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" form="requestsSearchForm" value="{{ request('search') }}" placeholder="Search by employee name or email..." onchange="document.getElementById('requestsSearchForm').submit()">
                    @if(request('search'))
                        <a href="{{ route('admin.attendance.requests', array_merge(request()->except('search'))) }}" class="text-low ms-2 text-decoration-none small" title="Clear search">
                            <i class="fas fa-times-circle"></i>
                        </a>
                    @endif
                </div>
                <div class="data-grid-results">{{ $requests->total() }} Results</div>
                <div class="data-grid-actions d-flex align-items-center gap-2">
                    {{ $requests->links('components.pagination.premium') }}
                </div>
            </div>

            <!-- Sticky Bulk Action Bar -->
            <div class="data-grid-bulk-actions d-none" id="bulkActionBar">
                <span class="text-white small fw-semibold me-2"><span id="selectedCount">0</span> selected</span>
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn-bulk-success" onclick="submitBulkRequests('Approved')">
                        <i class="fas fa-check-circle me-1"></i> Approve Selected
                    </button>
                    <button type="button" class="btn-bulk-danger" onclick="submitBulkRequests('Rejected')">
                        <i class="fas fa-times-circle me-1"></i> Reject Selected
                    </button>
                </div>
                <button type="button" class="btn-deselect-all" onclick="deselectAllRequests()">
                    Deselect All
                </button>
            </div>

            <div class="table-responsive">
                <table class="table data-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll"></th>
                            <th>EMPLOYEE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>TYPE</th>
                            <th>DATES</th>
                            <th>REASON</th>
                            <th class="text-center">STATUS</th>
                            <th>ACTION BY</th>
                            <th class="text-end pe-4">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $req->id }}" class="data-grid-checkbox req-checkbox" {{ $req->status !== 'Pending' ? 'disabled title=Processed' : '' }}>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-premium" style="width: 32px; height: 32px;">
                                            @if ($req->user->profile_image)
                                                <img src="{{ asset('storage/' . $req->user->profile_image) }}" alt="Profile">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($req->user->name ?? 'U', 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-high">{{ $req->user->name }}</div>
                                            <div class="text-low extra-small">{{ $req->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-premium bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1">
                                        {{ $req->type }}
                                    </span>
                                </td>
                                <td class="text-low small">
                                    {{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }}
                                    @if($req->end_date && $req->start_date !== $req->end_date)
                                        - {{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}
                                    @endif
                                </td>
                                <td class="text-low text-truncate" style="max-width: 200px;" title="{{ $req->reason }}">
                                    {{ $req->reason }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusColor = match($req->status) {
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            default => 'warning'
                                        };
                                    @endphp
                                    <span class="badge-premium bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-2 py-1">
                                        {{ $req->status }}
                                    </span>
                                </td>
                                <td class="text-low small">
                                    {{ $req->actionBy ? $req->actionBy->name : '-' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($req->status === 'Pending' && (Auth::user()->hasPermission('attendance.requests_manage') || Auth::user()->hasPermission('hr.leave_requests') || Auth::user()->hasRole('super-admin')))
                                            <button type="button" class="action-link border-0 bg-transparent" title="Approve Request" style="color: var(--success);" onclick="updateStatus({{ $req->id }}, 'Approved')">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <button type="button" class="action-link delete border-0 bg-transparent" title="Reject Request" onclick="updateStatus({{ $req->id }}, 'Rejected')">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif
                                        
                                        @if($req->status === 'Pending' && $req->user_id === Auth::id())
                                            <button type="button" class="action-link border-0 bg-transparent" title="Edit Request" onclick="editRequest({{ $req->id }}, '{{ $req->type }}', '{{ $req->start_date }}', '{{ $req->end_date }}', `{{ addslashes($req->reason) }}`)">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.attendance.calendar', ['user_id' => $req->user_id]) }}" class="action-link border-0 bg-transparent" title="View Calendar">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if($requests->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center text-low py-4">No requests found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Standalone search form --}}
    <form action="{{ route('admin.attendance.requests') }}" method="GET" id="requestsSearchForm" class="d-none">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
    </form>

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high">New Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.attendance.requests.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Type</label>
                            <select name="type" class="form-premium-control w-100" required>
                                <option value="Leave">Leave</option>
                                <option value="Regularization">Attendance Regularization</option>
                                <option value="Overtime">Overtime</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-high fw-semibold">Start Date</label>
                                <input type="date" name="start_date" class="form-premium-control w-100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-high fw-semibold">End Date (Optional)</label>
                                <input type="date" name="end_date" class="form-premium-control w-100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Reason</label>
                            <textarea name="reason" class="form-premium-control w-100" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn-premium-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high">Edit Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRequestForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Type</label>
                            <select name="type" id="editRequestType" class="form-premium-control w-100" required>
                                <option value="Leave">Leave</option>
                                <option value="Regularization">Attendance Regularization</option>
                                <option value="Overtime">Overtime</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-high fw-semibold">Start Date</label>
                                <input type="date" name="start_date" id="editRequestStartDate" class="form-premium-control w-100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-high fw-semibold">End Date (Optional)</label>
                                <input type="date" name="end_date" id="editRequestEndDate" class="form-premium-control w-100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Reason</label>
                            <textarea name="reason" id="editRequestReason" class="form-premium-control w-100" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn-premium-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave Request Action Modal -->
    <div class="modal fade" id="leaveActionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high" id="leaveActionTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="leaveActionForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" id="leaveActionStatus">
                    <div class="modal-body">
                        <p class="text-medium mb-3" id="leaveActionMessage">Are you sure you want to proceed?</p>
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Action Notes (Optional)</label>
                            <textarea name="action_notes" class="form-premium-control w-100" rows="3" placeholder="Explain the reason for approval or rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-premium btn-premium-primary" id="leaveActionSubmitBtn">
                            Confirm Action
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editRequest(id, type, startDate, endDate, reason) {
            const modal = new bootstrap.Modal(document.getElementById('editRequestModal'));
            const form = document.getElementById('editRequestForm');
            
            form.action = "{{ route('admin.attendance.requests.update', ':id') }}".replace(':id', id);
            
            document.getElementById('editRequestType').value = type;
            document.getElementById('editRequestStartDate').value = startDate;
            document.getElementById('editRequestEndDate').value = endDate || '';
            document.getElementById('editRequestReason').value = reason;
            
            modal.show();
        }

        function updateStatus(id, status) {
            const modal = new bootstrap.Modal(document.getElementById('leaveActionModal'));
            const form = document.getElementById('leaveActionForm');
            form.action = "{{ route('admin.attendance.requests.updateStatus', ':id') }}".replace(':id', id);
            
            document.getElementById('leaveActionStatus').value = status;
            document.getElementById('leaveActionTitle').innerText = status === 'Approved' ? 'Approve Leave Request' : 'Reject Leave Request';
            document.getElementById('leaveActionMessage').innerText = `Are you sure you want to mark this request as ${status}?`;
            
            const submitBtn = document.getElementById('leaveActionSubmitBtn');
            submitBtn.className = status === 'Approved' ? 'btn-premium btn-premium-primary' : 'btn-premium btn btn-danger';
            submitBtn.innerText = status === 'Approved' ? 'Approve Request' : 'Reject Request';
            
            modal.show();
        }

        function submitBulkRequests(action) {
            const selected = document.querySelectorAll('.req-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one request.');
                return;
            }
            if (!confirm(`Are you sure you want to mark ${selected.length} request(s) as ${action}?`)) {
                return;
            }
            document.getElementById('bulkRequestActionType').value = action;
            document.getElementById('bulkRequestsForm').submit();
        }

        function updateBulkState() {
            const enabledCheckboxes = document.querySelectorAll('.req-checkbox:not(:disabled)');
            const checked = document.querySelectorAll('.req-checkbox:checked');
            const selectAll = document.getElementById('selectAll');
            const bulkBar = document.getElementById('bulkActionBar');
            const countSpan = document.getElementById('selectedCount');

            if (selectAll) {
                selectAll.checked = enabledCheckboxes.length > 0 && checked.length === enabledCheckboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < enabledCheckboxes.length;
            }

            if (bulkBar) {
                if (checked.length > 0) {
                    bulkBar.classList.remove('d-none');
                    bulkBar.classList.add('d-flex');
                    if (countSpan) countSpan.textContent = checked.length;
                } else {
                    bulkBar.classList.remove('d-flex');
                    bulkBar.classList.add('d-none');
                }
            }
        }

        function deselectAllRequests() {
            document.querySelectorAll('.req-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.checked = false;
            updateBulkState();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.req-checkbox:not(:disabled)').forEach(cb => cb.checked = selectAll.checked);
                    updateBulkState();
                });
            }

            document.querySelectorAll('.req-checkbox').forEach(cb => {
                cb.addEventListener('change', updateBulkState);
            });
        });
    </script>
</x-admin>

<div class="filter-slideover" id="filterSlideoverAttendance">
    <form action="{{ route('admin.attendance.requests') }}" method="GET" class="h-100 d-flex flex-column">
        <div class="filter-slideover-header">
            <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
            <div class="filter-slideover-close" onclick="document.getElementById('filterSlideoverAttendance').classList.remove('show')">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="filter-slideover-body">
            @if((Auth::user()->hasPermission('attendance.requests_manage') || Auth::user()->hasRole('super-admin')) && isset($allUsers) && $allUsers->isNotEmpty())
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">EMPLOYEE / USER</label>
                    @php
                        $selectedUserIds = is_array(request('user_id')) 
                            ? array_values(array_filter(array_map('strval', request('user_id')))) 
                            : (request('user_id') ? [(string) request('user_id')] : []);
                    @endphp
                    <x-multiselect 
                        id="filter_user_id" 
                        name="user_id[]" 
                        placeholder="Select employees..." 
                        :selected="$selectedUserIds"
                        class="w-100"
                    >
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </x-multiselect>
                </div>
            @endif

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">REQUEST TYPE</label>
                <x-select 
                    id="filter_type" 
                    name="type" 
                    placeholder="All Request Types" 
                    :selected="request('type')"
                    class="w-100"
                >
                    <option value="" class="bg-dark">All Request Types</option>
                    <option value="Leave" class="bg-dark">Leave</option>
                    <option value="Regularization" class="bg-dark">Regularization</option>
                    <option value="Overtime" class="bg-dark">Overtime</option>
                </x-select>
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-low">STATUS</label>
                <x-select 
                    id="filter_status" 
                    name="status" 
                    placeholder="All Statuses" 
                    :selected="request('status')"
                    class="w-100"
                >
                    <option value="" class="bg-dark">All Statuses</option>
                    <option value="Pending" class="bg-dark">Pending</option>
                    <option value="Approved" class="bg-dark">Approved</option>
                    <option value="Rejected" class="bg-dark">Rejected</option>
                </x-select>
            </div>
        </div>
        <div class="filter-slideover-footer">
            <a href="{{ route('admin.attendance.requests') }}" class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</a>
            <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center" style="background: #0ea5e9;">Apply Filters</button>
        </div>
    </form>
</div>
