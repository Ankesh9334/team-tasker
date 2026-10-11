<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('user', 'assignedTo')->latest();

        $user = auth()->user();
        $isTicketAdmin = $user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasPermission('tickets.view_all');
        if (! $isTicketAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('assigned_to')) {
            $assignedTo = $request->assigned_to;
            $ids = is_array($assignedTo) ? array_filter($assignedTo) : [$assignedTo];
            if (! empty($ids)) {
                $query->whereIn('assigned_to', $ids);
            }
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($request->filled('updated_at')) {
            $query->whereDate('updated_at', $request->updated_at);
        }

        $tickets = $query->paginate(request('per_page', 15));
        $allUsers = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.tickets.index', compact('tickets', 'allUsers'));
    }

    public function create()
    {
        $clients = \App\Models\Client::select('id', 'name', 'email')->orderBy('name')->get();
        $staff = User::select('id', 'name')->where('role_id', '!=', 3)->orderBy('name')->get();

        return view('admin.tickets.create', compact('clients', 'staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'body' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'attachments' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachments')) {
            $attachmentPath = $request->file('attachments')->store('attachments', 'public');
        }

        Ticket::create([
            'client_id' => $request->client_id,
            'subject' => $request->subject,
            'priority' => $request->priority,
            'body' => $request->body,
            'status' => 'open',
            'assigned_to' => $request->assigned_to,
            'attachments' => $attachmentPath,
        ]);

        return redirect()->route('admin.tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'assignedTo', 'replies.user'])->findOrFail($id);
        $users = User::select('id', 'name')->where('role_id', '!=', 3)->orderBy('name')->get(); // Assuming 3 is client, list staff for assignment
        $projects = \App\Models\Project::all();

        return view('admin.tickets.show', compact('ticket', 'users', 'projects'));
    }

    public function storeReply(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string',
            'attachments' => 'nullable|file|max:10240',
        ]);

        $ticket = Ticket::findOrFail($id);

        $attachmentPath = null;
        if ($request->hasFile('attachments')) {
            $attachmentPath = $request->file('attachments')->store('attachments', 'public');
        }

        $isInternal = $request->has('is_internal_note');

        $ticket->replies()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
            'type' => 'internal', // Staff reply
            'is_private' => $isInternal,
            'attachments' => $attachmentPath,
        ]);

        // Auto-update status if it's a public reply
        if (! $isInternal) {
            $ticket->update(['status' => 'waiting_for_client']);

            // Notify Client - Wrap in try-catch to prevent crash on mail error
            try {
                if ($ticket->user) {
                    $ticket->user->notify(new TicketReplyNotification($ticket, $ticket->replies()->latest()->first()));
                } elseif ($ticket->email_source) {
                    Notification::route('mail', $ticket->email_source)
                        ->notify(new TicketReplyNotification($ticket, $ticket->replies()->latest()->first()));
                }
            } catch (\Exception $e) {
                // Log the error but don't stop execution
                \Illuminate\Support\Facades\Log::error('Failed to send ticket reply email: '.$e->getMessage());

                return back()->with('success', 'Reply posted successfully, but email notification failed to send.');
            }
        }

        return back()->with('success', 'Reply posted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|in:open,in_progress,waiting_for_client,closed,resolved',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($request->status) {
            $ticket->update(['status' => $request->status]);
        }

        if ($request->priority) {
            $ticket->update(['priority' => $request->priority]);
        }

        return back()->with('success', 'Ticket updated successfully.');
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update(['assigned_to' => $request->assigned_to]);

        // Notify Assigned User
        if ($request->assigned_to) {
            $user = User::find($request->assigned_to);
            if ($user && $user->id != Auth::id()) { // Don't notify self
                $user->notify(new TicketAssigned($ticket));
            }
        }

        return back()->with('success', 'Ticket assigned successfully.');
    }

    public function convertToTask(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $task = \App\Models\Task::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(), // Created by current staff
            'client_id' => $ticket->client_id,
            'assigned_to' => $ticket->assigned_to,
            'title' => $ticket->subject,
            'description' => $ticket->body,
            'status_id' => \App\Models\Status::where('is_default', true)->first()?->id,
            'priority' => $ticket->priority,
            'project_id' => $request->project_id,
        ]);

        if ($request->project_id) {
            $project = \App\Models\Project::with('users')->find($request->project_id);
            if ($project) {
                $projectManagerIds = $project->users->pluck('id')->toArray();
                if (! empty($projectManagerIds)) {
                    $task->users()->sync($projectManagerIds);
                }
            }
        }

        return back()->with('success', 'Ticket converted to task successfully. Task ID: '.$task->id);
    }
}
