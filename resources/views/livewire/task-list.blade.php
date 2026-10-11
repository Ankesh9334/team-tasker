<?php

use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function paginationView()
    {
        return 'components.pagination.premium';
    }

    public $search = '';
    public $status_id = '';
    public $priority = '';
    public $tag_id = '';
    public $assigned_to = '';
    public $created_at = '';
    public $updated_at = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $selectedTasks = [];
    public $bulkStatus = '';
    public $bulkAssignee = '';
    public $bulkPriority = '';

    protected $queryString = ['search', 'status_id', 'priority', 'tag_id', 'assigned_to', 'created_at', 'updated_at', 'sortField', 'sortDirection', 'perPage' => ['as' => 'per_page']];

    public function mount()
    {
        if (request()->has('per_page')) {
            $this->perPage = request('per_page');
        }
    }
    public function updated($property)
    {
        if (in_array($property, ['search', 'status_id', 'priority', 'tag_id', 'assigned_to', 'created_at', 'updated_at'])) {
            $this->resetPage();
        }
    }

    public function bulkDelete()
    {
        Task::whereIn('id', $this->selectedTasks)->delete();
        $this->selectedTasks = [];
        $this->dispatch('notify', message: 'Tasks deleted successfully');
    }

    public function bulkChangeStatus()
    {
        if (!$this->bulkStatus) {
            return;
        }
        Task::whereIn('id', $this->selectedTasks)->update(['status_id' => $this->bulkStatus]);
        $this->selectedTasks = [];
        $this->bulkStatus = '';
        $this->dispatch('notify', message: 'Statuses updated successfully');
    }

    public function bulkAssign()
    {
        if (!$this->bulkAssignee) {
            return;
        }
        Task::whereIn('id', $this->selectedTasks)->update(['assigned_to' => $this->bulkAssignee]);
        $this->selectedTasks = [];
        $this->bulkAssignee = '';
        $this->dispatch('notify', message: 'Assignees updated successfully');
    }

    public function bulkChangePriority()
    {
        if (!$this->bulkPriority) {
            return;
        }
        Task::whereIn('id', $this->selectedTasks)->update(['priority' => $this->bulkPriority]);
        $this->selectedTasks = [];
        $this->bulkPriority = '';
        $this->dispatch('notify', message: 'Priorities updated successfully');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function with()
    {
        $user = Auth::user();

        $tasks = Task::with([
            'assignedTo',
            'status',
            'tags',
            'timeLogs' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->whereNull('end_time');
            }
        ])
            ->when(!$user->hasPermission('tasks.view_all'), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)
                        ->orWhere('assigned_to', $user->id)
                        ->orWhereHas('users', fn($q3) => $q3->where('users.id', $user->id));
                });
            })
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->status_id, fn($q) => $q->where('status_id', $this->status_id))
            ->when($this->priority, fn($q) => $q->where('priority', $this->priority))
            ->when($this->tag_id, fn($q) => $q->whereHas('tags', fn($t) => $t->where('tags.id', $this->tag_id)))
            ->when($this->assigned_to, fn($q) => $q->where(function ($sq) {
                $sq->where('assigned_to', $this->assigned_to)
                    ->orWhereHas('users', fn($uq) => $uq->where('users.id', $this->assigned_to));
            }))
            ->when($this->created_at, fn($q) => $q->whereDate('created_at', $this->created_at))
            ->when($this->updated_at, fn($q) => $q->whereDate('updated_at', $this->updated_at))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'tasks' => $tasks,
            'statuses' => Status::all(),
            'tags' => Tag::all(),
            'users' => User::select('id', 'name')->where('role_id', '!=', 3)->orderBy('name')->get(),
            'priorities' => ['Low', 'Medium', 'High', 'Critical'],
            'globalActiveTimer' => \App\Models\TimeLog::with('task')->where('user_id', $user->id)->whereNull('end_time')->first(),
        ];
    }
};
?>

