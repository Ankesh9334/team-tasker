<?php

use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $search = '';
    public $priority = '';
    public $tag_id = '';
    public $assigned_to = '';

    public function resetFilters()
    {
        $this->search = '';
        $this->priority = '';
        $this->tag_id = '';
        $this->assigned_to = '';
    }

    public function updateTaskStatus($taskId, $newStatusId)
    {
        $task = Task::findOrFail($taskId);
        $status = Status::findOrFail($newStatusId);

        $task->status_id = $newStatusId;

        if ($status->is_completed && !$task->completed_at) {
            $task->completed_at = now();
        } elseif (!$status->is_completed) {
            $task->completed_at = null;
        }

        $task->save();

        $task->logs()->create([
            'user_id' => Auth::id(),
            'note' => "Status changed to <strong>{$status->name}</strong> via Kanban Board",
            'type' => 'log',
        ]);

        $this->dispatch('status-updated', taskId: $taskId, statusId: $newStatusId, statusName: $status->name, statusColor: $status->color);
    }

    public function with()
    {
        $user = Auth::user();

        $baseQuery = Task::with([
            'assignedTo:id,name,profile_image',
            'tags:id,name,color',
            'subtasks:id,task_id,status_id',
            'timeLogs:id,task_id,duration',
            'attachments:id,task_id',
        ])
            ->when(!$user->hasPermission('tasks.view_all'), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)
                        ->orWhere('assigned_to', $user->id)
                        ->orWhereHas('users', fn($q3) => $q3->where('users.id', $user->id));
                });
            })
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->priority, fn($q) => $q->where('priority', $this->priority))
            ->when($this->tag_id, fn($q) => $q->whereHas('tags', fn($t) => $t->where('tags.id', $this->tag_id)))
            ->when($this->assigned_to, fn($q) => $q->where(function ($sq) {
                $sq->where('assigned_to', $this->assigned_to)
                    ->orWhereHas('users', fn($uq) => $uq->where('users.id', $this->assigned_to));
            }));

        $statuses = Status::where(function ($q) {
            $q->where('order', '>', 0)
                ->orWhere('is_default', 1);
        })
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if ($statuses->isEmpty()) {
            $statuses = Status::orderBy('id')->take(5)->get();
        }

        // Calculate counts per status
        $taskCounts = (clone $baseQuery)
            ->select('status_id', DB::raw('count(*) as count'))
            ->groupBy('status_id')
            ->pluck('count', 'status_id');

        // Fetch up to 20 recent tasks per status
        $tasksByStatus = [];
        foreach ($statuses as $status) {
            $tasksByStatus[$status->id] = (clone $baseQuery)
                ->where('status_id', $status->id)
                ->latest('updated_at')
                ->limit(20)
                ->get();
        }

        $totalVisibleTasks = array_sum($taskCounts->toArray());

        return [
            'statuses' => $statuses,
            'tasksByStatus' => $tasksByStatus,
            'taskCounts' => $taskCounts,
            'totalTasks' => $totalVisibleTasks,
            'tags' => Tag::orderBy('name')->get(),
            'users' => User::select('id', 'name')->where('role_id', '!=', 3)->orderBy('name')->get(),
            'priorities' => ['Low', 'Medium', 'High', 'Critical'],
        ];
    }
};
?>

