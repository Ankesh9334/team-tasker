<x-admin>
    <x-slot:title>
        Task Status Settings | Team Tasker
    </x-slot:title>

    @php
        $resolveColor = function($color) {
            if (!$color) return '#6366f1';
            if (str_starts_with($color, '#')) return $color;
            return match(strtolower($color)) {
                'primary' => '#3b82f6',
                'secondary' => '#64748b',
                'success' => '#10b981',
                'danger' => '#ef4444',
                'warning' => '#f59e0b',
                'info' => '#06b6d4',
                'dark' => '#1e293b',
                'light' => '#94a3b8',
                default => '#6366f1',
            };
        };

        $presetColors = ['#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#ec4899', '#ef4444', '#f97316', '#f59e0b', '#10b981', '#14b8a6', '#06b6d4', '#64748b'];
    @endphp

    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high d-flex align-items-center gap-2">
                <i class="fas fa-sliders-h text-primary" style="font-size: 1.4rem;"></i>
                Task Status Settings
            </h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Configure workflow pipeline stages, progression sequence, and status color badges.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.tags') }}" class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                <i class="fas fa-tags me-1"></i> Task Tags
            </a>
            <button type="button" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createStatusModal">
                <i class="fas fa-plus-circle me-1"></i> Add New Status
            </button>
        </div>
    </div>

    <!-- Status Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Total Statuses</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                        <i class="fas fa-sliders-h" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-high">{{ $statusStats['total'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Default Status</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <i class="fas fa-check-circle" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success text-truncate" title="{{ $statusStats['default_name'] }}">{{ $statusStats['default_name'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Active Pipelines</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <i class="fas fa-project-diagram" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-warning">{{ $statusStats['in_use'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Active Tasks</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <i class="fas fa-tasks" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-high">{{ $statusStats['total_tasks'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Bulk Action Form -->
    <form id="bulkStatusForm" action="{{ route('admin.settings.status.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkStatusActionType">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" form="statusSearchForm" placeholder="Search statuses by name, slug, or color..." value="{{ request('search') }}" onchange="document.getElementById('statusSearchForm').submit()">
                    @if(request('search'))
                        <a href="{{ route('admin.settings.statuses') }}" class="text-low ms-2 text-decoration-none small" title="Clear search">
                            <i class="fas fa-times-circle"></i>
                        </a>
                    @endif
                </div>

                <div class="data-grid-results">{{ $statuses->total() }} Statuses</div>

                <div class="data-grid-actions d-flex align-items-center gap-2">
                    @if(request('search'))
                        <a href="{{ route('admin.settings.statuses') }}" class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    @endif
                    {{ $statuses->links('components.pagination.premium') }}
                </div>
            </div>

            <!-- Sticky Bulk Action Bar -->
            <div class="data-grid-bulk-actions d-none" id="bulkActionBar">
                <span class="text-white small fw-semibold me-2"><span id="selectedCount">0</span> selected</span>
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn-bulk-danger" onclick="submitBulkStatusAction('delete')">
                        <i class="fas fa-trash-alt me-1"></i> Delete Selected
                    </button>
                </div>
                <button type="button" class="btn-deselect-all" onclick="deselectAllStatuses()">
                    Deselect All
                </button>
            </div>

            <div class="table-responsive">
                <table class="table data-grid-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll"></th>
                            <th style="width: 80px;">ORDER <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>STATUS NAME <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>SLUG <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>COLOR CODE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th class="text-center">DEFAULT</th>
                            <th class="text-center">TASKS <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th class="text-end pe-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statuses as $status)
                            @php
                                $hex = $resolveColor($status->color);
                                $isDeletable = !$status->is_default && $status->tasks_count === 0;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $status->id }}"
                                        class="data-grid-checkbox status-checkbox"
                                        {{ !$isDeletable ? 'disabled' : '' }}
                                        title="{{ !$isDeletable ? ($status->is_default ? 'Default status cannot be deleted' : 'Status has assigned tasks') : 'Select status' }}">
                                </td>
                                <td>
                                    <span class="badge bg-surface border border-subtle text-low px-2 py-1 font-monospace" style="font-size: 0.8rem;">
                                        #{{ $status->order }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge-premium d-inline-flex align-items-center gap-1 px-3 py-1 fw-semibold"
                                              style="background: {{ $hex }}1a; color: {{ $hex }}; border: 1px solid {{ $hex }}40; font-size: 0.8rem; border-radius: 6px;">
                                            <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $hex }}; display: inline-block;"></span>
                                            {{ $status->name }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-monospace text-low small">{{ $status->slug }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 22px; height: 22px; border-radius: 5px; background: {{ $hex }}; border: 1px solid var(--border-subtle); box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                                        <span class="font-monospace small text-high fw-medium">{{ strtoupper($hex) }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($status->is_default)
                                        <span class="badge bg-success bg-opacity-15 text-success px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-check me-1"></i>DEFAULT
                                        </span>
                                    @else
                                        <span class="text-low small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($status->tasks_count > 0)
                                        <span class="badge bg-primary bg-opacity-15 text-primary px-2 py-1 rounded-pill small fw-semibold">
                                            <i class="fas fa-tasks me-1" style="font-size: 0.7rem;"></i>{{ $status->tasks_count }}
                                        </span>
                                    @else
                                        <span class="text-low small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="action-link border-0 bg-transparent" title="Edit Status" data-bs-toggle="modal" data-bs-target="#editStatusModal{{ $status->id }}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button type="button" class="action-link delete border-0 bg-transparent" title="{{ $isDeletable ? 'Delete Status' : 'Cannot Delete' }}" data-bs-toggle="modal" data-bs-target="#deleteStatusModal{{ $status->id }}">
                                            <i class="fas fa-trash {{ !$isDeletable ? 'opacity-50' : '' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-low">
                                        <i class="fas fa-sliders-h fa-3x mb-3 text-secondary opacity-50"></i>
                                        <p class="mb-0">No statuses found matching your query.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Standalone search form to submit GET requests --}}
    <form action="{{ route('admin.settings.statuses') }}" method="GET" id="statusSearchForm" class="d-none">
    </form>

    <!-- Create Status Modal -->
    <x-modal id="createStatusModal" title="Create New Status" submitText="Add Status" size="modal-md"
        formAction="{{ route('admin.settings.status.store') }}">
        <div class="mb-3">
            <label class="form-label text-high fw-semibold">Status Name</label>
            <input type="text" name="name" id="create_status_name" class="form-premium-control w-100" placeholder="e.g. In Review, QA Testing, Blocked" required
                oninput="document.getElementById('create_status_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); updateStatusPreview('create');">
        </div>

        <div class="mb-3">
            <label class="form-label text-high fw-semibold">Slug (Auto-generated)</label>
            <input type="text" name="slug" id="create_status_slug" class="form-premium-control w-100 font-monospace small" placeholder="e.g. in-review" required>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label text-high fw-semibold">Pipeline Order</label>
                <input type="number" name="order" class="form-premium-control w-100" value="{{ $statusStats['total'] + 1 }}" min="0">
                <div class="text-low small mt-1">Display sequence in board/list</div>
            </div>
            <div class="col-6 d-flex flex-column justify-content-center pt-3">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="create_status_default">
                    <label class="form-check-label text-high fw-semibold small" for="create_status_default">
                        Set as Default
                    </label>
                </div>
                <div class="text-low small">Applied to newly created tasks</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label text-high fw-semibold mb-2">Status Color</label>
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="color" name="color" id="create_status_color" class="form-control-color border-main rounded-2"
                    value="#6366f1" style="width: 44px; height: 38px; padding: 2px; cursor: pointer;"
                    onchange="document.getElementById('create_status_hex').value = this.value; updateStatusPreview('create');">
                <input type="text" id="create_status_hex" class="form-premium-control font-monospace small" value="#6366f1" style="width: 110px;"
                    oninput="document.getElementById('create_status_color').value = this.value; updateStatusPreview('create');">
            </div>

            <!-- Quick Preset Color Palette -->
            <div class="d-flex flex-wrap gap-2 pt-1">
                @foreach($presetColors as $color)
                    <button type="button" class="btn p-0 border rounded-circle" style="width: 24px; height: 24px; background-color: {{ $color }};"
                        onclick="setStatusColor('create', '{{ $color }}')" title="{{ $color }}"></button>
                @endforeach
            </div>
        </div>

        <div class="mb-2 p-3 rounded-2 bg-subtle border border-subtle">
            <label class="text-low small fw-semibold d-block mb-2">Live Badge Preview</label>
            <span id="create_status_preview" class="badge-premium px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                  style="background: #6366f11a; color: #6366f1; border: 1px solid #6366f140; font-size: 0.85rem;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #6366f1; display: inline-block;"></span>
                New Status
            </span>
        </div>
    </x-modal>

    <!-- Edit and Delete Modals for each status -->
    @foreach ($statuses as $status)
        @php
            $hex = $resolveColor($status->color);
            $isDeletable = !$status->is_default && $status->tasks_count === 0;
        @endphp

        <!-- Edit Status Modal -->
        <x-modal id="editStatusModal{{ $status->id }}" title="Edit Status: {{ $status->name }}" submitText="Save Changes" size="modal-md"
            formAction="{{ route('admin.settings.status.update', $status->id) }}">
            <div class="mb-3">
                <label class="form-label text-high fw-semibold">Status Name</label>
                <input type="text" name="name" id="edit_status_name_{{ $status->id }}" class="form-premium-control w-100" value="{{ $status->name }}" required
                    oninput="document.getElementById('edit_status_slug_{{ $status->id }}').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); updateStatusPreview('edit_{{ $status->id }}');">
            </div>

            <div class="mb-3">
                <label class="form-label text-high fw-semibold">Slug</label>
                <input type="text" name="slug" id="edit_status_slug_{{ $status->id }}" class="form-premium-control w-100 font-monospace small" value="{{ $status->slug }}" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label text-high fw-semibold">Pipeline Order</label>
                    <input type="number" name="order" class="form-premium-control w-100" value="{{ $status->order }}" min="0">
                </div>
                <div class="col-6 d-flex flex-column justify-content-center pt-3">
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="edit_status_default_{{ $status->id }}" {{ $status->is_default ? 'checked' : '' }}>
                        <label class="form-check-label text-high fw-semibold small" for="edit_status_default_{{ $status->id }}">
                            Set as Default
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-high fw-semibold mb-2">Status Color</label>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="color" name="color" id="edit_status_color_{{ $status->id }}" class="form-control-color border-main rounded-2"
                        value="{{ $hex }}" style="width: 44px; height: 38px; padding: 2px; cursor: pointer;"
                        onchange="document.getElementById('edit_status_hex_{{ $status->id }}').value = this.value; updateStatusPreview('edit_{{ $status->id }}');">
                    <input type="text" id="edit_status_hex_{{ $status->id }}" class="form-premium-control font-monospace small" value="{{ $hex }}" style="width: 110px;"
                        oninput="document.getElementById('edit_status_color_{{ $status->id }}').value = this.value; updateStatusPreview('edit_{{ $status->id }}');">
                </div>

                <!-- Quick Palette -->
                <div class="d-flex flex-wrap gap-2 pt-1">
                    @foreach($presetColors as $c)
                        <button type="button" class="btn p-0 border rounded-circle" style="width: 24px; height: 24px; background-color: {{ $c }};"
                            onclick="setStatusColor('edit_{{ $status->id }}', '{{ $c }}')" title="{{ $c }}"></button>
                    @endforeach
                </div>
            </div>

            <div class="mb-2 p-3 rounded-2 bg-subtle border border-subtle">
                <label class="text-low small fw-semibold d-block mb-2">Live Badge Preview</label>
                <span id="edit_{{ $status->id }}_status_preview" class="badge-premium px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                      style="background: {{ $hex }}1a; color: {{ $hex }}; border: 1px solid {{ $hex }}40; font-size: 0.85rem;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $hex }}; display: inline-block;"></span>
                    {{ $status->name }}
                </span>
            </div>
        </x-modal>

        <!-- Delete Status Modal -->
        <x-modal id="deleteStatusModal{{ $status->id }}" title="Delete Status"
            submitText="{{ $isDeletable ? 'Confirm Delete' : null }}"
            variant="danger"
            method="DELETE"
            formAction="{{ $isDeletable ? route('admin.settings.status.delete', $status->id) : null }}">
            <div class="text-center py-3">
                @if ($status->is_default)
                    <div class="text-warning mb-3">
                        <i class="fas fa-shield-alt fa-3x"></i>
                    </div>
                    <h5 class="text-high fw-semibold">Cannot Delete Default Status</h5>
                    <p class="text-low mb-0">
                        "{{ $status->name }}" is set as the default status for incoming tasks. Please designate another status as default before deleting this one.
                    </p>
                @elseif ($status->tasks_count > 0)
                    <div class="text-warning mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h5 class="text-high fw-semibold">Status In Use</h5>
                    <p class="text-low mb-0">
                        Cannot delete status "{{ $status->name }}" because it is currently assigned to <strong>{{ $status->tasks_count }}</strong> task(s). Reassign those tasks to another status first.
                    </p>
                @else
                    <div class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h5 class="text-high fw-semibold">Delete Status "{{ $status->name }}"?</h5>
                    <p class="text-low mb-0">
                        Are you sure you want to delete this status? This action cannot be undone.
                    </p>
                @endif
            </div>
            @if (!$isDeletable)
                <x-slot:footer>
                    <button type="button" class="btn-premium btn-premium-secondary py-2 px-4" data-bs-dismiss="modal" style="font-size: 0.85rem;">Close</button>
                </x-slot:footer>
            @endif
        </x-modal>
    @endforeach

    @push('scripts')
    <script>
        function setStatusColor(prefix, color) {
            const colorInput = document.getElementById(`${prefix}_status_color`);
            const hexInput = document.getElementById(`${prefix}_status_hex`);
            if (colorInput) colorInput.value = color;
            if (hexInput) hexInput.value = color;
            updateStatusPreview(prefix);
        }

        function updateStatusPreview(prefix) {
            const nameInput = document.getElementById(`${prefix}_status_name`);
            const colorInput = document.getElementById(`${prefix}_status_color`);
            const preview = document.getElementById(`${prefix}_status_preview`);

            if (preview && nameInput && colorInput) {
                const name = nameInput.value.trim() || 'Preview Status';
                const color = colorInput.value || '#6366f1';
                preview.style.background = `${color}1a`;
                preview.style.color = color;
                preview.style.border = `1px solid ${color}40`;
                preview.innerHTML = `<span style="width: 7px; height: 7px; border-radius: 50%; background: ${color}; display: inline-block;"></span> ${name}`;
            }
        }

        function submitBulkStatusAction(action) {
            const checked = document.querySelectorAll('.status-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one status.');
                return;
            }
            if (confirm(`Are you sure you want to delete ${checked.length} selected status(es)?`)) {
                document.getElementById('bulkStatusActionType').value = action;
                document.getElementById('bulkStatusForm').submit();
            }
        }

        function updateBulkStatusState() {
            const checkboxes = document.querySelectorAll('.status-checkbox:not(:disabled)');
            const checked = document.querySelectorAll('.status-checkbox:checked');
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

        function deselectAllStatuses() {
            document.querySelectorAll('.status-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.checked = false;
            updateBulkStatusState();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.status-checkbox:not(:disabled)').forEach(cb => cb.checked = selectAll.checked);
                    updateBulkStatusState();
                });
            }

            document.querySelectorAll('.status-checkbox').forEach(cb => {
                cb.addEventListener('change', updateBulkStatusState);
            });
        });
    </script>
    @endpush
</x-admin>
