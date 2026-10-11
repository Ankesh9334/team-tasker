<x-admin>
    <x-slot:title>
        Task Tag Settings | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high d-flex align-items-center gap-2">
                <i class="fas fa-tags text-primary" style="font-size: 1.4rem;"></i>
                Task Tag Settings
            </h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Configure task tags, custom categorization badges, and color-coded labels.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.statuses') }}"
                class="btn-premium btn-premium-secondary px-3 py-2 text-decoration-none">
                <i class="fas fa-sliders-h me-1"></i> Task Statuses
            </a>
            <button type="button" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm" data-bs-toggle="modal"
                data-bs-target="#createTagModal">
                <i class="fas fa-plus-circle me-1"></i> Add New Tag
            </button>
        </div>
    </div>

    <!-- Tag Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Total Tags</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                        style="width: 34px; height: 34px; background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                        <i class="fas fa-tags" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-high">{{ $tagStats['total'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">In Use</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                        style="width: 34px; height: 34px; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <i class="fas fa-check" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success">{{ $tagStats['used'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Unassigned</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                        style="width: 34px; height: 34px; background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <i class="fas fa-circle-notch" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-warning">{{ $tagStats['unused'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card p-3 h-100 border-main">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-low small fw-semibold text-uppercase tracking-wider">Tasks Labeled</span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center"
                        style="width: 34px; height: 34px; background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <i class="fas fa-tasks" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-high">{{ $tagStats['tasks_count'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Bulk Action Form -->
    <form id="bulkTagForm" action="{{ route('admin.settings.tag.bulkAction') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="bulkTagActionType">

        <div class="data-grid-wrapper mb-5">
            <div class="data-grid-top">
                <div class="data-grid-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" form="tagSearchForm"
                        placeholder="Search tags by name, slug, or color..." value="{{ request('search') }}"
                        onchange="document.getElementById('tagSearchForm').submit()">
                    @if(request('search'))
                        <a href="{{ route('admin.settings.tags') }}" class="text-low ms-2 text-decoration-none small"
                            title="Clear search">
                            <i class="fas fa-times-circle"></i>
                        </a>
                    @endif
                </div>

                <div class="data-grid-results">{{ $tags->total() }} Tags</div>

                <div class="data-grid-actions d-flex align-items-center gap-2">
                    @if(request('search'))
                        <a href="{{ route('admin.settings.tags') }}"
                            class="btn-premium btn-premium-secondary btn-sm d-flex align-items-center gap-1">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    @endif
                    {{ $tags->links('components.pagination.premium') }}
                </div>
            </div>

            <!-- Sticky Bulk Action Bar -->
            <div class="data-grid-bulk-actions d-none" id="bulkActionBar">
                <span class="text-white small fw-semibold me-2"><span id="selectedCount">0</span> selected</span>
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn-bulk-danger" onclick="submitBulkTagAction('delete')">
                        <i class="fas fa-trash-alt me-1"></i> Delete Selected
                    </button>
                </div>
                <button type="button" class="btn-deselect-all" onclick="deselectAllTags()">
                    Deselect All
                </button>
            </div>

            <div class="table-responsive">
                <table class="table data-grid-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox" id="selectAll">
                            </th>
                            <th>TAG NAME <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>SLUG <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th>COLOR CODE <i class="fas fa-sort text-low ms-1" style="font-size: 10px;"></i></th>
                            <th class="text-center">TASKS <i class="fas fa-sort text-low ms-1"
                                    style="font-size: 10px;"></i></th>
                            <th>CREATED</th>
                            <th class="text-end pe-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tags as $tag)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $tag->id }}"
                                        class="data-grid-checkbox tag-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span
                                            class="badge-premium d-inline-flex align-items-center gap-1 px-3 py-1 fw-semibold"
                                            style="background: {{ $tag->color }}1a; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40; font-size: 0.8rem; border-radius: 6px;">
                                            <span
                                                style="width: 7px; height: 7px; border-radius: 50%; background: {{ $tag->color }}; display: inline-block;"></span>
                                            {{ $tag->name }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-monospace text-low small">{{ $tag->slug }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div
                                            style="width: 22px; height: 22px; border-radius: 5px; background: {{ $tag->color }}; border: 1px solid var(--border-subtle); box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                                        </div>
                                        <span
                                            class="font-monospace small text-high fw-medium">{{ strtoupper($tag->color) }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($tag->tasks_count > 0)
                                        <span
                                            class="badge bg-primary bg-opacity-15 text-primary px-2 py-1 rounded-pill small fw-semibold">
                                            <i class="fas fa-tasks me-1" style="font-size: 0.7rem;"></i>{{ $tag->tasks_count }}
                                        </span>
                                    @else
                                        <span class="text-low small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-low small">
                                    {{ $tag->created_at ? $tag->created_at->format('d M Y') : '—' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="action-link border-0 bg-transparent" title="Edit Tag"
                                            data-bs-toggle="modal" data-bs-target="#editTagModal{{ $tag->id }}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button type="button" class="action-link delete border-0 bg-transparent"
                                            title="Delete Tag" data-bs-toggle="modal"
                                            data-bs-target="#deleteTagModal{{ $tag->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-low">
                                        <i class="fas fa-tags fa-3x mb-3 text-secondary opacity-50"></i>
                                        <p class="mb-0">No tags found matching your query.</p>
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
    <form action="{{ route('admin.settings.tags') }}" method="GET" id="tagSearchForm" class="d-none">
    </form>

    <!-- Create Tag Modal -->
    <x-modal id="createTagModal" title="Create New Tag" submitText="Add Tag" size="modal-md"
        formAction="{{ route('admin.settings.tag.store') }}">
        <div class="mb-3">
            <label class="form-label text-high fw-semibold">Tag Name</label>
            <input type="text" name="name" id="create_tag_name" class="form-premium-control w-100"
                placeholder="e.g. Critical, Frontend, Backend, Urgent" required
                oninput="document.getElementById('create_tag_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); updateTagPreview('create');">
        </div>

        <div class="mb-3">
            <label class="form-label text-high fw-semibold">Slug (Auto-generated)</label>
            <input type="text" name="slug" id="create_tag_slug" class="form-premium-control w-100 font-monospace small"
                placeholder="e.g. critical" required>
        </div>

        <div class="mb-3">
            <label class="form-label text-high fw-semibold mb-2">Tag Color</label>
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="color" name="color" id="create_tag_color" class="form-control-color border-main rounded-2"
                    value="#3b82f6" style="width: 44px; height: 38px; padding: 2px; cursor: pointer;"
                    onchange="document.getElementById('create_tag_hex').value = this.value; updateTagPreview('create');">
                <input type="text" id="create_tag_hex" class="form-premium-control font-monospace small" value="#3b82f6"
                    style="width: 110px;"
                    oninput="document.getElementById('create_tag_color').value = this.value; updateTagPreview('create');">
            </div>

            <!-- Quick Preset Color Palette -->
            <div class="d-flex flex-wrap gap-2 pt-1">
                @php
                    $presetColors = ['#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#ec4899', '#ef4444', '#f97316', '#f59e0b', '#10b981', '#14b8a6', '#06b6d4', '#64748b'];
                @endphp
                @foreach($presetColors as $color)
                    <button type="button" class="btn p-0 border rounded-circle"
                        style="width: 24px; height: 24px; background-color: {{ $color }};"
                        onclick="setTagColor('create', '{{ $color }}')" title="{{ $color }}"></button>
                @endforeach
            </div>
        </div>

        <div class="mb-2 p-3 rounded-2 bg-subtle border border-subtle">
            <label class="text-low small fw-semibold d-block mb-2">Live Badge Preview</label>
            <span id="create_tag_preview"
                class="badge-premium px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                style="background: #3b82f61a; color: #3b82f6; border: 1px solid #3b82f640; font-size: 0.85rem;">
                <span
                    style="width: 7px; height: 7px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                New Tag
            </span>
        </div>
    </x-modal>

    <!-- Edit and Delete Modals for each tag -->
    @foreach ($tags as $tag)
        <!-- Edit Tag Modal -->
        <x-modal id="editTagModal{{ $tag->id }}" title="Edit Tag: {{ $tag->name }}" submitText="Save Changes"
            size="modal-md" formAction="{{ route('admin.settings.tag.update', $tag->id) }}">
            <div class="mb-3">
                <label class="form-label text-high fw-semibold">Tag Name</label>
                <input type="text" name="name" id="edit_tag_name_{{ $tag->id }}" class="form-premium-control w-100"
                    value="{{ $tag->name }}" required
                    oninput="document.getElementById('edit_tag_slug_{{ $tag->id }}').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); updateTagPreview('edit_{{ $tag->id }}');">
            </div>

            <div class="mb-3">
                <label class="form-label text-high fw-semibold">Slug</label>
                <input type="text" name="slug" id="edit_tag_slug_{{ $tag->id }}"
                    class="form-premium-control w-100 font-monospace small" value="{{ $tag->slug }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-high fw-semibold mb-2">Tag Color</label>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="color" name="color" id="edit_tag_color_{{ $tag->id }}"
                        class="form-control-color border-main rounded-2" value="{{ $tag->color }}"
                        style="width: 44px; height: 38px; padding: 2px; cursor: pointer;"
                        onchange="document.getElementById('edit_tag_hex_{{ $tag->id }}').value = this.value; updateTagPreview('edit_{{ $tag->id }}');">
                    <input type="text" id="edit_tag_hex_{{ $tag->id }}" class="form-premium-control font-monospace small"
                        value="{{ $tag->color }}" style="width: 110px;"
                        oninput="document.getElementById('edit_tag_color_{{ $tag->id }}').value = this.value; updateTagPreview('edit_{{ $tag->id }}');">
                </div>

                <!-- Quick Palette -->
                <div class="d-flex flex-wrap gap-2 pt-1">
                    @foreach($presetColors as $color)
                        <button type="button" class="btn p-0 border rounded-circle"
                            style="width: 24px; height: 24px; background-color: {{ $color }};"
                            onclick="setTagColor('edit_{{ $tag->id }}', '{{ $color }}')" title="{{ $color }}"></button>
                    @endforeach
                </div>
            </div>

            <div class="mb-2 p-3 rounded-2 bg-subtle border border-subtle">
                <label class="text-low small fw-semibold d-block mb-2">Live Badge Preview</label>
                <span id="edit_{{ $tag->id }}_tag_preview"
                    class="badge-premium px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                    style="background: {{ $tag->color }}1a; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40; font-size: 0.85rem;">
                    <span
                        style="width: 7px; height: 7px; border-radius: 50%; background: {{ $tag->color }}; display: inline-block;"></span>
                    {{ $tag->name }}
                </span>
            </div>
        </x-modal>

        <!-- Delete Tag Modal -->
        <x-modal id="deleteTagModal{{ $tag->id }}" title="Delete Tag" submitText="Confirm Delete" isDelete="true"
            formAction="{{ route('admin.settings.tag.delete', $tag->id) }}">
            @method('DELETE')
            <div class="text-center py-3">
                <div class="text-danger mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h5 class="text-high fw-semibold">Delete Tag "{{ $tag->name }}"?</h5>
                <p class="text-low mb-0">
                    Are you sure you want to delete this tag? It will be safely detached from all associated tasks
                    ({{ $tag->tasks_count }} currently). This action cannot be undone.
                </p>
            </div>
        </x-modal>
    @endforeach

    @push('scripts')
        <script>
            function setTagColor(prefix, color) {
                const colorInput = document.getElementById(`${prefix}_tag_color`);
                const hexInput = document.getElementById(`${prefix}_tag_hex`);
                if (colorInput) colorInput.value = color;
                if (hexInput) hexInput.value = color;
                updateTagPreview(prefix);
            }

            function updateTagPreview(prefix) {
                const nameInput = document.getElementById(`${prefix}_tag_name`);
                const colorInput = document.getElementById(`${prefix}_tag_color`);
                const preview = document.getElementById(`${prefix}_tag_preview`);

                if (preview && nameInput && colorInput) {
                    const name = nameInput.value.trim() || 'Preview Tag';
                    const color = colorInput.value || '#3b82f6';
                    preview.style.background = `${color}1a`;
                    preview.style.color = color;
                    preview.style.border = `1px solid ${color}40`;
                    preview.innerHTML = `<span style="width: 7px; height: 7px; border-radius: 50%; background: ${color}; display: inline-block;"></span> ${name}`;
                }
            }

            function submitBulkTagAction(action) {
                const checked = document.querySelectorAll('.tag-checkbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one tag.');
                    return;
                }
                if (confirm(`Are you sure you want to delete ${checked.length} selected tag(s)?`)) {
                    document.getElementById('bulkTagActionType').value = action;
                    document.getElementById('bulkTagForm').submit();
                }
            }

            function updateBulkTagState() {
                const checkboxes = document.querySelectorAll('.tag-checkbox');
                const checked = document.querySelectorAll('.tag-checkbox:checked');
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

            function deselectAllTags() {
                document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = false);
                const selectAll = document.getElementById('selectAll');
                if (selectAll) selectAll.checked = false;
                updateBulkTagState();
            }

            document.addEventListener('DOMContentLoaded', function () {
                const selectAll = document.getElementById('selectAll');
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = selectAll.checked);
                        updateBulkTagState();
                    });
                }

                document.querySelectorAll('.tag-checkbox').forEach(cb => {
                    cb.addEventListener('change', updateBulkTagState);
                });
            });
        </script>
    @endpush
</x-admin>