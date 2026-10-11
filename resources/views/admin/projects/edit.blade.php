<x-admin title="Edit Project">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Edit Project: {{ Str::limit($project->name, 40) }}</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Modify the details of your project.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-premium btn-premium-secondary px-4 py-2 text-danger border-danger" style="background: rgba(var(--danger-rgb), 0.1);" onclick="if(confirm('Are you sure you want to delete this project?')) { document.getElementById('delete-form').submit(); }">
                Delete Project
            </button>
            <a href="{{ route('admin.projects.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="editProjectForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                Save Changes
            </button>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <form id="editProjectForm" action="{{ route('admin.projects.update', $project->id) }}" method="POST">
            @csrf
            
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <label class="heading-label d-block mb-2 text-high">Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-premium-control w-100" value="{{ $project->name }}" required>
                    @error('name')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Status</label>
                    <x-select name="status" placeholder="Select Status" :selected="$project->status">
                        <option value="active" class="bg-dark">Active</option>
                        <option value="on_hold" class="bg-dark">On Hold</option>
                        <option value="completed" class="bg-dark">Completed</option>
                        <option value="archived" class="bg-dark">Archived</option>
                    </x-select>
                </div>
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-high">Description</label>
                <x-textarea id="mytextarea" name="description" class="form-premium-control w-100" texteditor="true">{{ $project->description }}</x-textarea>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Project Managers</label>
                    <x-multiselect name="user_ids[]" placeholder="Select Managers" :selected="$project->users->pluck('id')->toArray()">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" class="bg-dark">{{ $user->name }}</option>
                        @endforeach
                    </x-multiselect>
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Start Date</label>
                    <input type="date" name="start_date" class="form-premium-control w-100" value="{{ $project->start_date ? $project->start_date->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Deadline</label>
                    <input type="date" name="deadline" class="form-premium-control w-100" value="{{ $project->deadline ? $project->deadline->format('Y-m-d') : '' }}">
                </div>
            </div>
        </form>
        
        <form id="delete-form" action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
    
    <script>
        setTimeout(function() {
            $('.alert-success,.alert-danger').fadeOut('fast');
        }, 3000);
        feather.replace();
    </script>
</x-admin>
