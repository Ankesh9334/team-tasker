<x-client title="My Projects">

    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">My Projects</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Track the progress of your ongoing projects.</p>
        </div>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-search">
                <i class="fas fa-search"></i>
                <form action="{{ route('client.projects.index') }}" method="GET" id="searchForm">
                    <input type="text" name="search" placeholder="Search projects..."
                        value="{{ request('search') }}"
                        onchange="document.getElementById('searchForm').submit()">
                </form>
            </div>
            <div class="data-grid-results">{{ $projects->total() }} Results</div>
            <div class="data-grid-actions">
                {{ $projects->links() }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>PROJECT DETAILS</th>
                        <th>STATUS</th>
                        <th>TIMELINE</th>
                        <th>MANAGER</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <div class="text-high fw-medium mb-1">{{ $project->name }}</div>
                                <div class="text-low" style="font-size: 0.75rem;">{{ Str::limit(strip_tags($project->description), 60) }}</div>
                                @php
                                    $totalTasks = $project->tasks->count();
                                    $completedTasks = $project->tasks->where('progress', 100)->count();
                                    $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                                @endphp
                                <div class="d-flex align-items-center mt-2 gap-2" style="max-width: 200px;">
                                    <div class="progress flex-grow-1" style="height: 6px; background: var(--border-main);">
                                        <div class="progress-bar" style="width: {{ $progress }}%; background: var(--primary);"></div>
                                    </div>
                                    <span class="text-low small fw-semibold">{{ $progress }}%</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $sc = match($project->status) {
                                        'active'    => ['rgba(16,185,129,0.1)', '#10b981'],
                                        'completed' => ['rgba(14,165,233,0.1)', '#0ea5e9'],
                                        'on_hold'   => ['rgba(245,158,11,0.1)', '#f59e0b'],
                                        default     => ['#f1f5f9', '#64748b'],
                                    };
                                @endphp
                                <span class="badge-premium" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:0.65rem; font-weight:700; padding:4px 10px; border-radius:4px; text-transform:uppercase;">
                                    {{ str_replace('_', ' ', $project->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-low d-inline-block" style="width: 45px;">Start:</span>
                                    <span class="text-high fw-medium">{{ $project->start_date ? $project->start_date->format('M d, Y') : '--' }}</span>
                                </div>
                                <div class="small mt-1">
                                    <span class="text-low d-inline-block" style="width: 45px;">Due:</span>
                                    <span class="text-danger fw-medium">{{ $project->deadline ? $project->deadline->format('M d, Y') : '--' }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($project->user)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-premium" style="width:30px; height:30px; font-size:0.75rem;">
                                            {{ substr($project->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-high fw-medium small">{{ $project->user->name }}</div>
                                            <div class="text-low" style="font-size: 0.7rem;">Project Manager</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-low italic small">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-end pe-2">
                                <a href="{{ route('client.projects.show', $project->id) }}" class="btn-premium btn-premium-secondary px-3 py-1 text-decoration-none">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-medium">
                                <i class="fas fa-project-diagram mb-2 d-block text-low" style="font-size: 2rem;"></i>
                                No projects assigned yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-client>
