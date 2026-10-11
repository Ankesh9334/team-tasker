<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    private function getClientId()
    {
        return Auth::guard('client')->check() ? Auth::guard('client')->id() : null;
    }

    private function getUserId()
    {
        return Auth::guard('web')->check() ? Auth::id() : null;
    }

    public function dashboard(Request $request)
    {
        $clientId = $this->getClientId();
        $userId = $this->getUserId();

        // ── Ticket query ──────────────────────────────────────────────────────
        $ticketQuery = Ticket::where(function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('id', -1);
            }
        });

        if ($request->filled('ticket_search')) {
            $ticketQuery->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%'.$request->ticket_search.'%')
                    ->orWhere('body', 'like', '%'.$request->ticket_search.'%');
            });
        }
        if ($request->filled('ticket_status')) {
            $ticketQuery->where('status', $request->ticket_status);
        }
        if ($request->filled('ticket_priority')) {
            $ticketQuery->where('priority', $request->ticket_priority);
        }
        if ($request->filled('ticket_date')) {
            $ticketQuery->whereDate('created_at', $request->ticket_date);
        }

        $tickets = $ticketQuery->latest()->paginate(request('per_page', 10), ['*'], 'tickets_page');

        // ── Task query ────────────────────────────────────────────────────────
        $ticketIds = Ticket::where(function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('id', -1);
            }
        })->pluck('id');

        $taskQuery = \App\Models\Task::whereIn('ticket_id', $ticketIds)
            ->with(['status', 'assignedTo']);

        if ($request->filled('task_search')) {
            $taskQuery->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->task_search.'%')
                    ->orWhere('description', 'like', '%'.$request->task_search.'%');
            });
        }

        $tasks = $taskQuery->latest()->paginate(request('per_page', 10), ['*'], 'tasks_page');

        return view('client.dashboard', compact('tickets', 'tasks'));
    }

    public function create()
    {
        return view('client.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        Ticket::create([
            'client_id' => $this->getClientId(),
            'user_id' => $this->getUserId(),
            'subject' => $request->subject,
            'body' => $request->body,
            'status' => 'open',
            'priority' => $request->priority,
        ]);

        return redirect()->route('client.dashboard')->with('success', 'Ticket submitted successfully.');
    }

    public function show($id)
    {
        $clientId = $this->getClientId();
        $userId = $this->getUserId();

        $ticket = Ticket::with(['replies.user', 'replies.client'])->where(function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            }
        })->findOrFail($id);

        return view('client.tickets.show', compact('ticket'));
    }

    public function showTask($id)
    {
        $clientId = $this->getClientId();
        $userId = $this->getUserId();

        $task = \App\Models\Task::with(['status', 'assignedTo', 'attachments', 'logs' => function ($q) {
            $q->where('type', 'message')->with(['user', 'client'])->latest();
        }])->whereHas('ticket', function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            }
        })->findOrFail($id);

        return view('client.tasks.show', compact('task'));
    }

    public function replyTask(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        $clientId = $this->getClientId();
        $userId = $this->getUserId();

        $task = \App\Models\Task::whereHas('ticket', function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            }
        })->findOrFail($id);

        \App\Models\TaskLog::create([
            'task_id' => $task->id,
            'client_id' => $clientId,
            'user_id' => $userId,
            'note' => $request->note,
            'type' => 'message', // Client messages are always public
        ]);

        return back()->with('success', 'Reply sent successfully');
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['body' => 'required|string']);

        $clientId = $this->getClientId();
        $userId = $this->getUserId();

        $ticket = Ticket::where('id', $id)->where(function ($q) use ($clientId, $userId) {
            if ($clientId) {
                $q->where('client_id', $clientId);
            } elseif ($userId) {
                $q->where('user_id', $userId);
            }
        })->firstOrFail();

        $ticket->replies()->create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'body' => $request->body,
            'type' => 'client_reply',
        ]);

        if ($ticket->status == 'resolved' || $ticket->status == 'closed') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateProfile(Request $request)
    {
        $client = Auth::guard('client')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,'.$client->id.'|unique:users,email',
            'password' => 'nullable|string|min:8',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_image')) {
            if ($client->profile_image) {
                Storage::disk('public')->delete($client->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $client->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function markNotificationsRead()
    {
        $client = Auth::guard('client')->user();
        if ($client) {
            $client->unreadNotifications->markAsRead();
        }

        return back()->with('success', 'Notifications marked as read.');
    }

    public function readAndRedirect($id)
    {
        $client = Auth::guard('client')->user();
        if (! $client) {
            return redirect()->route('client.login');
        }

        $notification = $client->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            $data = $notification->data ?? [];

            if (! empty($data['conversation_id'])) {
                return redirect()->route('client.chat.index', ['conversation_id' => $data['conversation_id']]);
            }
            if (! empty($data['task_id'])) {
                return redirect()->route('client.tasks.show', $data['task_id']);
            }
            if (! empty($data['ticket_id'])) {
                return redirect()->route('client.tickets.show', $data['ticket_id']);
            }
        }

        return redirect()->back();
    }
}
