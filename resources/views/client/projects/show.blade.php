<x-client title="{{ $project->name }}">

    <div class="mb-4">
        <a href="{{ route('client.projects.index') }}" class="text-decoration-none text-low small mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> Back to Projects
        </a>
        <h1 class="h3 fw-semibold mb-1 text-high">{{ $project->name }}</h1>
        <div class="text-low" style="font-size: 0.9rem;">
            Project Overview &amp; Task Progress
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Project Details Card -->
        <div class="col-md-8">
            <div class="glass-card p-4 h-100" style="border: 1px solid var(--border-main);">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <h5 class="fw-semibold text-high mb-0">Project Information</h5>
                    @php
                        $sc = match($project->status) {
                            'active'    => ['rgba(16,185,129,0.1)', '#10b981'],
                            'completed' => ['rgba(14,165,233,0.1)', '#0ea5e9'],
                            'on_hold'   => ['rgba(245,158,11,0.1)', '#f59e0b'],
                            default     => ['#f1f5f9', '#64748b'],
                        };
                    @endphp
                    <span class="badge-premium" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:0.75rem; font-weight:700; padding:6px 12px; border-radius:6px; text-transform:uppercase;">
                        {{ str_replace('_', ' ', $project->status) }}
                    </span>
                </div>
                
                <div class="text-medium mb-4 pb-4 border-bottom border-subtle" style="line-height: 1.6; font-size: 0.9rem;">
                    {!! $project->description ?: '<em class="text-low">No description provided.</em>' !!}
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-low small mb-1">Start Date</div>
                        <div class="text-high fw-medium">{{ $project->start_date ? $project->start_date->format('F d, Y') : 'Not Set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-low small mb-1">Deadline</div>
                        <div class="text-danger fw-medium">{{ $project->deadline ? $project->deadline->format('F d, Y') : 'Not Set' }}</div>
                    </div>
                    <div class="col-sm-12 mt-3">
                        <div class="text-low small mb-2">Project Manager</div>
                        @if ($project->user)
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-premium" style="width:36px; height:36px; font-size:0.9rem;">
                                    {{ substr($project->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-high fw-medium">{{ $project->user->name }}</div>
                                    <div class="text-low" style="font-size: 0.75rem;">{{ $project->user->email }}</div>
                                </div>
                            </div>
                        @else
                            <span class="text-low italic small">Unassigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="col-md-4">
            <div class="glass-card p-4 h-100 d-flex flex-column" style="border: 1px solid var(--border-main);">
                <h5 class="fw-semibold text-high mb-4">Overall Progress</h5>
                
                <div class="text-center mb-4 flex-grow-1 d-flex flex-column justify-content-center">
                    <h2 class="display-4 fw-bold text-primary mb-2">{{ $progress }}%</h2>
                    <div class="progress mb-3" style="height: 10px; background: var(--border-main);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: {{ $progress }}%; background: var(--primary);"></div>
                    </div>
                    <div class="text-low small">Based on {{ $project->tasks->count() }} tasks</div>
                </div>

                <div class="row g-2 text-center border-top border-subtle pt-3">
                    <div class="col-6">
                        <div class="text-high fw-bold h4 mb-0">{{ $project->tasks->where('progress', 100)->count() }}</div>
                        <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase;">Completed</div>
                    </div>
                    <div class="col-6 border-start border-subtle">
                        <div class="text-high fw-bold h4 mb-0">{{ $project->tasks->where('progress', '<', 100)->count() }}</div>
                        <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase;">In Progress</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Tasks -->
    <h4 class="h5 fw-semibold mb-3 text-high mt-4">Project Tasks</h4>
    <div class="data-grid-wrapper mb-5">
        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>TASK</th>
                        <th>STATUS</th>
                        <th>PROGRESS</th>
                        <th>ASSIGNED TO</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->tasks as $task)
                        <tr>
                            <td>
                                <div class="text-high fw-medium mb-1">{{ Str::limit($task->title, 50) }}</div>
                                <div class="text-low" style="font-size: 0.75rem;">
                                    Due: <span class="{{ $task->due_date && $task->due_date->isPast() && $task->progress < 100 ? 'text-danger fw-semibold' : '' }}">
                                        {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No Date' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-premium" style="background:rgba(var(--primary-rgb),0.1); color:var(--primary); font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                    {{ $task->status->name ?? 'Pending' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2" style="max-width: 150px;">
                                    <div class="progress flex-grow-1" style="height: 6px; background: var(--border-main);">
                                        <div class="progress-bar" style="width: {{ $task->progress ?? 0 }}%; background: {{ ($task->progress ?? 0) == 100 ? '#10b981' : 'var(--primary)' }};"></div>
                                    </div>
                                    <span class="text-low small fw-semibold" style="width: 30px;">{{ $task->progress ?? 0 }}%</span>
                                </div>
                            </td>
                            <td>
                                @if ($task->assignedTo)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-premium" style="width:26px; height:26px; font-size:0.65rem;">
                                            {{ substr($task->assignedTo->name, 0, 1) }}
                                        </div>
                                        <span class="small" style="color:var(--text-medium);">{{ $task->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-low italic small">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-end pe-2">
                                <a href="{{ route('client.tasks.show', $task->id) }}" class="btn-premium btn-premium-secondary px-3 py-1 text-decoration-none" title="View Task Details">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-medium">
                                <i class="fas fa-tasks mb-2 d-block text-low" style="font-size: 2rem;"></i>
                                No tasks added to this project yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-client>
