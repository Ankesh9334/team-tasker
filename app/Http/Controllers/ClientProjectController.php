<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientProjectController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('client')->user();
        
        $query = Project::where('client_id', $client->id)->with('user', 'tasks');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate(10)->withQueryString();

        return view('client.projects.index', compact('projects'));
    }

    public function show($id)
    {
        $client = Auth::guard('client')->user();
        
        $project = Project::where('client_id', $client->id)
            ->with(['user', 'tasks' => function($q) {
                $q->with('assignedTo', 'status')->latest();
            }])
            ->findOrFail($id);
            
        // Calculate progress based on tasks
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('progress', 100)->count();
        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return view('client.projects.show', compact('project', 'progress'));
    }
}
