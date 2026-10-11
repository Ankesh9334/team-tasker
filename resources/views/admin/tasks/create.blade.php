<x-admin>
    <x-slot:title>
        Create Task | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Create New Task</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Add a new task to the system.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="createTaskForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                Create Task
            </button>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <form id="createTaskForm" action="{{ route('store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <label for="title" class="heading-label d-block mb-2 text-high">Task Title</label>
                        <input type="text" class="form-premium-control w-100"
                            name="title" placeholder="e.g. Design Landing Page" required>
                        @error('title')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="heading-label d-block mb-2 text-high">Description</label>
                        <x-textarea id="mytextarea" name="description" class="form-premium-control w-100" placeholder="Detailed description of the task..."
                            texteditor="true"></x-textarea>
                        @error('description')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Project</label>
                            <x-select name="project_id" placeholder="None (Standalone Task)">
                                <option value="" class="bg-dark">None (Standalone Task)</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" class="bg-dark">{{ $project->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Parent Task (for Subtasks)</label>
                            <x-select name="parent_id" placeholder="None (Top-Level Task)">
                                <option value="" class="bg-dark">None (Top-Level Task)</option>
                                @foreach ($parentTasks as $ptask)
                                    <option value="{{ $ptask->id }}"
                                        {{ ($selectedParentId ?? null) == $ptask->id ? 'selected' : '' }}
                                        class="bg-dark">#{{ $ptask->id }} - {{ $ptask->title }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Dependencies (Blockers)</label>
                            <x-multiselect id="dependencies" name="dependencies[]" placeholder="Select Dependencies">
                                @foreach ($allTasks as $atask)
                                    <option value="{{ $atask->id }}" class="bg-dark">#{{ $atask->id }} -
                                        {{ $atask->title }}</option>
                                @endforeach
                            </x-multiselect>
                            <div class="text-low extra-small mt-1">Hold Ctrl/Cmd to select multiple</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Attachments</label>
                        <input type="file" name="attachments[]"
                            class="form-premium-control w-100" multiple>
                        <div class="text-low extra-small mt-1">Max 10MB per file. Multiple files allowed.</div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="p-4" style="background: rgba(var(--primary-rgb), 0.02); border: 1px solid var(--border-main); border-radius: var(--radius-md);">
                        <div class="mb-4">
                            <label for="assigned_to" class="heading-label d-block mb-2 text-high">Assigned To</label>
                            <x-select id="assigned_to" name="assigned_to" placeholder="Unassigned">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" class="bg-dark">{{ $user->name }}
                                        ({{ $user->role->name ?? 'No Role' }})
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Additional Assignees</label>
                            <x-multiselect id="additional_users" name="additional_users[]" placeholder="Select Team Members">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" class="bg-dark">{{ $user->name }}</option>
                                @endforeach
                            </x-multiselect>
                        </div>

                        <div class="mb-4">
                            <label for="status_id" class="heading-label d-block mb-2 text-high">Status</label>
                            <x-select id="status_id" name="status_id" placeholder="Select Status" required="true">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" {{ $status->is_default ? 'selected' : '' }}
                                        class="bg-dark">
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Priority</label>
                            <x-select id="priority" name="priority" placeholder="Select Priority">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority }}" {{ $priority == 'Medium' ? 'selected' : '' }}
                                        class="bg-dark">{{ $priority }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Tags</label>
                            <x-multiselect id="tags" name="tags[]" placeholder="Select Tags">
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}" class="bg-dark">{{ $tag->name }}</option>
                                @endforeach
                            </x-multiselect>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Estimated Hours</label>
                            <input type="number" step="0.5" name="estimated_hours"
                                class="form-premium-control w-100" placeholder="e.g. 5.5">
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Deadline</label>
                            <input type="datetime-local" name="deadline"
                                class="form-premium-control w-100">
                        </div>

                        <hr style="border-color: var(--border-main);">

                        <div class="form-check form-switch mb-3 mt-4">
                            <input class="form-check-input" type="checkbox" name="is_recurring" value="1"
                                id="recurSwitch" onchange="toggleRecur()">
                            <label class="form-check-label text-high fw-medium" for="recurSwitch">Recurring Task</label>
                        </div>

                        <div id="recurSettings" style="display: none;">
                            <x-select name="recurring_interval" placeholder="Select Interval">
                                <option value="daily" class="bg-dark">Daily</option>
                                <option value="weekly" class="bg-dark">Weekly</option>
                                <option value="monthly" class="bg-dark">Monthly</option>
                                <option value="yearly" class="bg-dark">Yearly</option>
                            </x-select>
                        </div>
                    </div>
                </div>
            </div>


        </form>
    </div>

    <script>
        function toggleRecur() {
            const settings = document.getElementById('recurSettings');
            const isChecked = document.getElementById('recurSwitch').checked;
            settings.style.display = isChecked ? 'block' : 'none';
        }
    </script>


    <script>
        setTimeout(function() {
            $('.alert-success,.alert-danger').fadeOut('fast');
        }, 3000);


        feather.replace();

        document.getElementById('templateSelector').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) return;

            const structure = JSON.parse(selectedOption.getAttribute('data-structure'));

            if (structure.title) document.getElementsByName('title')[0].value = structure.title;

            if (structure.priority) {
                const el = document.getElementsByName('priority')[0];
                el.value = structure.priority;
                if (el.tomselect) el.tomselect.sync();
            }

            if (structure.estimated_hours) document.getElementsByName('estimated_hours')[0].value = structure
                .estimated_hours;

            if (structure.description) {
                tinymce.get('mytextarea').setContent(structure.description);
            }

            if (structure.tags && Array.isArray(structure.tags)) {
                const tagSelect = document.getElementsByName('tags[]')[0];
                if (tagSelect) {
                    const values = structure.tags.map(t => String(t));
                    if (tagSelect.tomselect) {
                        tagSelect.tomselect.setValue(values);
                    } else {
                        Array.from(tagSelect.options).forEach(option => {
                            option.selected = values.includes(option.value);
                        });
                    }
                }
            }
        });
    </script>
</x-admin>


