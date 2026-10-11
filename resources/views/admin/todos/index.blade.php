<x-admin title="My Personal To-Do List">
    <div class="top-bar-premium d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high d-flex align-items-center gap-2">
                <i class="fas fa-clipboard-check text-primary" style="font-size: 1.4rem;"></i>
                My Personal To-Do
            </h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Manage your private action items, checklist notes, and daily goals.</p>
        </div>
        <div>
            <button type="button" class="btn-premium btn-premium-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#todoModal" onclick="Livewire.dispatch('open-todo-modal')">
                <i class="fas fa-plus-circle me-1"></i> Add Task
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @livewire('todo-list', ['height' => '650px', 'standalone' => true])
        </div>
    </div>
</x-admin>
