<x-admin>
    <x-slot:title>
        Role Management | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Role Management</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage user roles and permissions.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hr.settings') }}" class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                <i class="fas fa-user-gear me-1"></i> HR Settings
            </a>
            <button class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                <i class="fas fa-plus-circle me-1"></i> Add Role
            </button>
        </div>
    </div>

    @php
        $groupMeta = [
            'hr' => ['title' => 'HR Settings', 'icon' => 'fas fa-user-gear text-primary', 'is_hr' => true],
            'attendance' => ['title' => 'Attendance & Time', 'icon' => 'fas fa-calendar-check text-info', 'is_hr' => false],
            'users' => ['title' => 'Users & Staff', 'icon' => 'fas fa-users text-success', 'is_hr' => false],
            'roles' => ['title' => 'Roles & Access', 'icon' => 'fas fa-shield-halved text-purple', 'is_hr' => false],
            'settings' => ['title' => 'System Settings', 'icon' => 'fas fa-sliders-h text-secondary', 'is_hr' => false],
            'tasks' => ['title' => 'Tasks & Projects', 'icon' => 'fas fa-tasks text-warning', 'is_hr' => false],
            'projects' => ['title' => 'Projects', 'icon' => 'fas fa-diagram-project text-primary', 'is_hr' => false],
            'tickets' => ['title' => 'Tickets & Support', 'icon' => 'fas fa-headset text-danger', 'is_hr' => false],
            'clients' => ['title' => 'Clients', 'icon' => 'fas fa-user-tie text-info', 'is_hr' => false],
            'chat' => ['title' => 'Team Chat', 'icon' => 'fas fa-comments text-success', 'is_hr' => false],
            'meetings' => ['title' => 'Meetings & Calls', 'icon' => 'fas fa-video text-warning', 'is_hr' => false],
            'dashboard' => ['title' => 'Dashboard', 'icon' => 'fas fa-chart-line text-primary', 'is_hr' => false],
        ];
    @endphp

    <!-- Add Role Modal -->
    <x-modal id="addRoleModal" title="Create New Role" submitText="Create Role" size="modal-lg"
        formAction="{{ route('admin.roles.store') }}">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label text-high fw-semibold">Role Name</label>
                <input type="text" name="name" id="add_role_name" class="form-premium-control w-100" placeholder="e.g. HR Manager, Staff Admin" required
                    oninput="document.getElementById('add_role_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')">
            </div>
            <div class="col-md-6">
                <label class="form-label text-high fw-semibold">Role Slug</label>
                <input type="text" name="slug" id="add_role_slug" class="form-premium-control w-100" placeholder="e.g. hr-manager" required>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label text-high fw-semibold mb-0">Role Permissions</label>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small" onclick="toggleAllPerms('addRoleModal', true)">Select All</button>
                    <span class="text-low small">&bull;</span>
                    <button type="button" class="btn btn-sm btn-link text-low p-0 text-decoration-none small" onclick="toggleAllPerms('addRoleModal', false)">Deselect All</button>
                </div>
            </div>

            <div class="row g-3" style="max-height: 460px; overflow-y: auto; padding: 4px;">
                @foreach (config('permissions') as $group => $permissions)
                    @php
                        $meta = $groupMeta[$group] ?? ['title' => ucfirst($group), 'icon' => 'fas fa-key text-low', 'is_hr' => false];
                    @endphp
                    <div class="col-md-6">
                        <div class="glass-card p-3 h-100 border-main {{ $meta['is_hr'] ? 'border-primary' : '' }}" 
                             style="background: {{ $meta['is_hr'] ? 'rgba(59, 130, 246, 0.05)' : 'rgba(255,255,255,0.02)' }};">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-subtle">
                                <h6 class="text-high fw-bold small text-uppercase mb-0 tracking-wider d-flex align-items-center gap-2">
                                    <i class="{{ $meta['icon'] }}"></i>
                                    {{ $meta['title'] }}
                                    @if($meta['is_hr'])
                                        <span class="badge bg-primary text-white" style="font-size: 0.65rem;">HR Module</span>
                                    @endif
                                </h6>
                                <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small" style="font-size: 0.75rem;" onclick="toggleGroupPerms('group_add_{{ $group }}')">
                                    Toggle
                                </button>
                            </div>
                            <div id="group_add_{{ $group }}">
                                @if (is_array($permissions))
                                    @foreach ($permissions as $key => $label)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                value="{{ $group }}.{{ $key }}"
                                                id="perm_add_{{ $group }}_{{ $key }}">
                                            <label class="form-check-label text-medium small" for="perm_add_{{ $group }}_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                            value="{{ $group }}" id="perm_add_{{ $group }}">
                                        <label class="form-check-label text-medium small" for="perm_add_{{ $group }}">
                                            {{ $permissions }}
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-modal>

    {{-- Bulk Action Form wraps the Data Grid --}}
    <form id="bulkRoleActionForm" action="{{ route('admin.roles.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkRoleActionType">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" form="searchForm" placeholder="Search roles by name or slug..." value="{{ request('search') }}" onchange="document.getElementById('searchForm').submit()">
                    @if(request('search'))
                        <a href="{{ route('admin.roles.index') }}" class="text-low ms-2 text-decoration-none small" title="Clear search">
                            <i class="fas fa-times-circle"></i>
                        </a>
                    @endif
                </div>
                <div class="data-grid-results">{{ $roles->total() }} Roles</div>
                <div class="data-grid-actions">
                    {{ $roles->links('components.pagination.premium') }}
                </div>
            </div>

            <!-- Sticky Bulk Action Bar -->
            <div class="data-grid-bulk-actions d-none" id="bulkActionBar">
                <span class="text-white small fw-semibold me-2"><span id="selectedCount">0</span> selected</span>
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn-bulk-danger" onclick="submitBulkRoleAction('delete')">
                        <i class="fas fa-trash-alt me-1"></i> Delete Selected
                    </button>
                </div>
                <button type="button" class="btn-deselect-all" onclick="deselectAllRoles()">
                    Deselect All
                </button>
            </div>

            <div class="table-responsive">
                <table class="table data-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll"></th>
                            <th>ROLE NAME <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>SLUG <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>HR PERMISSIONS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>ALL PERMISSIONS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>USERS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th class="text-end pe-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            @php
                                $rolePerms = $role->permissions ?? [];
                                $hrPermCount = is_array($rolePerms) ? count(array_filter($rolePerms, fn($p) => str_starts_with($p, 'hr.'))) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $role->id }}" class="data-grid-checkbox role-checkbox">
                                </td>
                                <td class="text-high fw-medium">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-premium icon-primary-premium m-0 d-inline-flex justify-content-center align-items-center" style="width: 28px; height: 28px;">
                                            <i class="fas fa-shield-halved" style="font-size: 0.7rem;"></i>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">{{ $role->name }}</span>
                                            @if($role->slug === 'hr-manager' || str_contains($role->slug, 'hr'))
                                                <span class="badge bg-primary bg-opacity-25 text-primary ms-1 small" style="font-size: 0.65rem;">HR Role</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-high font-monospace small">{{ $role->slug }}</td>
                                <td>
                                    @if($hrPermCount > 0)
                                        <button type="button" class="badge bg-primary bg-opacity-25 text-primary border-0 small px-2 py-1 fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">
                                            <i class="fas fa-user-gear me-1"></i> {{ $hrPermCount }} HR Perms
                                        </button>
                                    @else
                                        <span class="text-low small">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none border-0 fw-medium" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">
                                        {{ is_array($rolePerms) ? count($rolePerms) : 0 }} Perms
                                    </button>
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.index', ['role_id' => $role->id]) }}" class="action-link d-flex align-items-center gap-1 text-decoration-none">
                                        <i class="fas fa-users" style="font-size: 0.8rem;"></i>
                                        <span class="small fw-medium">{{ $role->users_count }}</span>
                                    </a>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="action-link border-0 bg-transparent" title="Edit Role & HR Settings" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    @if ($role->users_count == 0 && !in_array($role->slug, ['super-admin', 'manager', 'client', 'hr-manager']))
                                        <button type="button" class="action-link delete border-0 bg-transparent" title="Delete Role" data-bs-toggle="modal" data-bs-target="#deleteRoleModal{{ $role->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-medium">No roles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Standalone search form to submit GET requests --}}
    <form action="{{ route('admin.roles.index') }}" method="GET" id="searchForm" class="d-none">
    </form>

    @foreach ($roles as $role)
        <!-- Edit Role Modal -->
        <x-modal id="editRoleModal{{ $role->id }}" title="Edit Role: {{ $role->name }}" size="modal-lg"
            submitText="Save Changes" formAction="{{ route('admin.roles.update', $role->id) }}">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-high fw-semibold">Role Name</label>
                    <input type="text" name="name" value="{{ $role->name }}" class="form-premium-control w-100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-high fw-semibold">Role Slug</label>
                    <input type="text" name="slug" value="{{ $role->slug }}" class="form-premium-control w-100" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <label class="form-label text-high fw-semibold mb-0">Role Permissions</label>
                        <p class="text-low small mb-0" style="font-size: 0.8rem;">Configure system access and specific HR Settings privileges for this role.</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small" onclick="toggleAllPerms('editRoleModal{{ $role->id }}', true)">Select All</button>
                        <span class="text-low small">&bull;</span>
                        <button type="button" class="btn btn-sm btn-link text-low p-0 text-decoration-none small" onclick="toggleAllPerms('editRoleModal{{ $role->id }}', false)">Deselect All</button>
                    </div>
                </div>

                <div class="row g-3" style="max-height: 460px; overflow-y: auto; padding: 4px;">
                    @foreach (config('permissions') as $group => $permissions)
                        @php
                            $meta = $groupMeta[$group] ?? ['title' => ucfirst($group), 'icon' => 'fas fa-key text-low', 'is_hr' => false];
                        @endphp
                        <div class="col-md-6">
                            <div class="glass-card p-3 h-100 border-main {{ $meta['is_hr'] ? 'border-primary' : '' }}" 
                                 style="background: {{ $meta['is_hr'] ? 'rgba(59, 130, 246, 0.05)' : 'rgba(255,255,255,0.02)' }};">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-subtle">
                                    <h6 class="text-high fw-bold small text-uppercase mb-0 tracking-wider d-flex align-items-center gap-2">
                                        <i class="{{ $meta['icon'] }}"></i>
                                        {{ $meta['title'] }}
                                        @if($meta['is_hr'])
                                            <span class="badge bg-primary text-white" style="font-size: 0.65rem;">HR Module</span>
                                        @endif
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small" style="font-size: 0.75rem;" onclick="toggleGroupPerms('group_edit_{{ $group }}_{{ $role->id }}')">
                                        Toggle
                                    </button>
                                </div>
                                <div id="group_edit_{{ $group }}_{{ $role->id }}">
                                    @if (is_array($permissions))
                                        @foreach ($permissions as $key => $label)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                                    value="{{ $group }}.{{ $key }}"
                                                    id="perm_{{ $group }}_{{ $key }}_{{ $role->id }}"
                                                    {{ in_array($group . '.' . $key, $role->permissions ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label text-medium small"
                                                    for="perm_{{ $group }}_{{ $key }}_{{ $role->id }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                value="{{ $group }}"
                                                id="perm_{{ $group }}_{{ $role->id }}"
                                                {{ in_array($group, $role->permissions ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label text-medium small"
                                                for="perm_{{ $group }}_{{ $role->id }}">
                                                {{ $permissions }}
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-modal>

        @if ($role->users_count == 0 && !in_array($role->slug, ['super-admin', 'manager', 'client', 'hr-manager']))
            <!-- Delete Role Confirmation Modal -->
            <x-modal id="deleteRoleModal{{ $role->id }}" title="Are you sure?" variant="danger"
                cancelText="No, Cancel" submitText="Yes, Delete Role" size="modal-sm"
                formAction="{{ route('admin.roles.delete', $role->id) }}" method="DELETE"
                bodyClass="text-center p-4">
                <div class="mb-3 text-danger" style="font-size: 3rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="text-low small mb-0">You are about to delete the <strong
                        class="text-high">{{ $role->name }}</strong>
                    role.
                    This action cannot be undone.</p>
            </x-modal>
        @endif
    @endforeach

    <script>
        function toggleAllPerms(modalId, check) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.querySelectorAll('input[type="checkbox"][name="permissions[]"]').forEach(cb => cb.checked = check);
            }
        }

        function toggleGroupPerms(groupId) {
            const group = document.getElementById(groupId);
            if (group) {
                const cbs = group.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
                const allChecked = Array.from(cbs).every(cb => cb.checked);
                cbs.forEach(cb => cb.checked = !allChecked);
            }
        }

        function submitBulkRoleAction(action) {
            const selected = document.querySelectorAll('.role-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one role.');
                return;
            }
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the ' + selected.length + ' selected role(s)? Roles with active users cannot be deleted.')) {
                    return;
                }
            }
            document.getElementById('bulkRoleActionType').value = action;
            document.getElementById('bulkRoleActionForm').submit();
        }

        function updateBulkState() {
            const checkboxes = document.querySelectorAll('.role-checkbox');
            const checked = document.querySelectorAll('.role-checkbox:checked');
            const selectAll = document.getElementById('selectAll');
            const bulkBar = document.getElementById('bulkActionBar');
            const countSpan = document.getElementById('selectedCount');

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
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

        function deselectAllRoles() {
            document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.checked = false;
            updateBulkState();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = selectAll.checked);
                    updateBulkState();
                });
            }

            document.querySelectorAll('.role-checkbox').forEach(cb => {
                cb.addEventListener('change', updateBulkState);
            });
        });
    </script>
</x-admin>
