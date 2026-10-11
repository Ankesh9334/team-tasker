<?php

use Livewire\Volt\Component;
use App\Models\Project;
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
    public $status = '';
    public $user_id = '';
    public $created_at = '';
    public $updated_at = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $selectedProjects = [];
    public $bulkStatus = '';
    public $bulkManager = '';

    protected $queryString = ['search', 'status', 'user_id', 'created_at', 'updated_at', 'sortField', 'sortDirection', 'perPage' => ['as' => 'per_page']];

    public function mount()
    {
        if (request()->has('per_page')) {
            $this->perPage = request('per_page');
        }
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'status', 'user_id', 'created_at', 'updated_at'])) {
            $this->resetPage();
        }
    }

    public function bulkDelete()
    {
        Project::whereIn('id', $this->selectedProjects)->delete();
        $this->selectedProjects = [];
        $this->dispatch('notify', message: 'Projects deleted successfully');
    }

    public function bulkChangeStatus()
    {
        if (!$this->bulkStatus) {
            return;
        }
        Project::whereIn('id', $this->selectedProjects)->update(['status' => $this->bulkStatus]);
        $this->selectedProjects = [];
        $this->bulkStatus = '';
        $this->dispatch('notify', message: 'Statuses updated successfully');
    }

    public function bulkAssign()
    {
        if (!$this->bulkManager) {
            return;
        }
        $projects = Project::whereIn('id', $this->selectedProjects)->get();
        foreach ($projects as $project) {
            $project->users()->sync([$this->bulkManager]);
        }
        $this->selectedProjects = [];
        $this->bulkManager = '';
        $this->dispatch('notify', message: 'Managers updated successfully');
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
        $isAdmin = Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('admin') || Auth::user()->hasPermission('tasks.view_all');
        $userId = Auth::user()->id;

        $projectsQuery = Project::withCount('tasks')
            ->with('users');

        if (!$isAdmin) {
            $projectsQuery->where(function ($q) use ($userId) {
                $q->whereHas('users', function ($q2) use ($userId) {
                    $q2->where('users.id', $userId);
                })->orWhereHas('tasks', function ($q3) use ($userId) {
                    $q3->where('assigned_to', $userId);
                });
            });
        }

        $projects = $projectsQuery->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->user_id, fn($q) => $q->whereHas('users', fn($q2) => $q2->where('users.id', $this->user_id)))
            ->when($this->created_at, fn($q) => $q->whereDate('created_at', $this->created_at))
            ->when($this->updated_at, fn($q) => $q->whereDate('updated_at', $this->updated_at))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'projects' => $projects,
            'users' => User::select('id', 'name')->where('role_id', '!=', 3)->orderBy('name')->get(),
            'statuses' => ['active', 'on_hold', 'completed', 'archived']
        ];
    }
};
?>