<div>
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks...">
            </div>
            <div class="data-grid-results">{{ $tasks->total() }} Results</div>
            <div class="data-grid-actions">{{ $tasks->links() }}</div>
        </div>

        <div class="data-grid-bulk-actions {{ count($selectedTasks) > 0 ? 'active' : '' }}">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span>{{ count($selectedTasks) }}</span> Items Selected</span>

                <div class="d-flex align-items-center gap-2 border-start border-white-50 ps-3 ms-1">
                    <select wire:model.live="bulkStatus" class="form-select form-select-sm"
                        style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 28px 4px 10px; width: 120px; cursor: pointer;">
                        <option value="" style="color: black;">Status...</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" style="color: black;">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="bulkChangeStatus" class="btn-bulk-outline" @if (!$bulkStatus) disabled @endif>
                        Apply
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2 border-start border-white-50 ps-3 ms-1">
                    <select wire:model.live="bulkPriority" class="form-select form-select-sm"
                        style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 28px 4px 10px; width: 120px; cursor: pointer;">
                        <option value="" style="color: black;">Priority...</option>
                        @foreach ($priorities as $p)
                            <option value="{{ $p }}" style="color: black;">{{ $p }}</option>
                        @endforeach
                    </select>
                    <button wire:click="bulkChangePriority" class="btn-bulk-outline" @if (!$bulkPriority) disabled
                    @endif>
                        Apply
                    </button>
                </div>

                <button wire:click="bulkDelete" onclick="return confirm('Are you sure?')"
                    class="btn-bulk-danger border-start border-white-50 ps-3 ms-1" style="border-radius: 0 6px 6px 0;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
            <button type="button" class="btn-deselect-all" wire:click="$set('selectedTasks', [])">
                Deselect All
            </button>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" class="data-grid-checkbox"
                                onclick="let checked = this.checked; document.querySelectorAll('.task-checkbox').forEach(c => { c.checked = checked; c.dispatchEvent(new Event('change')); })">
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('title')">
                            TITLE @if ($sortField === 'title') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th>ASSIGNED TO</th>
                        <th class="cursor-pointer" wire:click="sortBy('status_id')">
                            STATUS @if ($sortField === 'status_id') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th>PRIORITY</th>
                        <th>TAGS</th>
                        <th class="cursor-pointer" wire:click="sortBy('created_at')">
                            CREATED @if ($sortField === 'created_at') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th class="text-end pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr wire:key="task-row-{{ $task->id }}"
                            class="{{ in_array($task->id, $selectedTasks) ? 'bg-primary-subtle' : '' }}"
                            style="border-bottom: 1px solid var(--border-subtle);">
                            <td><input type="checkbox" wire:model.live="selectedTasks" value="{{ $task->id }}"
                                    class="data-grid-checkbox task-checkbox"></td>
                            <td>
                                <a href="{{ route('details', $task->id) }}"
                                    class="text-decoration-none fw-medium d-block text-high">
                                    {{ $task->title }}
                                </a>
                                @if ($task->parent_id)
                                    <span class="extra-small text-low"><i
                                            class="fas fa-level-up-alt fa-rotate-90 me-1"></i>Subtask of
                                        #{{ $task->parent_id }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($task->assignedTo)
                                    <span class="text-high fw-medium">{{ $task->assignedTo->name }}</span>
                                @else
                                    <span class="text-low italic">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColorMap = ['primary' => 'var(--primary)', 'success' => 'var(--accent)', 'danger' => 'var(--danger)', 'warning' => '#f59e0b', 'info' => '#0ea5e9', 'secondary' => 'var(--text-medium)'];
                                    $themeColor = $statusColorMap[$task->status->color ?? 'secondary'] ?? 'var(--text-medium)';
                                @endphp
                                <span class="badge-premium"
                                    style="background: color-mix(in srgb, {{ $themeColor }} 15%, transparent); color: {{ $themeColor }}; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                                    {{ $task->status->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $pColor = $task->priority == 'Critical' ? '#ef4444' : ($task->priority == 'High' ? '#10b981' : '#64748b');
                                    $pBg = $task->priority == 'Critical' ? 'rgba(239,68,68,0.1)' : ($task->priority == 'High' ? 'rgba(16,185,129,0.1)' : '#f1f5f9');
                                @endphp
                                <span class="badge-premium"
                                    style="background: {{ $pBg }}; color: {{ $pColor }}; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                                    {{ $task->priority ?? 'Medium' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($task->tags->take(2) as $tag)
                                        <span class="badge bg-opacity-10 extra-small px-2 py-0"
                                            style="background-color: {{ $tag->color }}1a; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40;">{{ $tag->name }}</span>
                                    @endforeach
                                    @if ($task->tags->count() > 2)
                                        <span class="extra-small text-low">+{{ $task->tags->count() - 2 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-low">{{ $task->created_at->format('M d, Y') }}</td>
                            <td class="text-end pe-4 d-flex align-items-center justify-content-end gap-2">
                                @if (Auth::id() == $task->assigned_to)
                                    @php
                                        $activeTimer = $task->timeLogs->first();
                                    @endphp
                                    @if ($activeTimer)
                                        <form action="{{ route('tasks.stop_timer', $task->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-link text-danger border-0 bg-transparent p-0"
                                                title="Stop Timer">
                                                <i class="fas fa-stop-circle fa-lg"></i>
                                            </button>
                                        </form>
                                    @elseif(isset($globalActiveTimer) && $globalActiveTimer)
                                        <button type="button" class="action-link text-primary border-0 bg-transparent p-0"
                                            title="Start Timer" data-bs-toggle="modal" data-bs-target="#switchTimerModal"
                                            onclick="document.getElementById('forceStartForm').action = '{{ route('tasks.start_timer', $task->id) }}?force=1'">
                                            <i class="fas fa-play-circle fa-lg"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('tasks.start_timer', $task->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-link text-primary border-0 bg-transparent p-0"
                                                title="Start Timer">
                                                <i class="fas fa-play-circle fa-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ route('details', $task->id) }}" class="action-link ms-2"><i
                                        class="fas fa-eye"></i></a>
                                <a href="{{ route('edit', $task->id) }}" class="action-link"><i
                                        class="fas fa-pencil-alt"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-low">
                                <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                No tasks found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- Filter Slideover -->
    <div class="filter-slideover" id="filterSlideoverTasks">
        <div class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <div class="filter-slideover-close"
                    onclick="document.getElementById('filterSlideoverTasks').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <x-select wire:model.live="status_id" name="status_id" id="status_id">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">PRIORITY</label>
                    <x-select wire:model.live="priority">
                        <option value="">All Priorities</option>
                        @foreach ($priorities as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">TAGS</label>
                    <x-select wire:model.live="tag_id">
                        <option value="">All Tags</option>
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">ASSIGNED TO</label>
                    <x-select wire:model.live="assigned_to">
                        <option value="">All Assignees</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">CREATED DATE</label>
                    <input type="date" wire:model.live="created_at" class="form-premium-control w-100"
                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px;">
                </div>
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">UPDATED DATE</label>
                    <input type="date" wire:model.live="updated_at" class="form-premium-control w-100"
                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px;">
                </div>
            </div>
            <div class="filter-slideover-footer">
                <button type="button"
                    wire:click="$set('search', ''); $set('status_id', ''); $set('priority', ''); $set('tag_id', ''); $set('assigned_to', ''); $set('created_at', ''); $set('updated_at', '');"
                    class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</button>
                <button type="button" onclick="document.getElementById('filterSlideoverTasks').classList.remove('show')"
                    class="btn-premium btn-premium-primary w-50 justify-content-center"
                    style="background: #0ea5e9;">Apply Filters</button>
            </div>
        </div>
    </div>

    @if(isset($globalActiveTimer) && $globalActiveTimer)
        <!-- Switch Timer Modal -->
        <div class="modal fade" id="switchTimerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-premium"
                    style="border-radius: 20px; border: 1px solid var(--border-subtle); background: var(--bg-surface); backdrop-filter: blur(20px);">
                    <div class="modal-header border-0 px-4 pt-4 pb-0">
                        <h5 class="modal-title fw-bold text-high">Active Timer Detected</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <p class="text-medium mb-3">You already have an active timer running for task:</p>
                        <div class="p-3 mb-3"
                            style="background: var(--bg-input); border-radius: 12px; border: 1px solid var(--border-main);">
                            <div class="fw-bold text-primary">{{ $globalActiveTimer->task->title ?? 'Unknown Task' }}</div>
                        </div>
                        <p class="text-medium mb-0">Do you want to stop that timer and start a new one for this task?</p>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-premium btn-premium-secondary py-2 px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-premium py-2 px-4"
                            style="background: var(--primary); color: white;"
                            onclick="document.getElementById('forceStartForm').submit();">
                            <i class="fas fa-exchange-alt me-2"></i> Switch Timer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form id="forceStartForm" action="" method="POST" class="d-none">
            @csrf
        </form>
    @endif

    <style>
        .bulk-action-bar {
            position: fixed;
            bottom: var(--space-6);
            left: 50%;
            transform: translateX(-50%) translateY(200%);
            width: auto;
            min-width: 500px;
            z-index: 1050;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: var(--radius-xl);
            backdrop-filter: blur(15px);
        }

        .bulk-action-bar.show {
            transform: translateX(-50%) translateY(0);
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
    <script>
        document.addEventListener('openFilterModal', () => {
            document.getElementById('filterSlideoverTasks')?.classList.add('show');
        });
    </script>
</div>