<x-admin>
    <x-slot:title>
        Edit Task | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Edit Task: {{ Str::limit($task->title, 40) }}</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Modify the details of your task.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="editTaskForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                Update Task
            </button>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <form id="editTaskForm" action="{{ route('update', $task->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <label for="title" class="heading-label d-block mb-2 text-high">Task Title</label>
                        <input type="text" class="form-premium-control w-100"
                            name="title" value="{{ $task->title }}" required>
                        @error('title')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="heading-label d-block mb-2 text-high">Description</label>
                        <x-textarea id="mytextarea" name="description" class="form-premium-control w-100"
                            texteditor="true">{{ $task->description }}</x-textarea>
                        @error('description')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Project</label>
                            <x-select name="project_id" placeholder="None (Standalone Task)">
                                <option value="" class="bg-dark">None (Standalone Task)</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" class="bg-dark" {{ $task->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Parent Task (for Subtasks)</label>
                            <x-select name="parent_id" placeholder="None (Top-Level Task)">
                                <option value="" class="bg-dark">None (Top-Level Task)</option>
                                @foreach ($parentTasks as $ptask)
                                    <option value="{{ $ptask->id }}"
                                        {{ $task->parent_id == $ptask->id ? 'selected' : '' }} class="bg-dark">
                                        #{{ $ptask->id }} - {{ $ptask->title }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <label class="heading-label d-block mb-2 text-high">Dependencies (Blockers)</label>
                            @php $depIds = $task->dependencies->pluck('depends_on_id')->toArray(); @endphp
                            <x-multiselect name="dependencies[]" placeholder="Select Dependencies" :selected="$depIds">
                                @foreach ($allTasks as $atask)
                                    <option value="{{ $atask->id }}"
                                        {{ in_array($atask->id, $depIds) ? 'selected' : '' }} class="bg-dark">
                                        #{{ $atask->id }} - {{ $atask->title }}</option>
                                @endforeach
                            </x-multiselect>
                            <div class="text-low extra-small mt-1">Hold Ctrl/Cmd to select multiple</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Attachments</label>
                        @if ($task->attachments->count() > 0)
                            <div class="mb-2">
                                <label class="extra-small text-low">Current Attachments:</label>
                                <ul class="list-unstyled">
                                    @foreach ($task->attachments as $attachment)
                                        <li class="extra-small">
                                            <i class="fas fa-paperclip me-1"></i>
                                            <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                                class="text-primary">{{ $attachment->file_name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <input type="file" name="attachments[]"
                            class="form-premium-control w-100" multiple>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="p-4" style="background: rgba(var(--primary-rgb), 0.02); border: 1px solid var(--border-main); border-radius: var(--radius-md);">
                        <div class="mb-4">
                            <label for="assigned_to" class="heading-label d-block mb-2 text-high">Assigned To</label>
                            <x-select id="assigned_to" name="assigned_to" placeholder="Unassigned" :selected="$task->assigned_to">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" class="bg-dark">
                                        {{ $user->name }} ({{ $user->role->name ?? 'No Role' }})
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Additional Assignees</label>
                            <x-multiselect id="additional_users" name="additional_users[]" placeholder="Select Team Members" :selected="$taskUsers ?? []">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" class="bg-dark">{{ $user->name }}</option>
                                @endforeach
                            </x-multiselect>
                        </div>

                        <div class="mb-4">
                            <label for="status_id" class="heading-label d-block mb-2 text-high">Status</label>
                            <x-select id="status_id" name="status_id" placeholder="Select Status" required="true"
                                :selected="$task->status_id">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" class="bg-dark">
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Priority</label>
                            <x-select id="priority" name="priority" placeholder="Select Priority" :selected="$task->priority">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority }}" class="bg-dark">
                                        {{ $priority }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Tags</label>
                            <x-multiselect id="tags" name="tags[]" placeholder="Select Tags" :selected="$task->tags->pluck('id')->toArray()">
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}" class="bg-dark">
                                        {{ $tag->name }}</option>
                                @endforeach
                            </x-multiselect>
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Estimated Hours</label>
                            <input type="number" step="0.5" name="estimated_hours"
                                value="{{ $task->estimated_hours }}"
                                class="form-premium-control w-100" placeholder="e.g. 5.5">
                        </div>

                        <div class="mb-4">
                            <label class="heading-label d-block mb-2 text-high">Deadline</label>
                            <input type="datetime-local" name="deadline"
                                value="{{ $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '' }}"
                                class="form-premium-control w-100">
                        </div>

                        <hr style="border-color: var(--border-main);">

                        <div class="form-check form-switch mb-3 mt-4">
                            <input class="form-check-input" type="checkbox" name="is_recurring" value="1"
                                id="recurSwitch" {{ $task->is_recurring ? 'checked' : '' }} onchange="toggleRecur()">
                            <label class="form-check-label text-high fw-medium" for="recurSwitch">Recurring Task</label>
                        </div>

                        <div id="recurSettings" style="{{ $task->is_recurring ? '' : 'display: none;' }}">
                            <x-select name="recurring_interval" placeholder="Select Interval">
                                <option value="daily" {{ $task->recurring_interval == 'daily' ? 'selected' : '' }}
                                    class="bg-dark">Daily</option>
                                <option value="weekly" {{ $task->recurring_interval == 'weekly' ? 'selected' : '' }}
                                    class="bg-dark">Weekly</option>
                                <option value="monthly" {{ $task->recurring_interval == 'monthly' ? 'selected' : '' }}
                                    class="bg-dark">Monthly</option>
                                <option value="yearly" {{ $task->recurring_interval == 'yearly' ? 'selected' : '' }}
                                    class="bg-dark">Yearly</option>
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


        feather.replace()
    </script>
</x-admin>


