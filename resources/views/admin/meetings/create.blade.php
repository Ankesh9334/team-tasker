<x-admin title="Create Project Meeting | Team Tasker">
    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Create New Meeting</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Setup audio/video meeting for your project, task, or team members.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.meetings.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="createMeetingForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-check me-1"></i> Create Meeting
            </button>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <form id="createMeetingForm" action="{{ route('admin.meetings.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-high">Meeting Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-premium-control w-100 @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Project Sprint Sync" required>
                @error('title') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-high">Description</label>
                <textarea name="description" class="form-premium-control w-100 @error('description') is-invalid @enderror" rows="3" placeholder="Agenda or notes for the meeting...">{{ old('description') }}</textarea>
                @error('description') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Meeting Type <span class="text-danger">*</span></label>
                    <x-select name="type" placeholder="Select Meeting Type" required>
                        <option value="scheduled_meeting" class="bg-dark" {{ old('type') == 'scheduled_meeting' ? 'selected' : '' }}>Scheduled Meeting</option>
                        <option value="project_meeting" class="bg-dark" {{ (old('type') == 'project_meeting' || $selectedProjectId) ? 'selected' : '' }}>Project Meeting</option>
                        <option value="task_meeting" class="bg-dark" {{ (old('type') == 'task_meeting' || $selectedTaskId) ? 'selected' : '' }}>Task Meeting</option>
                        <option value="group_call" class="bg-dark" {{ old('type') == 'group_call' ? 'selected' : '' }}>Group Call</option>
                    </x-select>
                    @error('type') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Meeting Mode <span class="text-danger">*</span></label>
                    <x-select name="mode" placeholder="Select Meeting Mode" required>
                        <option value="video" class="bg-dark" {{ old('mode', 'video') == 'video' ? 'selected' : '' }}>📹 Video Call</option>
                        <option value="audio" class="bg-dark" {{ old('mode') == 'audio' ? 'selected' : '' }}>📞 Audio Call</option>
                    </x-select>
                    @error('mode') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Project (Optional)</label>
                    <x-select name="project_id" placeholder="-- Select Project --">
                        <option value="" class="bg-dark">-- None --</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" class="bg-dark" {{ (old('project_id', $selectedProjectId) == $project->id) ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Task (Optional)</label>
                    <x-select name="task_id" placeholder="-- Select Task --">
                        <option value="" class="bg-dark">-- None --</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}" class="bg-dark" {{ (old('task_id', $selectedTaskId) == $task->id) ? 'selected' : '' }}>
                                {{ $task->title }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Scheduled Date & Time</label>
                    <input type="datetime-local" name="scheduled_at" class="form-premium-control w-100 @error('scheduled_at') is-invalid @enderror" value="{{ old('scheduled_at') }}">
                    <span class="text-low" style="font-size: 0.8rem;">Leave empty to start immediately.</span>
                    @error('scheduled_at') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Estimated Duration (Minutes)</label>
                    <input type="number" name="duration" class="form-premium-control w-100" min="5" max="1440" value="{{ old('duration', 30) }}">
                </div>
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-high">Invite Participants</label>
                <x-multiselect name="participant_ids[]" placeholder="Select Team Members to Invite">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" class="bg-dark" {{ (is_array(old('participant_ids')) && in_array($user->id, old('participant_ids'))) ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </x-multiselect>
            </div>
        </form>
    </div>
</x-admin>
