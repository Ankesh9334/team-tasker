<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return view('admin.projects.index');
    }

    public function create()
    {
        $users = User::all();

        return view('admin.projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $project = Project::create($request->except('user_ids'));

        if ($request->has('user_ids')) {
            $project->users()->sync($request->user_ids);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
    }

    public function show($id)
    {
        $project = Project::with(['tasks.status', 'tasks.assignedTo', 'tasks.users', 'users'])->findOrFail($id);
        $unassignedTasks = \App\Models\Task::whereNull('project_id')->orWhere('project_id', '!=', $project->id)->get();

        return view('admin.projects.show', compact('project', 'unassignedTasks'));
    }

    public function assignTask(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'task_id' => 'required|exists:tasks,id',
        ]);

        $task = \App\Models\Task::findOrFail($request->task_id);
        $task->project_id = $project->id;
        $task->save();

        // Auto-assign project managers as task followers
        $projectManagerIds = $project->users->pluck('id')->toArray();
        $task->users()->syncWithoutDetaching($projectManagerIds);

        return back()->with('success', 'Task successfully assigned to the project.');
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $users = User::all();

        return view('admin.projects.edit', compact('project', 'users'));
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $project->update($request->except('user_ids'));

        if ($request->has('user_ids')) {
            $project->users()->sync($request->user_ids);
        } else {
            $project->users()->detach();
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully.');
    }
}
