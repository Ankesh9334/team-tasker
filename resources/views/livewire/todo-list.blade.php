<?php

use Livewire\Volt\Component;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $title = '';
    public $height = '250px';
    public $standalone = false;
    
    public $search = '';
    public $status = ''; // '' = all, 'pending', 'completed'
    public $targetUserId = null;

    public $modalTitle = '';
    public $modalId = null;

    public function mount($height = null, $targetUserId = null, $standalone = null)
    {
        if ($height) $this->height = $height;
        if ($targetUserId) $this->targetUserId = $targetUserId;
        $this->standalone = $standalone !== null ? $standalone : request()->routeIs('admin.todos.index');
    }

    private function getUserId() 
    {
        return $this->targetUserId ?: Auth::id();
    }
    
    private function getUserType()
    {
        if ($this->targetUserId) {
            return 'web'; 
        }
        return Auth::guard('admin')->check() ? 'admin' : 'web';
    }

    public function setStatusFilter($status)
    {
        $this->status = ($this->status === $status) ? '' : $status;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->targetUserId = null;
    }

    public function with()
    {
        $baseQuery = Todo::where('user_id', $this->getUserId())
            ->where('user_type', $this->getUserType());

        $totalCount = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->where('is_completed', false)->count();
        $completedCount = (clone $baseQuery)->where('is_completed', true)->count();

        $todos = (clone $baseQuery)
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->status === 'pending', fn($q) => $q->where('is_completed', false))
            ->when($this->status === 'completed', fn($q) => $q->where('is_completed', true))
            ->orderBy('is_completed')
            ->orderBy('updated_at', 'desc')
            ->get();

        $canViewOtherUsers = Auth::user() && (
            Auth::user()->hasRole('super-admin') || 
            Auth::user()->hasRole('admin') || 
            Auth::user()->hasPermission('tasks.view_all') ||
            Auth::user()->hasPermission('tasks.todo')
        );

        $users = $canViewOtherUsers 
            ? User::select('id', 'name', 'email')->where('role_id', '!=', 3)->orderBy('name')->get() 
            : collect();

        $activeUser = null;
        if ($this->targetUserId) {
            $activeUser = User::find($this->targetUserId);
        }

        return [
            'todos' => $todos,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
            'users' => $users,
            'canViewOtherUsers' => $canViewOtherUsers,
            'activeUser' => $activeUser,
        ];
    }

    public function toggleTodo($id)
    {
        $todo = Todo::where('user_id', $this->getUserId())->where('user_type', $this->getUserType())->findOrFail($id);
        $todo->update(['is_completed' => !$todo->is_completed]);
    }

    public function deleteTodo($id)
    {
        Todo::where('user_id', $this->getUserId())->where('user_type', $this->getUserType())->findOrFail($id)->delete();
    }

    #[\Livewire\Attributes\On('open-todo-modal')]
    public function openAddModal()
    {
        $this->modalId = null;
        $this->modalTitle = '';
    }

    public function openEditModal($id, $title)
    {
        $this->modalId = $id;
        $this->modalTitle = $title;
    }
    
    public function saveTodo()
    {
        $this->validate(['modalTitle' => 'required|string|max:255']);
        
        if ($this->modalId) {
            $todo = Todo::where('user_id', $this->getUserId())->where('user_type', $this->getUserType())->findOrFail($this->modalId);
            $todo->update(['title' => $this->modalTitle]);
        } else {
            Todo::create([
                'user_id' => $this->getUserId(),
                'user_type' => $this->getUserType(),
                'title' => $this->modalTitle,
                'is_completed' => false,
            ]);
        }
        
        $this->modalId = null;
        $this->modalTitle = '';
        $this->dispatch('close-todo-modal');
    }
};
?>