<div>
    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search projects...">
            </div>
            <div class="data-grid-results">{{ $projects->total() }} Results</div>
            <div class="data-grid-actions">{{ $projects->links() }}</div>
        </div>

        <div class="data-grid-bulk-actions {{ count($selectedProjects) > 0 ? 'active' : '' }}">
            <div class="data-grid-bulk-left">
                <span class="data-grid-bulk-count"><span>{{ count($selectedProjects) }}</span> Items Selected</span>

                @if(Auth::user()->hasPermission('projects.edit'))
                    <div class="d-flex align-items-center gap-2 border-start border-white-50 ps-3 ms-1">
                        <select wire:model.live="bulkStatus" class="form-select form-select-sm"
                            style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 28px 4px 10px; width: 120px; cursor: pointer;">
                            <option value="" style="color: black;">Status...</option>
                            @foreach ($statuses as $stat)
                                <option value="{{ $stat }}" style="color: black;">{{ ucfirst(str_replace('_', ' ', $stat)) }}
                                </option>
                            @endforeach
                        </select>
                        <button wire:click="bulkChangeStatus" class="btn-bulk-outline" @if (!$bulkStatus) disabled @endif>
                            Apply
                        </button>
                    </div>
                @endif

                @if(Auth::user()->hasPermission('projects.edit'))
                    <div class="d-flex align-items-center gap-2 border-start border-white-50 ps-3 ms-1">
                        <select wire:model.live="bulkManager" class="form-select form-select-sm"
                            style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 28px 4px 10px; width: 120px; cursor: pointer;">
                            <option value="" style="color: black;">Manager...</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" style="color: black;">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <button wire:click="bulkAssign" class="btn-bulk-outline" @if (!$bulkManager) disabled @endif>
                            Apply
                        </button>
                    </div>
                @endif

                @if(Auth::user()->hasPermission('projects.delete'))
                    <button wire:click="bulkDelete" onclick="return confirm('Are you sure?')"
                        class="btn-bulk-danger border-start border-white-50 ps-3 ms-1" style="border-radius: 0 6px 6px 0;">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                @endif
            </div>
            <button type="button" class="btn-deselect-all" wire:click="$set('selectedProjects', [])">
                Deselect All
            </button>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            @if(Auth::user()->hasPermission('projects.delete') || Auth::user()->hasPermission('projects.edit'))
                                <input type="checkbox" class="data-grid-checkbox"
                                    onclick="let checked = this.checked; document.querySelectorAll('.project-checkbox').forEach(c => { c.checked = checked; c.dispatchEvent(new Event('change')); })">
                            @endif
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('name')">
                            PROJECT NAME @if ($sortField === 'name') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th>MANAGER</th>
                        <th>TASKS</th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">
                            STATUS @if ($sortField === 'status') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('deadline')">
                            DEADLINE @if ($sortField === 'deadline') <i
                                class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"
                            style="font-size: 10px;"></i> @endif
                        </th>
                        <th class="text-end pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                                    <tr wire:key="project-row-{{ $project->id }}"
                                        class="{{ in_array($project->id, $selectedProjects) ? 'bg-primary-subtle' : '' }}"
                                        style="border-bottom: 1px solid var(--border-subtle);">
                                        <td>
                                            @if(Auth::user()->hasPermission('projects.delete') || Auth::user()->hasPermission('projects.edit'))
                                                <input type="checkbox" wire:model.live="selectedProjects" value="{{ $project->id }}"
                                                    class="data-grid-checkbox project-checkbox">
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if(Auth::user()->hasPermission('projects.view'))
                                                <a href="{{ route('admin.projects.show', $project->id) }}"
                                                    class="fw-bold text-decoration-none text-high d-block mb-1" style="font-size: 0.9rem;">
                                                    {{ $project->name }}
                                                </a>
                                            @else
                                                <span class="fw-bold text-high d-block mb-1"
                                                    style="font-size: 0.9rem;">{{ $project->name }}</span>
                                            @endif
                                            <div class="text-low mt-1 text-truncate" style="font-size: 0.75rem; max-width: 250px;">
                                                {{ $project->description
                        ? \Illuminate\Support\Str::limit(strip_tags($project->description), 100)
                        : 'No description'
                                                    }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @forelse($project->users->take(3) as $manager)
                                                    <div class="avatar-premium" title="{{ $manager->name }}"
                                                        style="width: 28px; height: 28px; font-size: 0.7rem; background: rgba(var(--primary-rgb), 0.1); color: var(--primary); margin-left: {{ $loop->first ? '0' : '-8px' }}; border: 2px solid var(--bg-surface); z-index: {{ 10 - $loop->index }};">
                                                        {{ substr($manager->name, 0, 1) }}
                                                    </div>
                                                @empty
                                                    <span class="text-low small italic">Unassigned</span>
                                                @endforelse
                                                @if($project->users->count() > 3)
                                                    <div class="avatar-premium"
                                                        style="width: 28px; height: 28px; font-size: 0.6rem; background: var(--border-main); color: var(--text-high); margin-left: -8px; border: 2px solid var(--bg-surface); z-index: 1;">
                                                        +{{ $project->users->count() - 3 }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-premium"
                                                style="background: var(--bg-input); color: var(--text-high); font-size: 0.7rem; padding: 4px 8px;">
                                                {{ $project->tasks_count }} Tasks
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'active' => 'var(--accent)',
                                                    'completed' => 'var(--primary)',
                                                    'on_hold' => '#f59e0b',
                                                    'archived' => 'var(--text-medium)'
                                                ];
                                                $themeColor = $statusColors[$project->status] ?? 'var(--text-medium)';
                                            @endphp
                                            <span class="badge-premium"
                                                style="background: color-mix(in srgb, {{ $themeColor }} 15%, transparent); color: {{ $themeColor }}; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                                                {{ str_replace('_', ' ', $project->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-medium small">
                                                {{ $project->deadline ? $project->deadline->format('M d, Y') : 'No Deadline' }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if(Auth::user()->hasPermission('projects.view'))
                                                    <a href="{{ route('admin.projects.show', $project->id) }}"
                                                        class="btn btn-sm shadow-none" style="color: var(--primary);" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                @if(Auth::user()->hasPermission('projects.edit'))
                                                    <a href="{{ route('admin.projects.edit', $project->id) }}"
                                                        class="btn btn-sm shadow-none" style="color: var(--accent);" title="Edit Project">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-low italic">
                                <div class="mb-3"><i class="fas fa-project-diagram fa-3x text-muted"
                                        style="opacity: 0.3;"></i></div>
                                No projects found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>


    </div>

    <!-- Filter Slideover -->
    <div class="filter-slideover" id="filterSlideoverProjects">
        <div class="h-100 d-flex flex-column">
            <div class="filter-slideover-header">
                <h4><i class="fas fa-sliders-h text-low me-2"></i> Advanced Filters</h4>
                <div class="filter-slideover-close"
                    onclick="document.getElementById('filterSlideoverProjects').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="filter-slideover-body">
                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">STATUS</label>
                    <x-select wire:model.live="status">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $stat)
                            <option value="{{ $stat }}">{{ ucfirst(str_replace('_', ' ', $stat)) }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="mb-4">
                    <label class="heading-label d-block mb-2 text-low">PROJECT MANAGER</label>
                    <x-select wire:model.live="user_id">
                        <option value="">Any Manager</option>
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
                    wire:click="$set('status', ''); $set('user_id', ''); $set('created_at', ''); $set('updated_at', '');"
                    class="btn-premium btn-premium-secondary w-50 justify-content-center bg-white text-dark border-main">Reset</button>
                <button type="button"
                    onclick="document.getElementById('filterSlideoverProjects').classList.remove('show')"
                    class="btn-premium btn-premium-primary w-50 justify-content-center"
                    style="background: #0ea5e9;">Apply Filters</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('openFilterModal', () => {
            document.getElementById('filterSlideoverProjects')?.classList.add('show');
        });
    </script>
</div>