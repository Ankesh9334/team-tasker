<x-admin title="Create Project">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Create New Project</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Add a new project to the system.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="createProjectForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                Create Project
            </button>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        <form id="createProjectForm" action="{{ route('admin.projects.store') }}" method="POST">
            @csrf
            
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <label class="heading-label d-block mb-2 text-high">Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-premium-control w-100" required placeholder="E.g. Website Redesign">
                    @error('name')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Status</label>
                    <x-select name="status" placeholder="Select Status">
                        <option value="active" class="bg-dark" selected>Active</option>
                        <option value="on_hold" class="bg-dark">On Hold</option>
                        <option value="completed" class="bg-dark">Completed</option>
                    </x-select>
                </div>
            </div>

            <div class="mb-4">
                <label class="heading-label d-block mb-2 text-high">Description</label>
                <x-textarea id="mytextarea" name="description" class="form-premium-control w-100" placeholder="Detailed description of the project..." texteditor="true"></x-textarea>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Project Managers</label>
                    <x-multiselect name="user_ids[]" placeholder="Select Managers">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" class="bg-dark">{{ $user->name }}</option>
                        @endforeach
                    </x-multiselect>
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Start Date</label>
                    <input type="date" name="start_date" class="form-premium-control w-100">
                </div>
                <div class="col-md-4">
                    <label class="heading-label d-block mb-2 text-high">Deadline</label>
                    <input type="date" name="deadline" class="form-premium-control w-100">
                </div>
            </div>
        </form>
    </div>
    
    <script>
        setTimeout(function() {
            $('.alert-success,.alert-danger').fadeOut('fast');
        }, 3000);
        feather.replace();
    </script>
</x-admin>