{{-- Single Livewire Root --}}
<div x-data="taskBoard()" x-init="initBoard()" wire:ignore.self>

    @php
        $resolveColor = function($color) {
            if (!$color) return '#6366f1';
            if (str_starts_with($color, '#')) return $color;
            return match(strtolower($color)) {
                'primary' => '#3b82f6',
                'secondary' => '#64748b',
                'success' => '#10b981',
                'danger' => '#ef4444',
                'warning' => '#f59e0b',
                'info' => '#06b6d4',
                'dark' => '#1e293b',
                'light' => '#94a3b8',
                default => '#6366f1',
            };
        };

        $hasFilters = $search || $priority || $tag_id || $assigned_to;
    @endphp

    {{-- ─── DRAG-AND-DROP TOAST ─── --}}
    <div id="dnd-toast" class="dnd-toast" style="display:none;">
        <i class="fas fa-check-circle me-2" style="color: var(--accent);"></i>
        <span id="dnd-toast-msg">Task moved</span>
    </div>

    {{-- ─── KANBAN FILTER TOOLBAR ─── --}}
    <style>
        @media (max-width: 768px) {
            .kanban-filters {
                flex-direction: column;
                align-items: stretch !important;
                width: 100%;
            }
            .kanban-filters > div {
                min-width: 100% !important;
                max-width: 100% !important;
                width: 100%;
            }
            .kanban-filters button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <div class="glass-card p-3 mb-4 border-main d-flex flex-wrap align-items-center justify-content-between gap-3"
         style="background: var(--bg-surface); border-radius: var(--radius-lg);">
        <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1 kanban-filters">
            {{-- Search Input --}}
            <div class="position-relative" style="min-width: 220px; max-width: 300px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-low" style="font-size: 0.85rem;"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks..."
                       class="form-premium-control ps-5 w-100" style="height: 38px; font-size: 0.85rem;">
                @if($search)
                    <button type="button" wire:click="$set('search', '')" class="btn btn-sm border-0 position-absolute top-50 end-0 translate-middle-y text-low me-1" style="background: transparent;">
                        <i class="fas fa-times-circle"></i>
                    </button>
                @endif
            </div>

            {{-- Priority Filter --}}
            <div style="min-width: 150px;">
                <x-select wire:model.live="priority" placeholder="All Priorities">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Tag Filter --}}
            <div style="min-width: 150px;">
                <x-select wire:model.live="tag_id" placeholder="All Tags">
                    <option value="">All Tags</option>
                    @foreach($tags as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Assignee Filter --}}
            <div style="min-width: 170px;">
                <x-select wire:model.live="assigned_to" placeholder="All Assignees">
                    <option value="">All Assignees</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </x-select>
            </div>

            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="btn-premium btn-premium-secondary btn-sm px-3 d-flex align-items-center gap-1" style="height: 38px;">
                    <i class="fas fa-redo"></i> Reset
                </button>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-surface border border-subtle text-high px-3 py-2 rounded-pill small fw-semibold">
                <i class="fas fa-tasks text-primary me-1"></i> {{ number_format($totalTasks) }} Tasks
            </span>
        </div>
    </div>

    {{-- ─── KANBAN BOARD CONTAINER ─── --}}
    <div class="kanban-wrapper overflow-auto pb-5" style="min-height: calc(100vh - 220px);">
        <div class="d-flex gap-4 p-1 pb-4" style="min-width: fit-content; align-items: flex-start;">
            @foreach ($statuses as $status)
                @php
                    $hex = $resolveColor($status->color);
                    $columnTasks = $tasksByStatus[$status->id] ?? collect();
                    $columnCount = $taskCounts[$status->id] ?? 0;
                @endphp
                <div class="kanban-column" style="width: 320px; flex-shrink: 0;" wire:key="col-{{ $status->id }}">

                    {{-- Column Header --}}
                    <div class="kanban-col-header d-flex align-items-center justify-content-between mb-3 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="kanban-status-dot"
                                style="background: {{ $hex }}; box-shadow: 0 0 8px {{ $hex }}66;">
                            </div>
                            <span class="fw-bold text-high"
                                style="font-size: 0.84rem; letter-spacing: 0.03em; text-transform: uppercase;">
                                {{ $status->name }}
                            </span>
                            <span class="kanban-count-badge" data-status-id="{{ $status->id }}"
                                style="background: {{ $hex }}1f; color: {{ $hex }}; border: 1px solid {{ $hex }}44;">
                                {{ $columnCount }}
                            </span>
                            @if(isset($status->wip_limit) && $status->wip_limit > 0)
                                <span class="badge ms-1 wip-badge" data-status-id="{{ $status->id }}" data-wip="{{ $status->wip_limit }}"
                                    style="background-color: {{ $columnCount > $status->wip_limit ? 'var(--danger)' : 'var(--bg-input)' }}; color: {{ $columnCount > $status->wip_limit ? 'white' : 'var(--text-medium)' }}; font-size: 0.65rem;">
                                    Max: {{ $status->wip_limit }}
                                </span>
                            @endif
                        </div>
                        {{-- Colored top stripe --}}
                        <div style="width: 28px; height: 4px; border-radius: 2px; background: {{ $hex }}; opacity: 0.85;"></div>
                    </div>

                    {{-- Dropzone --}}
                    <div class="kanban-dropzone" data-status-id="{{ $status->id }}"
                        data-status-name="{{ $status->name }}" data-status-color="{{ $hex }}"
                        style="min-height: 580px; border-radius: var(--radius-lg);">

                        @foreach ($columnTasks as $task)
                            @php
                                $pColor = match ($task->priority) {
                                    'Critical' => 'var(--danger)',
                                    'High' => '#f59e0b',
                                    default => 'var(--primary)',
                                };
                                $pBg = match ($task->priority) {
                                    'Critical' => 'rgba(239,68,68,0.1)',
                                    'High' => 'rgba(245,158,11,0.1)',
                                    default => 'rgba(99,102,241,0.1)',
                                };
                                $pBorder = match ($task->priority) {
                                    'Critical' => 'rgba(239,68,68,0.25)',
                                    'High' => 'rgba(245,158,11,0.25)',
                                    default => 'rgba(99,102,241,0.25)',
                                };
                                $pIcon = match ($task->priority) {
                                    'Critical' => 'fa-fire-alt',
                                    'High' => 'fa-arrow-up',
                                    default => 'fa-minus',
                                };
                                $totalSubs = $task->subtasks ? $task->subtasks->count() : 0;
                                $doneSubs = $task->subtasks
                                    ? $task->subtasks->filter(fn($s) => $s->status && $s->status->is_completed)->count()
                                    : 0;
                                $hasDeadline = $task->deadline;
                                $isOverdue = $hasDeadline && $task->deadline->isPast();
                                $timeSpent = $task->timeLogs ? round($task->timeLogs->sum('duration') / 3600, 1) : 0;
                            @endphp

                            <div class="kanban-card" data-id="{{ $task->id }}"
                                data-status-id="{{ $status->id }}" wire:key="task-{{ $task->id }}"
                                style="border-top: 3px solid {{ $hex }};">

                                {{-- Card Top Row --}}
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="kcard-badge"
                                        style="background: {{ $pBg }}; color: {{ $pColor }}; border: 1px solid {{ $pBorder }};">
                                        <i class="fas {{ $pIcon }}" style="font-size: 0.6rem;"></i>
                                        {{ $task->priority ?? 'Medium' }}
                                    </span>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="drag-handle" title="Drag to move">
                                            <i class="fas fa-grip-vertical"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="kcard-menu-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-premium" style="min-width: 150px;">
                                                <li>
                                                    <a class="dropdown-item small" href="{{ route('details', $task->id) }}">
                                                        <i class="fas fa-eye me-2 text-primary"></i> View Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item small" href="{{ route('edit', $task->id) }}">
                                                        <i class="fas fa-edit me-2" style="color: var(--accent);"></i> Edit Task
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider" style="border-color: var(--border-subtle); margin: 4px 0;">
                                                </li>
                                                <li class="dropdown-header small text-low py-1">Move to:</li>
                                                @foreach ($statuses as $targetStatus)
                                                    @if ($targetStatus->id !== $status->id)
                                                        @php $tHex = $resolveColor($targetStatus->color); @endphp
                                                        <li>
                                                            <button type="button" class="dropdown-item small text-high d-flex align-items-center"
                                                                wire:click="updateTaskStatus({{ $task->id }}, {{ $targetStatus->id }})">
                                                                <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $tHex }}; display: inline-block; margin-right: 8px;"></span>
                                                                {{ $targetStatus->name }}
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                {{-- Title --}}
                                <a href="{{ route('details', $task->id) }}"
                                    class="text-decoration-none d-block mb-2 fw-semibold lh-base"
                                    style="color: var(--text-high); font-size: 0.88rem;">
                                    {{ Str::limit($task->title, 65) }}
                                </a>

                                {{-- Tags --}}
                                @if ($task->tags && $task->tags->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        @foreach ($task->tags->take(3) as $tag)
                                            <span class="kcard-tag"
                                                style="background: {{ $tag->color }}1a; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}44;">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                        @if ($task->tags->count() > 3)
                                            <span class="kcard-tag"
                                                style="background: var(--bg-input); color: var(--text-low);">+{{ $task->tags->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif

                                {{-- Progress Bar (if subtasks) --}}
                                @if ($totalSubs > 0)
                                    @php $pct = round(($doneSubs / $totalSubs) * 100); @endphp
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1"
                                            style="font-size: 0.68rem; color: var(--text-low);">
                                            <span>{{ $doneSubs }}/{{ $totalSubs }} subtasks</span>
                                            <span>{{ $pct }}%</span>
                                        </div>
                                        <div style="height: 3px; background: var(--bg-input); border-radius: 2px; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $pct }}%; background: {{ $hex }}; border-radius: 2px; transition: width 0.5s;"></div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Card Footer --}}
                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2"
                                    style="border-top: 1px solid var(--border-subtle);">

                                    {{-- Assignee --}}
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($task->assignedTo)
                                            <div class="avatar-premium rounded-circle overflow-hidden d-flex align-items-center justify-content-center"
                                                style="width: 22px; height: 22px; font-size: 0.55rem; border: 1.5px solid var(--border-main); background: rgba(var(--primary-rgb), 0.1); color: var(--primary);"
                                                title="{{ $task->assignedTo->name }}">
                                                @if ($task->assignedTo->profile_image)
                                                    <img src="{{ asset('storage/' . $task->assignedTo->profile_image) }}" alt="{{ $task->assignedTo->name }}" class="w-100 h-100 object-fit-cover">
                                                @else
                                                    {{ substr($task->assignedTo->name, 0, 1) }}
                                                @endif
                                            </div>
                                            <span style="font-size: 0.7rem; color: var(--text-low);">{{ explode(' ', $task->assignedTo->name)[0] }}</span>
                                        @else
                                            <div style="font-size: 0.68rem; color: var(--text-low); font-style: italic;">Unassigned</div>
                                        @endif
                                    </div>

                                    {{-- Meta chips --}}
                                    <div class="d-flex align-items-center gap-2" style="font-size: 0.68rem; color: var(--text-low);">
                                        @if ($timeSpent > 0)
                                            <span title="Time logged"><i class="fas fa-clock me-1"></i>{{ $timeSpent }}h</span>
                                        @endif
                                        @if ($task->attachments && $task->attachments->count() > 0)
                                            <span title="Attachments"><i class="fas fa-paperclip me-1"></i>{{ $task->attachments->count() }}</span>
                                        @endif
                                        @if ($hasDeadline)
                                            <span title="{{ $isOverdue ? 'Overdue!' : 'Deadline' }}"
                                                style="color: {{ $isOverdue ? 'var(--danger)' : 'var(--text-low)' }};">
                                                <i class="fas fa-calendar{{ $isOverdue ? '-times' : '-alt' }} me-1"></i>
                                                {{ $task->deadline->format('M d') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Empty state --}}
                        @if ($columnTasks->isEmpty())
                            <div class="kanban-empty-state">
                                <div style="font-size: 1.8rem; margin-bottom: 8px; opacity: 0.25;">⬡</div>
                                <div style="font-size: 0.75rem; color: var(--text-low);">Drop tasks here</div>
                            </div>
                        @elseif($columnCount > $columnTasks->count())
                            <div class="text-center py-2 text-low" style="font-size: 0.72rem;">
                                Showing {{ $columnTasks->count() }} of {{ $columnCount }} tasks
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .kanban-col-header {
            user-select: none;
            padding-bottom: 4px;
        }

        .kanban-status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .kanban-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            transition: all 0.2s;
        }

        .kanban-dropzone {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 8px;
            border-radius: var(--radius-lg);
            border: 2px dashed var(--border-main);
            background: transparent;
            transition: background 0.2s, border-color 0.2s, box-shadow 0.15s;
            min-height: 580px;
        }

        .kanban-dropzone.drop-target {
            background: rgba(var(--primary-rgb), 0.04);
            border-color: rgba(var(--primary-rgb), 0.3);
        }

        .kanban-dropzone.drop-active {
            background: rgba(var(--primary-rgb), 0.08);
            border-color: var(--primary);
            box-shadow: inset 0 0 0 2px rgba(var(--primary-rgb), 0.15);
        }

        .kanban-card {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 14px;
            border: 1px solid var(--border-subtle);
            cursor: default;
            transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.18s cubic-bezier(0.4, 0, 0.2, 1),
                border-color 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .kanban-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 60%, rgba(255, 255, 255, 0.015));
            pointer-events: none;
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.25);
            border-color: var(--border-main);
            z-index: 2;
        }

        .kanban-ghost {
            opacity: 0;
            background: transparent !important;
        }

        .kanban-chosen {
            opacity: 1;
        }

        .kanban-dragging {
            transform: rotate(1.5deg) scale(1.03) !important;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4) !important;
            opacity: 0.95 !important;
            cursor: grabbing !important;
            z-index: 9999 !important;
        }

        body.is-dragging * {
            cursor: grabbing !important;
        }

        .drag-handle {
            cursor: grab;
            color: var(--text-low);
            opacity: 0;
            font-size: 0.75rem;
            padding: 2px 4px;
            border-radius: 4px;
            transition: opacity 0.15s, background 0.15s;
            display: flex;
            align-items: center;
        }

        .kanban-card:hover .drag-handle {
            opacity: 1;
        }

        .drag-handle:hover {
            background: var(--bg-input);
            color: var(--text-medium);
        }

        .kcard-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .kcard-tag {
            display: inline-flex;
            align-items: center;
            padding: 1px 7px;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .kcard-menu-btn {
            background: none;
            border: none;
            color: var(--text-low);
            opacity: 0;
            font-size: 0.75rem;
            cursor: pointer;
            padding: 3px 5px;
            border-radius: var(--radius-sm);
            transition: opacity 0.15s, background 0.15s;
            display: flex;
            align-items: center;
        }

        .kanban-card:hover .kcard-menu-btn {
            opacity: 1;
        }

        .kcard-menu-btn:hover {
            background: var(--bg-input);
            color: var(--text-high);
        }

        .kanban-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            flex: 1;
            pointer-events: none;
        }

        .kanban-dropzone:not(:has(.kanban-card)) .kanban-empty-state {
            display: flex;
        }

        .dnd-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--bg-surface);
            border: 1px solid var(--border-main);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-full);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--text-high);
            box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.4);
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.25s, transform 0.25s;
            pointer-events: none;
        }

        .dnd-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 768px) {
            .kanban-card {
                padding: 10px;
            }
        }
    </style>
