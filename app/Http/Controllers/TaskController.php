<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskDependency;
use App\Models\TaskTemplate;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.tasks.index');
    }

    /**
     * Display the Global Task Activity Feed.
     */
    public function activity(Request $request)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        $query = \App\Models\TaskLog::with(['task', 'project', 'user', 'client'])->latest();

        // If not an admin with view_all permission, filter tasks and projects user can see
        if (! \Illuminate\Support\Facades\Auth::user()->hasPermission('tasks.view_all')) {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('task', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhere('assigned_to', $userId);
                })->orWhereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            });
        }

        // Filter by user(s)
        $filterUser = $request->input('user_id');
        if (! empty($filterUser)) {
            if (is_array($filterUser)) {
                $cleanUserIds = array_values(array_filter(array_map('intval', $filterUser)));
                if (! empty($cleanUserIds)) {
                    $query->whereIn('user_id', $cleanUserIds);
                }
            } else {
                $query->where('user_id', (int) $filterUser);
            }
        }

        // Filter by search keyword
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('task', fn ($tq) => $tq->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by date
        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        // Base metrics query (before type filter so stats cards stay comprehensive)
        $baseForStats = clone $query;
        $totalCount = (clone $baseForStats)->count();
        $systemLogsCount = (clone $baseForStats)->where('type', 'log')->count();
        $clientMessagesCount = (clone $baseForStats)->where('type', 'message')->count();
        $activeUsersCount = (clone $baseForStats)->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        $activityStats = [
            'total' => $totalCount,
            'logs' => $systemLogsCount,
            'messages' => $clientMessagesCount,
            'users' => $activeUsersCount,
        ];

        // Filter by activity type (log vs message)
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->input('per_page', 20);
        $activities = $query->paginate($perPage > 0 ? $perPage : 20)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email', 'profile_image']);

        return view('admin.tasks.activity', compact('activities', 'users', 'activityStats'));
    }

    /**
     * Display the Kanban Board.
     */
    public function board()
    {
        return view('admin.tasks.board');
    }

    /**
     * Display the Calendar View.
     */
    public function calendar()
    {
        return view('admin.tasks.calendar');
    }

    /**
     * Fetch events for FullCalendar.
     */
    public function calendarEvents(Request $request)
    {
        $userId = Auth::id();
        $tasks = Task::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('assigned_to', $userId)
                ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
        })
            ->whereNotNull('deadline')
            ->get();

        $events = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->deadline->toIso8601String(),
                'allDay' => true,
                'extendedProps' => [
                    'priority' => $task->priority,
                    'status' => $task->status->name ?? 'Unknown',
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Display the Gantt Chart View.
     */
    public function gantt()
    {
        return view('admin.tasks.gantt');
    }

    /**
     * Fetch data for Frappe Gantt.
     */
    public function ganttData()
    {
        $userId = Auth::id();
        $tasks = Task::with('dependencies')
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
            })
            ->whereNotNull('deadline')
            ->get();

        $data = $tasks->map(function ($task) {
            $start = $task->created_at->format('Y-m-d');
            $end = $task->deadline->format('Y-m-d');

            if ($start == $end) {
                $end = $task->deadline->addDay()->format('Y-m-d');
            }

            return [
                'id' => (string) $task->id,
                'name' => $task->title,
                'start' => $start,
                'end' => $end,
                'progress' => $task->progress ?? 0,
                'dependencies' => $task->dependencies->pluck('depends_on_id')
                    ->map(fn ($id) => (string) $id)->implode(', '),
            ];
        });

        return response()->json($data);
    }

    /**
     * API for searching users (AJAX)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);

        $query = \App\Models\User::query();

        if (! empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->select('id', 'name', 'role_id')->paginate(20, ['*'], 'page', $page);

        $formattedUsers = $users->map(function ($user) {
            $roleLabel = $user->role_id == 1 ? 'Admin' : 'User';

            return [
                'id' => $user->id,
                'name' => "{$user->name} ({$roleLabel})",
            ];
        });

        return response()->json([
            'items' => $formattedUsers,
            'total_count' => $users->total(),
            'has_more' => $users->hasMorePages(),
            'next_page' => $users->hasMorePages() ? $page + 1 : null,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('client.dashboard');
        }

        $userId = Auth::user()->id;
        // Admins see everything. Others see only their assigned/owned items.
        $isAdmin = Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('admin') || Auth::user()->hasPermission('tasks.view_all');
        $isTicketAdmin = Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('admin') || Auth::user()->hasPermission('tickets.view_all');

        $viewUserId = request('view_user_id');
        $viewUser = null;

        // Impersonation for dashboard metrics
        if ($viewUserId && (Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('admin'))) {
            $userId = $viewUserId;
            $isAdmin = false; // Act as standard user to filter metrics
            $isTicketAdmin = false;
            $viewUser = \App\Models\User::find($userId);
        }

        // Personal tasks for the list
        $personalTasks = Task::with(['user', 'assignedTo', 'status'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $taskQuery = Task::query();
        if (! $isAdmin) {
            $taskQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
            });
        }

        // Metrics
        $totalTasks = (clone $taskQuery)->count();
        $completedTasksCount = (clone $taskQuery)->whereHas('status', function ($q) {
            $q->where('slug', 'completed')->orWhere('name', 'Completed');
        })->count();

        $pendingTasksCount = $totalTasks - $completedTasksCount;

        $ticketQuery = \App\Models\Ticket::query();
        if (! $isTicketAdmin) {
            $ticketQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('assigned_to', $userId);
            });
        }
        $totalTickets = $ticketQuery->count();

        $totalUsers = \App\Models\User::count();
        $projectQuery = \App\Models\Project::query();
        if (! $isAdmin) {
            $projectQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('tasks', function ($q2) use ($userId) {
                        $q2->where('assigned_to', $userId);
                    });
            });
        }
        $totalProjects = $projectQuery->count();
        $criticalTasksCount = (clone $taskQuery)->where('priority', 'Critical')->count();

        $projectProgress = $totalTasks > 0 ? (clone $taskQuery)->avg('progress') : 0;

        // Recent Activity
        $activityQuery = \App\Models\TaskLog::with(['user', 'task', 'project']);

        if ($viewUserId && (Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('admin'))) {
            // When an admin is viewing AS a specific user, show the activity performed BY that user
            $activityQuery->where('user_id', $userId);
        } elseif (! $isAdmin) {
            // Standard user logic: show activity on tasks/projects they are involved in
            $activityQuery->where(function ($q) use ($userId) {
                $q->whereHas('task', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhere('assigned_to', $userId)
                        ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
                })->orWhereHas('project', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            });
        }
        $recentActivities = $activityQuery->latest()->take(6)->get();

        // Data for chart: Multiple time periods
        $chart7d = $this->buildChartData(7, 'day', 'D, M j', $isAdmin, $userId);
        $chart30d = $this->buildChartData(30, 'day', 'M j', $isAdmin, $userId);
        $chart90d = $this->buildChartData(90, 'week', 'M j', $isAdmin, $userId);
        $chartAll = $this->buildChartDataAllTime($isAdmin, $userId);

        // Default view: Last 7 days
        $chartData = $chart7d['data'];
        $chartLabels = $chart7d['labels'];

        $allUsers = \App\Models\User::all(); // For the dropdown

        // Attendance Integrations
        $attendanceService = app(\App\Services\AttendanceService::class);
        $todayStr = $attendanceService->today()->format('Y-m-d');
        $attendanceStats = $isAdmin ? $attendanceService->getDailyStats($todayStr) : null;

        $currentUser = $viewUser ?: Auth::user();
        $userAttendanceToday = \App\Models\Attendance::where('user_id', $currentUser->id)
            ->where(function ($q) use ($todayStr) {
                $q->where('attendance_date', $todayStr)->orWhere('date', $todayStr);
            })
            ->first();
        $userMonthlyStats = $attendanceService->getMonthlyUserStats($currentUser, $attendanceService->today()->format('Y-m'));

        return view('admin.dashboard', compact(
            'personalTasks',
            'totalTasks',
            'completedTasksCount',
            'pendingTasksCount',
            'totalTickets',
            'totalUsers',
            'totalProjects',
            'criticalTasksCount',
            'projectProgress',
            'recentActivities',
            'chartData',
            'chartLabels',
            'chart7d',
            'chart30d',
            'chart90d',
            'chartAll',
            'viewUser',
            'allUsers',
            'isAdmin',
            'isTicketAdmin',
            'attendanceStats',
            'userAttendanceToday',
            'userMonthlyStats'
        ));
    }

    /**
     * Build chart data for a given period.
     */
    private function buildChartData(int $days, string $groupBy, string $labelFormat, bool $isAdmin = true, $userId = null): array
    {
        $labels = [];
        $data = [];

        if ($groupBy === 'day') {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format($labelFormat);
                $query = Task::whereDate('created_at', $date->toDateString());
                if (! $isAdmin) {
                    $query->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                            ->orWhere('assigned_to', $userId)
                            ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
                    });
                }
                $data[] = $query->count();
            }
        } elseif ($groupBy === 'week') {
            $weeks = (int) ceil($days / 7);
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $start = now()->subWeeks($i)->startOfWeek();
                $end = now()->subWeeks($i)->endOfWeek();
                $labels[] = $start->format($labelFormat);
                $query = Task::whereBetween('created_at', [$start, $end]);
                if (! $isAdmin) {
                    $query->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                            ->orWhere('assigned_to', $userId)
                            ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
                    });
                }
                $data[] = $query->count();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Build All Time chart data grouped by month.
     */
    private function buildChartDataAllTime(bool $isAdmin = true, $userId = null): array
    {
        $query = Task::query();
        $oldest = $query->min('created_at');
        if (! $oldest) {
            return ['labels' => [], 'data' => []];
        }

        $start = \Carbon\Carbon::parse($oldest)->startOfMonth();
        $end = now()->endOfMonth();
        $labels = [];
        $data = [];

        while ($start->lessThanOrEqualTo($end)) {
            $labels[] = $start->format('M Y');
            $q = Task::whereYear('created_at', $start->year)->whereMonth('created_at', $start->month);
            if (! $isAdmin) {
                $q->where(function ($sq) use ($userId) {
                    $sq->where('user_id', $userId)
                        ->orWhere('assigned_to', $userId)
                        ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
                });
            }
            $data[] = $q->count();
            $start->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        ini_set('memory_limit', '256M');

        $selectedProjectId = $request->get('project_id');
        $selectedParentId = $request->get('parent_id');

        // Staff users with eager loaded role, selecting only required columns
        $users = User::select('id', 'name', 'role_id')
            ->where(function ($q) {
                $q->whereNull('role_id')->orWhere('role_id', '!=', 3);
            })
            ->with('role:id,name')
            ->orderBy('name')
            ->get();

        $statuses = Status::orderBy('order')->get();
        $tags = Tag::all();

        // Top-level parent tasks scoped by project or latest 100
        $parentTasksQuery = Task::whereNull('parent_id');
        if ($selectedProjectId) {
            $parentTasksQuery->where('project_id', $selectedProjectId);
        }
        $parentTasks = $parentTasksQuery->select('id', 'title')->latest('id')->limit(100)->get();
        if ($selectedParentId && ! $parentTasks->contains('id', $selectedParentId)) {
            $currentParent = Task::select('id', 'title')->find($selectedParentId);
            if ($currentParent) {
                $parentTasks->prepend($currentParent);
            }
        }

        // All tasks for blockers/dependencies scoped by project or latest 100
        $allTasksQuery = Task::query();
        if ($selectedProjectId) {
            $allTasksQuery->where('project_id', $selectedProjectId);
        }
        $allTasks = $allTasksQuery->select('id', 'title')->latest('id')->limit(100)->get();

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $templates = TaskTemplate::where('is_active', true)->get();
        $projects = \App\Models\Project::select('id', 'name')->orderBy('name')->get();

        return view('admin.tasks.create', compact('users', 'statuses', 'tags', 'parentTasks', 'allTasks', 'priorities', 'selectedParentId', 'templates', 'projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'required|exists:statuses,id',
            'priority' => 'nullable|string',
            'parent_id' => 'nullable|exists:tasks,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:tasks,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB limit
            'is_recurring' => 'nullable|boolean',
            'recurring_interval' => 'nullable|string|in:daily,weekly,monthly,yearly',
        ]);

        $status = Status::find($request->status_id);

        $task = Task::create([
            'user_id' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'status_id' => $request->status_id,
            'priority' => $request->priority,
            'parent_id' => $request->parent_id,
            'estimated_hours' => $request->estimated_hours,
            'deadline' => $request->deadline,
            'is_recurring' => $request->boolean('is_recurring'),
            'recurring_interval' => $request->recurring_interval,
            'project_id' => $request->project_id,
            'completed_at' => ($status && $status->is_completed) ? now() : null,
        ]);

        $additionalUsers = $request->additional_users ?? [];
        if ($request->project_id) {
            $project = \App\Models\Project::with('users')->find($request->project_id);
            if ($project) {
                $projectManagerIds = $project->users->pluck('id')->toArray();
                $additionalUsers = array_unique(array_merge($additionalUsers, $projectManagerIds));
            }
        }

        if (! empty($additionalUsers)) {
            $task->users()->sync($additionalUsers);
        }

        // Notify Assigned User
        if ($task->assigned_to && $task->assigned_to != Auth::id()) {
            $task->assignedTo->notify(new TaskAssigned($task));
        }

        if ($request->tags) {
            $task->tags()->sync($request->tags);
        }

        if ($request->dependencies) {
            foreach ($request->dependencies as $depId) {
                TaskDependency::create([
                    'task_id' => $task->id,
                    'depends_on_id' => $depId,
                    'type' => 'blocker',
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                TaskAttachment::create([
                    'task_id' => $task->id,
                    'user_id' => Auth::id(),
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('index')->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $task = Task::with([
            'user',
            'assignedTo',
            'logs.user',
            'timeLogs.user',
            'subtasks.status',
            'subtasks.assignedTo',
            'dependencies.blocker.status',
            'attachments.user',
            'tags',
            'users',
        ])->findOrFail($id);

        $activeTimer = TimeLog::where('task_id', $task->id)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        $globalActiveTimer = TimeLog::with('task')
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        $statuses = Status::orderBy('order')->get();

        return view('admin.tasks.details', compact('task', 'activeTimer', 'globalActiveTimer', 'statuses'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        ini_set('memory_limit', '256M');

        $task = Task::with(['tags', 'dependencies', 'attachments', 'users'])->findOrFail($id);
        $taskUsers = $task->users->pluck('id')->toArray();

        // Selected user IDs that must be present in the dropdown
        $selectedUserIds = array_filter(array_unique(array_merge(
            [$task->assigned_to],
            $taskUsers
        )));

        // Staff users with eager loaded role, selecting only required columns
        $users = User::select('id', 'name', 'role_id')
            ->where(function ($q) use ($selectedUserIds) {
                $q->where(function ($sq) {
                    $sq->whereNull('role_id')->orWhere('role_id', '!=', 3);
                });
                if (! empty($selectedUserIds)) {
                    $q->orWhereIn('id', $selectedUserIds);
                }
            })
            ->with('role:id,name')
            ->orderBy('name')
            ->get();

        $statuses = Status::orderBy('order')->get();
        $tags = Tag::all();

        // Parent tasks for subtasks: scoped by project or latest 100
        $parentTasksQuery = Task::whereNull('parent_id')->where('id', '!=', $id);
        if ($task->project_id) {
            $parentTasksQuery->where('project_id', $task->project_id);
        }
        $parentTasks = $parentTasksQuery->select('id', 'title')->latest('id')->limit(100)->get();
        if ($task->parent_id && ! $parentTasks->contains('id', $task->parent_id)) {
            $currentParent = Task::select('id', 'title')->find($task->parent_id);
            if ($currentParent) {
                $parentTasks->prepend($currentParent);
            }
        }

        // All tasks for blockers/dependencies: scoped by project or latest 100
        $depIds = $task->dependencies->pluck('depends_on_id')->toArray();
        $allTasksQuery = Task::where('id', '!=', $id);
        if ($task->project_id) {
            $allTasksQuery->where('project_id', $task->project_id);
        }
        $allTasks = $allTasksQuery->select('id', 'title')->latest('id')->limit(100)->get();
        if (! empty($depIds)) {
            $missingDepIds = array_diff($depIds, $allTasks->pluck('id')->toArray());
            if (! empty($missingDepIds)) {
                $missingTasks = Task::whereIn('id', $missingDepIds)->select('id', 'title')->get();
                $allTasks = $allTasks->concat($missingTasks);
            }
        }

        $priorities = ['Low', 'Medium', 'High', 'Critical'];
        $templates = TaskTemplate::where('is_active', true)->get();
        $projects = \App\Models\Project::select('id', 'name')->orderBy('name')->get();

        return view('admin.tasks.edit', compact('task', 'users', 'statuses', 'tags', 'parentTasks', 'allTasks', 'priorities', 'templates', 'projects', 'taskUsers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'required|exists:statuses,id',
            'priority' => 'nullable|string',
            'parent_id' => 'nullable|exists:tasks,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:tasks,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer',
            'project_id' => 'nullable|exists:projects,id',
            'additional_users' => 'nullable|array',
            'additional_users.*' => 'exists:users,id',
        ]);

        $status = Status::find($request->status_id);
        $completed_at = $task->completed_at;

        if ($status && $status->is_completed && ! $completed_at) {
            $completed_at = now();
        } elseif ($status && ! $status->is_completed) {
            $completed_at = null;
        }

        $task->update([
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'status_id' => $request->status_id,
            'priority' => $request->priority,
            'parent_id' => $request->parent_id,
            'estimated_hours' => $request->estimated_hours,
            'deadline' => $request->deadline,
            'is_recurring' => $request->boolean('is_recurring'),
            'recurring_interval' => $request->recurring_interval,
            'completed_at' => $completed_at,
            'project_id' => $request->project_id,
        ]);

        $additionalUsers = $request->additional_users ?? [];
        if ($request->project_id) {
            $project = \App\Models\Project::with('users')->find($request->project_id);
            if ($project) {
                $projectManagerIds = $project->users->pluck('id')->toArray();
                $additionalUsers = array_unique(array_merge($additionalUsers, $projectManagerIds));
            }
        }

        if (! empty($additionalUsers)) {
            $task->users()->sync($additionalUsers);
        } else {
            $task->users()->detach();
        }

        // Notify Assigned User if changed
        if ($task->wasChanged('assigned_to') && $task->assigned_to && $task->assigned_to != Auth::id()) {
            $task->assignedTo->notify(new TaskAssigned($task));
        }

        if ($request->has('tags')) {
            $task->tags()->sync($request->tags);
        }

        if ($request->has('dependencies')) {
            // Simple sync for dependencies
            TaskDependency::where('task_id', $task->id)->delete();
            foreach ($request->dependencies as $depId) {
                TaskDependency::create([
                    'task_id' => $task->id,
                    'depends_on_id' => $depId,
                    'type' => 'blocker',
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                TaskAttachment::create([
                    'task_id' => $task->id,
                    'user_id' => Auth::id(),
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('index')->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $task = Task::where('id', $request->id)->first()->delete();

        return to_route('index')->with('success', 'Data deleted successfully');
    }
}
