<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        $tasks = collect();
        $users = collect();
        $isClient = \Auth::guard('client')->check();
        $user = $isClient ? \Auth::guard('client')->user() : \Auth::user();

        if ($query) {
            // Search Tasks
            $taskQuery = \App\Models\Task::where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });

            if ($isClient) {
                // Clients only see tasks linked to their tickets
                $ticketIds = \App\Models\Ticket::where($isClient ? 'client_id' : 'user_id', $user->id)->pluck('id');
                $taskQuery->whereIn('ticket_id', $ticketIds);
            } else {
                // Admins/Staff
                $taskQuery->where(function ($q) {
                    $q->where('user_id', ($isClient ? \Auth::guard('client')->id() : \Auth::id()))
                        ->orWhere('assigned_to', ($isClient ? \Auth::guard('client')->id() : \Auth::id()));
                });
            }

            $tasks = $taskQuery->with(['status', 'assignedTo'])->take(20)->get();

            // Search Tickets
            $ticketQuery = \App\Models\Ticket::where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            });

            if ($isClient) {
                $ticketQuery->where($isClient ? 'client_id' : 'user_id', $user->id);
            }

            $tickets = $ticketQuery->with(['user'])->latest()->take(20)->get();

            // Search Users (if has permission)
            if ($user->hasPermission('users.view')) {
                $users = \App\Models\User::where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->with('role')
                    ->take(20)
                    ->get();
            }
        } else {
            $tickets = collect();
        }

        $layout = $isClient ? 'client' : 'admin';

        return view('admin.search.results', compact('tasks', 'users', 'tickets', 'query', 'layout'));
    }
}