</div>{{-- /Single Livewire Root --}}

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        function taskBoard() {
            return {
                sortables: [],
                isDragging: false,

                initBoard() {
                    this.initSortables();
                    
                    document.addEventListener('livewire:morph-complete', () => {
                        if (!this.isDragging) setTimeout(() => this.initSortables(), 100);
                    });

                    Livewire.on('status-updated', (data) => {
                        this.showToast('Moved to <strong>' + data.statusName + '</strong>');
                    });
                },

                destroySortables() {
                    if (this.isDragging) return;
                    this.sortables.forEach(s => {
                        try { s.destroy(); } catch (e) {}
                    });
                    this.sortables = [];
                },

                initSortables() {
                    this.destroySortables();
                    const self = this;
                    const zones = document.querySelectorAll('.kanban-dropzone');
                    if (!zones.length) return;

                    zones.forEach(zone => {
                        const inst = new Sortable(zone, {
                            group: 'kanban',
                            animation: 180,
                            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                            ghostClass: 'kanban-ghost',
                            chosenClass: 'kanban-chosen',
                            dragClass: 'kanban-dragging',
                            filter: 'a, button, .dropdown, .dropdown-menu, .dropdown-item, select, input',
                            preventOnFilter: false,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            scroll: true,
                            scrollSensitivity: 60,
                            scrollSpeed: 12,

                            onStart(evt) {
                                self.isDragging = true;
                                document.body.classList.add('is-dragging');
                                document.querySelectorAll('.kanban-dropzone').forEach(z => {
                                    if (z !== evt.from) z.classList.add('drop-target');
                                });
                            },

                            onMove(evt) {
                                document.querySelectorAll('.kanban-dropzone').forEach(z => z.classList.remove('drop-active'));
                                if (evt.to) {
                                    evt.to.classList.add('drop-active');
                                    
                                    const statusId = evt.to.getAttribute('data-status-id');
                                    const badge = document.querySelector(`.wip-badge[data-status-id="${statusId}"]`);
                                    if (badge && evt.from !== evt.to) {
                                        const currentCount = evt.to.querySelectorAll('.kanban-card').length;
                                        const limit = parseInt(badge.getAttribute('data-wip'));
                                        if (currentCount >= limit) {
                                            evt.to.classList.remove('drop-active');
                                            return false;
                                        }
                                    }
                                }
                                return true;
                            },

                            onEnd(evt) {
                                self.isDragging = false;
                                document.body.classList.remove('is-dragging');
                                document.querySelectorAll('.kanban-dropzone').forEach(z => {
                                    z.classList.remove('drop-target', 'drop-active');
                                });

                                const taskId = evt.item.getAttribute('data-id');
                                const newStatusId = evt.to ? evt.to.getAttribute('data-status-id') : null;
                                const oldStatusId = evt.from ? evt.from.getAttribute('data-status-id') : null;
                                const statusName = evt.to ? evt.to.getAttribute('data-status-name') : '';
                                const statusColor = evt.to ? evt.to.getAttribute('data-status-color') : null;

                                if (!taskId || !newStatusId || newStatusId === oldStatusId) return;

                                if (statusColor) evt.item.style.borderTopColor = statusColor;

                                self.changeBadge(oldStatusId, -1);
                                self.changeBadge(newStatusId, 1);

                                self.$wire.updateTaskStatus(taskId, newStatusId);
                                self.showToast('Moved to <strong>' + statusName + '</strong>', statusColor);
                            }
                        });

                        self.sortables.push(inst);
                    });
                },

                changeBadge(statusId, delta) {
                    if (!statusId) return;
                    const badge = document.querySelector('.kanban-count-badge[data-status-id="' + statusId + '"]');
                    if (!badge) return;
                    const n = Math.max(0, (parseInt(badge.textContent) || 0) + delta);
                    badge.textContent = n;
                    
                    const wipBadge = document.querySelector(`.wip-badge[data-status-id="${statusId}"]`);
                    if (wipBadge) {
                        const limit = parseInt(wipBadge.getAttribute('data-wip'));
                        if (n > limit) {
                            wipBadge.style.backgroundColor = 'var(--danger)';
                            wipBadge.style.color = 'white';
                        } else {
                            wipBadge.style.backgroundColor = 'var(--bg-input)';
                            wipBadge.style.color = 'var(--text-medium)';
                        }
                    }

                    const zone = document.querySelector('.kanban-dropzone[data-status-id="' + statusId + '"]');
                    if (zone) {
                        const empty = zone.querySelector('.kanban-empty-state');
                        if (empty) empty.style.display = zone.querySelectorAll('.kanban-card').length === 0 ? 'flex' : 'none';
                    }
                },

                showToast(html, color) {
                    const t = document.getElementById('dnd-toast');
                    if (!t) return;
                    const m = document.getElementById('dnd-toast-msg');
                    if (m) m.innerHTML = html;
                    const ic = t.querySelector('i');
                    if (ic) ic.style.color = color || 'var(--accent)';
                    t.style.display = 'flex';
                    t.classList.add('show');
                    clearTimeout(t._tmr);
                    t._tmr = setTimeout(() => {
                        t.classList.remove('show');
                        setTimeout(() => {
                            t.style.display = 'none';
                        }, 300);
                    }, 2600);
                }
            };
        }
    </script>
@endpush

