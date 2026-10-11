<x-admin title="Projects">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Projects</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage groups of tasks efficiently.</p>
        </div>
        <div class="d-flex gap-3">
            @if(Auth::user()->hasPermission('projects.create'))
                <a href="{{ route('admin.projects.create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> New Project
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @livewire('project-list')
</x-admin>