<div class="h-100">
    @if($standalone)
        <!-- Clickable Metric Cards -->
        <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
            <div class="col">
                <div class="glass-card p-3 border-main cursor-pointer transition-all {{ $status === '' ? 'border-primary shadow-sm' : '' }}"
                     wire:click="setStatusFilter('')"
                     style="background: {{ $status === '' ? 'rgba(var(--primary-rgb), 0.08)' : 'var(--bg-surface)' }}; border-radius: var(--radius-md);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-low d-block fw-medium" style="font-size: 0.78rem;">TOTAL TO-DOS</span>
                            <h3 class="fw-bold mb-0 text-high mt-1">{{ $totalCount }}</h3>
                        </div>
                        <div class="stat-icon-premium icon-primary-premium" style="width: 42px; height: 42px;">
                            <i class="fas fa-clipboard-list" style="font-size: 1.1rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-3 border-main cursor-pointer transition-all {{ $status === 'pending' ? 'border-warning shadow-sm' : '' }}"
                     wire:click="setStatusFilter('pending')"
                     style="background: {{ $status === 'pending' ? 'rgba(var(--warning-rgb), 0.08)' : 'var(--bg-surface)' }}; border-radius: var(--radius-md);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-low d-block fw-medium" style="font-size: 0.78rem;">PENDING</span>
                            <h3 class="fw-bold mb-0 text-warning mt-1">{{ $pendingCount }}</h3>
                        </div>
                        <div class="stat-icon-premium icon-warning-premium" style="width: 42px; height: 42px;">
                            <i class="fas fa-clock" style="font-size: 1.1rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="glass-card p-3 border-main cursor-pointer transition-all {{ $status === 'completed' ? 'border-success shadow-sm' : '' }}"
                     wire:click="setStatusFilter('completed')"
                     style="background: {{ $status === 'completed' ? 'rgba(var(--accent-rgb), 0.08)' : 'var(--bg-surface)' }}; border-radius: var(--radius-md);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-low d-block fw-medium" style="font-size: 0.78rem;">COMPLETED</span>
                            <h3 class="fw-bold mb-0 text-success mt-1">{{ $completedCount }}</h3>
                        </div>
                        <div class="stat-icon-premium icon-success-premium" style="width: 42px; height: 42px;">
                            <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar using x-select -->
        <div class="glass-card p-3 mb-4 border-main d-flex flex-wrap align-items-center justify-content-between gap-3"
             style="background: var(--bg-surface); border-radius: var(--radius-lg);">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                {{-- Search Bar --}}
                <div class="position-relative" style="min-width: 240px; max-width: 320px;">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-low" style="font-size: 0.85rem;"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks..."
                           class="form-premium-control ps-5 w-100" style="height: 38px; font-size: 0.85rem;">
                    @if($search)
                        <button type="button" wire:click="$set('search', '')" class="btn btn-sm border-0 position-absolute top-50 end-0 translate-middle-y text-low me-1" style="background: transparent;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    @endif
                </div>

                {{-- Status Filter with x-select --}}
                <div style="min-width: 150px;">
                    <x-select wire:model.live="status" placeholder="All Statuses">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </x-select>
                </div>

                {{-- Employee / User Filter with x-select (if manager/admin) --}}
                @if($canViewOtherUsers && $users->isNotEmpty())
                    <div style="min-width: 220px;">
                        <x-select wire:model.live="targetUserId" placeholder="My To-Dos (Me)">
                            <option value="">My To-Dos (Me)</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </x-select>
                    </div>
                @endif

                @if($search || $status || $targetUserId)
                    <button type="button" wire:click="resetFilters" class="btn-premium btn-premium-secondary btn-sm px-3 d-flex align-items-center gap-1" style="height: 38px;">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge-premium bg-subtle text-high border-subtle px-3 py-2" style="font-size: 0.8rem;">
                    Showing&nbsp;<strong>{{ $todos->count() }}</strong>&nbsp;of&nbsp;<strong>{{ $totalCount }}</strong>&nbsp;tasks
                </span>
            </div>
        </div>
    @endif

    <div class="glass-card p-0 overflow-hidden h-100" style="border: 1px solid var(--border-main); display: flex; flex-direction: column;">
        @if($standalone)
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom border-main">
                <div>
                    <h5 class="fw-bold mb-0 text-high d-flex align-items-center gap-2">
                        <i class="fas fa-clipboard-check text-primary"></i>
                        @if($activeUser)
                            {{ $activeUser->name }}'s To-Dos
                        @else
                            Personal To-Do List
                        @endif
                    </h5>
                    <p class="mb-0 text-low" style="font-size: 0.75rem; margin-top: 2px;">
                        @if($activeUser)
                            Viewing to-dos for {{ $activeUser->email }}
                        @else
                            Private action items and personal checklists
                        @endif
                    </p>
                </div>
                <button type="button" class="btn-premium btn-premium-primary btn-sm px-3 py-1.5 d-flex align-items-center gap-1 shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#todoModal" wire:click="openAddModal" title="Add To-Do">
                    <i class="fas fa-plus"></i> <span>Add Task</span>
                </button>
            </div>
        @else
            {{-- Dashboard Widget Header: Perfectly matches 'My Recent Tasks' --}}
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom border-main">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                    <i class="fas fa-clipboard-check text-primary" style="font-size: 1.05rem;"></i>
                    <h5 class="fw-bold mb-0 text-high text-nowrap" style="font-size: 1.05rem;">
                        @if($activeUser)
                            {{ $activeUser->name }}'s To-Dos
                        @else
                            Personal To-Do
                        @endif
                    </h5>
                    @if($pendingCount > 0)
                        <span class="badge-premium d-none d-sm-inline-flex" style="background: rgba(var(--warning-rgb), 0.15); color: #f59e0b; border: 1px solid rgba(var(--warning-rgb), 0.3); font-size: 0.68rem; padding: 2px 8px; white-space: nowrap;">
                            {{ $pendingCount }} Pending
                        </span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="{{ route('admin.todos.index') }}" class="btn-premium btn-premium-secondary btn-sm px-3 py-1" style="font-size: 0.75rem; text-decoration: none;" title="View all to-dos">
                        View All
                    </a>
                    <button type="button" class="btn-premium btn-premium-primary btn-sm px-3 py-1 d-flex align-items-center gap-1 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#todoModal" wire:click="openAddModal" title="Add To-Do" style="font-size: 0.75rem;">
                        <i class="fas fa-plus"></i> <span>Add Task</span>
                    </button>
                </div>
            </div>

            {{-- Compact full-width search for dashboard widget --}}
            <div class="px-4 py-2 border-bottom border-subtle" style="background: rgba(255, 255, 255, 0.015);">
                <div class="position-relative w-100">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-low" style="font-size: 0.78rem;"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks..."
                           class="form-premium-control ps-5 pe-4 w-100" style="height: 34px; font-size: 0.8rem; border-radius: var(--radius-sm);">
                    @if($search)
                        <button type="button" wire:click="$set('search', '')" class="btn btn-sm border-0 position-absolute top-50 end-0 translate-middle-y text-low me-2 p-0" style="background: transparent;">
                            <i class="fas fa-times-circle" style="font-size: 0.8rem;"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <!-- Todo List -->
        <div class="todo-list overflow-auto p-3" style="max-height: {{ $standalone ? $height : '420px' }}; flex-grow: 1; padding-right: 8px;">
            @forelse($todos as $todo)
                <div class="d-flex flex-column p-3 mb-2 rounded transition-all todo-item" 
                     style="background: {{ $todo->is_completed ? 'rgba(255,255,255,0.015)' : 'var(--bg-surface)' }}; border: 1px solid var(--border-subtle); @if($todo->is_completed) opacity: 0.7; @endif">
                    
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3 overflow-hidden flex-grow-1">
                            <!-- Checkbox -->
                            <div class="form-check m-0">
                                <input class="form-check-input cursor-pointer" type="checkbox" 
                                       wire:click="toggleTodo({{ $todo->id }})" 
                                       @if($todo->is_completed) checked @endif
                                       style="width: 1.15rem; height: 1.15rem; border-color: var(--border-main); background-color: var(--bg-input);">
                            </div>
                            
                            <!-- Title -->
                            <span class="text-truncate fw-medium @if($todo->is_completed) text-decoration-line-through text-low @else text-high @endif" 
                                  style="font-size: 0.88rem; cursor: pointer;"
                                  wire:click="toggleTodo({{ $todo->id }})"
                                  title="{{ $todo->title }}">
                                {{ $todo->title }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex align-items-center gap-1 ms-2">
                            <button type="button" data-bs-toggle="modal" data-bs-target="#todoModal" wire:click="openEditModal({{ $todo->id }}, '{{ addslashes($todo->title) }}')" class="btn btn-sm text-low hover-primary p-1" title="Edit">
                                <i class="fas fa-pencil-alt" style="font-size: 0.78rem;"></i>
                            </button>
                            <button type="button" wire:click="deleteTodo({{ $todo->id }})" wire:confirm="Are you sure you want to delete this task?" class="btn btn-sm text-low hover-danger p-1" title="Delete">
                                <i class="fas fa-trash-alt" style="font-size: 0.78rem;"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Metadata (Status & Time) -->
                    <div class="d-flex justify-content-between align-items-center ms-4 ps-1 mt-2 pt-1 border-top border-subtle border-opacity-25">
                        <span class="badge-premium d-inline-flex align-items-center gap-1" 
                              style="font-size: 0.68rem; padding: 2px 8px; 
                              background: {{ $todo->is_completed ? 'rgba(var(--accent-rgb), 0.12)' : 'rgba(var(--warning-rgb), 0.12)' }}; 
                              color: {{ $todo->is_completed ? 'var(--accent)' : '#f59e0b' }};">
                            <i class="fas {{ $todo->is_completed ? 'fa-check' : 'fa-hourglass-half' }}" style="font-size: 0.6rem;"></i>
                            {{ $todo->is_completed ? 'Completed' : 'Pending' }}
                        </span>
                        <span class="text-low" style="font-size: 0.7rem;">
                            <i class="far fa-clock me-1"></i> {{ $todo->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-low">
                    <div class="mb-3" style="font-size: 2.2rem; opacity: 0.25;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    @if($search || $status || $targetUserId)
                        <h6 class="fw-semibold text-high mb-1">No matching tasks</h6>
                        <p class="mb-3 small">No to-dos matched your current filter criteria.</p>
                        <button type="button" wire:click="resetFilters" class="btn-premium btn-premium-secondary btn-sm px-3">
                            <i class="fas fa-redo me-1"></i> Clear Filters
                        </button>
                    @else
                        <h6 class="fw-semibold text-high mb-1">All caught up!</h6>
                        <p class="mb-3 small">You have no tasks in your personal to-do list.</p>
                        <button type="button" class="btn-premium btn-premium-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#todoModal" wire:click="openAddModal">
                            <i class="fas fa-plus me-1"></i> Add First Task
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
    </div> <!-- End glass-card -->

    <!-- Todo Modal -->
    <div wire:ignore.self class="modal fade" id="todoModal" tabindex="-1" aria-labelledby="todoModalLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high" id="todoModalLabel">
                        @if($modalId) Edit Task @else Add Task @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="saveTodo">
                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Task Description</label>
                            <input type="text" wire:model="modalTitle" class="form-premium-control w-100" placeholder="E.g., Review project documentation..." required>
                            @error('modalTitle') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-premium btn-premium-primary px-4">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .todo-item {
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .todo-item:hover {
            border-color: var(--border-main) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.15);
        }
        .hover-danger:hover {
            color: var(--danger) !important;
            background: rgba(var(--danger-rgb), 0.1);
        }
        .hover-primary:hover {
            color: var(--primary) !important;
            background: rgba(var(--primary-rgb), 0.1);
        }
        .todo-list::-webkit-scrollbar {
            width: 4px;
        }
        .todo-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .todo-list::-webkit-scrollbar-thumb {
            background: var(--border-main);
            border-radius: 4px;
        }
        .todo-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-medium);
        }
    </style>

    <!-- Script to close modal via browser event -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('close-todo-modal', () => {
                const modalEl = document.getElementById('todoModal');
                if (modalEl) {
                    let modal = window.bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        modal = new window.bootstrap.Modal(modalEl);
                    }
                    modal.hide();
                    
                    // Fallback to remove stuck backdrop
                    setTimeout(() => {
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 150);
                }
            });
        });
    </script>
</div>

