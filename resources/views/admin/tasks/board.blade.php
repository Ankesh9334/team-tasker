<x-admin>
    <x-slot:title>
        Task Board | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium mb-4">
        <div class="d-flex align-items-center gap-3">
            <h1 class="h3 fw-semibold mb-0 text-high d-flex align-items-center gap-2">
                <i class="fas fa-columns text-primary" style="font-size: 1.4rem;"></i>
                Task Board
            </h1>
            <div class="ms-2 d-none d-md-flex gap-1 bg-glass border border-main p-1" style="border-radius: var(--radius-md);">
                <a href="{{ route('tasks.board') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1 active"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm);">
                    <i class="fas fa-columns me-1"></i> Kanban
                </a>
                <a href="{{ route('index') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm);">
                    <i class="fas fa-list me-1"></i> List
                </a>
                <a href="{{ route('tasks.calendar') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm);">
                    <i class="fas fa-calendar-alt me-1"></i> Calendar
                </a>
                <a href="{{ route('tasks.gantt') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm);">
                    <i class="fas fa-project-diagram me-1"></i> Gantt
                </a>
                <a href="{{ route('tasks.activity') }}"
                    class="btn-premium btn-premium-secondary btn-sm px-3 py-1"
                    style="font-size: 0.75rem; border: none; border-radius: var(--radius-sm);">
                    <i class="fas fa-history me-1"></i> Activity
                </a>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('create') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> New Task
            </a>
        </div>
    </div>

    @livewire('task-board')

</x-admin>
