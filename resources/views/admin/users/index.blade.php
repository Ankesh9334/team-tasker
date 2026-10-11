<x-admin>
    <x-slot:title>
        Team Management | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Team Management</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage internal staff and system users.</p>
        </div>
        <button class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus-circle me-1"></i> Add Team Member
        </button>
    </div>

    @if ($errors->any())
        <div class="glass-card mb-4 border-danger" style="background: rgba(var(--danger-rgb), 0.05);">
            <div class="d-flex gap-3">
                <div class="text-danger"><i class="fas fa-exclamation-circle fs-5"></i></div>
                <div>
                    <h6 class="fw-bold text-danger mb-2">Attention Required</h6>
                    <ul class="mb-0 text-low small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Add User Modal -->
    <x-modal id="addUserModal" title="Onboard Team Member" submitText="Create Profile"
        formAction="{{ route('admin.users.store') }}">
        <div class="mb-4">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Legal Full Name</label>
            <input type="text" name="name" class="form-premium-control" required placeholder="e.g. John Doe">
        </div>
        <div class="mb-4">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Professional Email</label>
            <input type="email" name="email" class="form-premium-control" required placeholder="john@company.com">
        </div>
        <div class="mb-4">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Secure Password</label>
            <input type="password" name="password" class="form-premium-control" required
                placeholder="Minimum 8 characters">
        </div>
        <div class="mb-4">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Administrative Role</label>
            <x-select name="role_id" placeholder="Normal User">
                <option value="" class="bg-dark">Normal User</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" class="bg-dark">{{ $role->name }}</option>
                @endforeach
            </x-select>
        </div>
    </x-modal>

    {{-- ── Filter Slideover ── --}}
    <div class="filter-slideover" id="filterSlideoverUsers">
        <form action="{{ route('admin.users.index') }}" method="GET" class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <div class="filter-slideover-close" onclick="document.getElementById('filterSlideoverUsers').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH NAME</label>
                    <input type="text" name="name" value="{{ request('name') }}" class="form-premium-control bg-white text-dark border-main" placeholder="Search by name...">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">SEARCH EMAIL</label>
                    <input type="text" name="email" value="{{ request('email') }}" class="form-premium-control bg-white text-dark border-main" placeholder="Search by email...">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">ROLE</label>
                    <x-select name="role_id" placeholder="All Roles" :selected="request('role_id')">
                        <option value="" class="bg-dark">All Roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" class="bg-dark">{{ $role->name }}</option>
                        @endforeach
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
                <a href="{{ route('admin.users.index') }}" class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</a>
                <button type="submit" class="btn-premium btn-premium-primary w-50 justify-content-center" style="background: #0ea5e9;">Apply Filters</button>
            </div>
        </form>
    </div>

    {{-- ── Bulk Action Form wraps ONLY the table ── --}}
    <form id="bulkActionForm" action="{{ route('admin.users.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkActionType">
        <input type="hidden" name="role_id" id="bulkRoleId">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search anything..." value="{{ request('search') }}" onkeypress="if(event.key === 'Enter') { event.preventDefault(); window.location.href='{{ route('admin.users.index') }}?search=' + this.value; }">
                </div>
                <div class="data-grid-results">{{ $users->total() }} Results</div>
                <div class="data-grid-actions">
                    {{ $users->links() }}
                </div>
            </div>

            <div class="data-grid-bulk-actions" id="bulkActionBar">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span id="selectedCount">0</span> Items Selected</span>
                
                <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('approve')">
                    <i class="fas fa-check-circle"></i> Approve
                </button>
                <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('disapprove')">
                    <i class="fas fa-times-circle"></i> Suspend
                </button>
                
                <div class="d-flex align-items-center gap-2 border-start border-white-50 ps-3 ms-1">
                    <select id="bulkRoleSelect" class="form-select form-select-sm" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 28px 4px 10px; width: 140px; cursor: pointer;">
                        <option value="" style="color: black;">Map to Role...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" style="color: black;">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-bulk-outline" onclick="submitBulkAction('change_role')">
                        Apply
                    </button>
                </div>

                <button type="button" class="btn-bulk-danger border-start border-white-50 ps-3 ms-1" onclick="submitBulkAction('delete')" style="border-radius: 0 6px 6px 0;">
                    <i class="fas fa-trash-alt"></i> Delete Permanent
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
                            <th>TEAM MEMBER <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>ROLE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th class="text-center">STATUS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>CONTACT DETAILS</th>
                            <th class="text-end pe-4">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $user->id }}" class="data-grid-checkbox user-checkbox"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-premium" style="width: 32px; height: 32px;">
                                            @if ($user->profile_image)
                                                <img alt="team-tasker" src="{{ asset('storage/' . $user->profile_image) }}" alt="">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center w-100 h-100 text-white" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); font-weight: 600; font-size: 0.8rem;">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-high">{{ $user->name }}</div>
                                            <div class="text-low extra-small">ID: #{{ $user->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($user->role)
                                        <span class="text-high fw-medium">{{ $user->role->name }}</span>
                                    @else
                                        <span class="text-low italic">Normal User</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($user->is_approved)
                                        <span class="badge-premium" style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">APPROVED</span>
                                    @else
                                        <span class="badge-premium" style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PENDING</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-medium">{{ $user->email }}</div>
                                    @if ($user->phone)
                                        <div class="text-low extra-small"><i class="fas fa-phone-alt me-1"></i>{{ $user->phone }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.toggleApproval', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="action-link border-0 bg-transparent" title="{{ $user->is_approved ? 'Revoke Approval' : 'Grant Approval' }}" style="color: {{ $user->is_approved ? 'var(--danger)' : 'var(--accent)' }};">
                                                    <i class="fas {{ $user->is_approved ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="action-link border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <button type="button" class="action-link delete border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#deleteUserModal{{ $user->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const bulkActionBar = document.getElementById('bulkActionBar');
            const selectedCount = document.getElementById('selectedCount');
            const topBar = document.querySelector('.data-grid-top');

            function updateBulkBar() {
                const checked = document.querySelectorAll('.user-checkbox:checked').length;
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
            const checked = document.querySelectorAll('.user-checkbox:checked');
            if (checked.length === 0) return;

            document.getElementById('bulkActionType').value = action;
            if (action === 'change_role') {
                const roleId = document.getElementById('bulkRoleSelect').value;
                if (!roleId) {
                    alert('Please select a role to map.');
                    return;
                }
                document.getElementById('bulkRoleId').value = roleId;
            }

            if (action === 'delete') {
                if (!confirm('Are you sure you want to permanently delete the selected users? This action cannot be undone.')) {
                    return;
                }
            }

            document.getElementById('bulkActionForm').submit();
        }
    </script>


    @foreach ($users as $user)
        <!-- Edit User Modal -->
        <x-modal id="editUserModal{{ $user->id }}" title="Refine Profile: {{ $user->name }}"
            submitText="Update Identity" formAction="{{ route('admin.users.update', $user->id) }}">
            <div class="mb-4">
                <label class="heading-label mb-2" style="font-size: 0.7rem;">Display Name</label>
                <input type="text" name="name" value="{{ $user->name }}" class="form-premium-control"
                    required>
            </div>
            <div class="mb-4">
                <label class="heading-label mb-2" style="font-size: 0.7rem;">Communication Email</label>
                <input type="email" name="email" value="{{ $user->email }}" class="form-premium-control"
                    required>
            </div>
            <div class="mb-4">
                <label class="heading-label mb-2" style="font-size: 0.7rem;">Authentication Password</label>
                <input type="password" name="password" class="form-premium-control"
                    placeholder="Leave empty to maintain current">
            </div>
            <div class="mb-4">
                <label class="heading-label mb-2" style="font-size: 0.7rem;">Access Permissions</label>
                <x-select name="role_id" placeholder="Normal User">
                    <option value="" class="bg-dark">Normal User</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}
                            class="bg-dark">
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>
        </x-modal>

        @if ($user->id !== auth()->id())
            <!-- Delete User Confirmation Modal -->
            <x-modal id="deleteUserModal{{ $user->id }}" title="Security Warning" variant="danger"
                cancelText="Retain Member" submitText="Purge Account" size="modal-sm"
                formAction="{{ route('admin.users.delete', $user->id) }}" method="DELETE"
                bodyClass="text-center p-4">
                <div class="mb-4 text-danger" style="font-size: 3.5rem; opacity: 0.8;">
                    <i class="fas fa-user-minus"></i>
                </div>
                <h6 class="fw-bold mb-3" style="color: var(--text-high);">Irreversible Action</h6>
                <p class="text-low mb-0" style="font-size: 0.85rem;">You are about to permanently remove
                    <strong>{{ $user->name }}</strong>. All associated permissions and session keys will be
                    invalidated immediately.
                </p>
            </x-modal>
        @endif
    @endforeach
</x-admin>


