<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskLogController extends Controller
{
    /**
     * Store a new task log (activity log).
     */
    public function storeLog(Request $request, $taskId)
    {
        $request->validate([
            'note' => 'required|string',
            'type' => 'nullable|string|in:log,message',
        ]);

        $task = Task::with('ticket.user', 'assignedTo')->findOrFail($taskId);

        $log = TaskLog::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'note' => $request->note,
            'type' => $request->type ?? 'log',
        ]);

        // Notify relevant parties
        $this->notifyParties($task, $log);

        return back()->with('success', 'Note added successfully');
    }

    /**
     * Send a message to the admin.
     */
    public function sendMessage(Request $request, $taskId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $task = Task::with('ticket.user', 'assignedTo')->findOrFail($taskId);

        // Store the message as a log entry of type 'message'
        $log = TaskLog::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'note' => $request->message,
            'type' => 'message',
        ]);

        // Notify relevant parties
        $this->notifyParties($task, $log);

        return back()->with('success', 'Message sent to admin successfully');
    }

    /**
     * Helper to notify parties about task updates.
     */
    private function notifyParties($task, $log)
    {
        try {
            // 1. Notify Client if linked to a Ticket
            if ($task->ticket) {
                $ticket = $task->ticket;
                $notification = new \App\Notifications\TaskReplyNotification($task, $log);

                if ($ticket->user) {
                    $ticket->user->notify($notification);
                } elseif ($ticket->email_source) {
                    \Illuminate\Support\Facades\Notification::route('mail', $ticket->email_source)
                        ->notify($notification);
                }
            }

            // 2. Notify Assigned User if log is by someone else
            if ($task->assignedTo && $task->assignedTo->id !== Auth::id()) {
                $task->assignedTo->notify(new \App\Notifications\TaskReplyNotification($task, $log));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send task log notification: '.$e->getMessage());
        }
    }

    /**
     * Start task timer.
     */
    public function startTime(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        // Prevent starting if not assigned to the user
        if ($task->assigned_to != Auth::id()) {
            return back()->with('error', 'This task is not assigned to you.');
        }

        // Check if there is already an active timer for THIS task
        $activeTimer = TimeLog::where('task_id', $taskId)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        if ($activeTimer) {
            return back()->with('error', 'A timer is already running for this task.');
        }

        // Check if there's any other global active timer
        $globalActiveTimer = TimeLog::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        if ($globalActiveTimer) {
            if ($request->has('force')) {
                // Stop the old global active timer
                $endTime = now();
                $startTime = $globalActiveTimer->start_time;
                $duration = abs($endTime->diffInSeconds($startTime));

                $globalActiveTimer->update([
                    'end_time' => $endTime,
                    'duration' => $duration,
                ]);
            } else {
                return back()->with('error', 'You already have an active timer on another task. Please stop it first.');
            }
        }

        TimeLog::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'start_time' => now(),
        ]);

        // Automatically set status to in_progress if it was pending
        if ($task->status == 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Timer started successfully');
    }

    /**
     * Stop task timer.
     */
    public function stopTime($taskId)
    {
        $activeTimer = TimeLog::where('task_id', $taskId)
            ->where('user_id', Auth::id())
            ->whereNull('end_time')
            ->latest()
            ->first();

        if (! $activeTimer) {
            return back()->with('error', 'No active timer found for this task.');
        }

        $endTime = now();
        $startTime = $activeTimer->start_time;
        $duration = abs($endTime->diffInSeconds($startTime));

        $activeTimer->update([
            'end_time' => $endTime,
            'duration' => $duration,
        ]);

        return back()->with('success', 'Timer stopped successfully. Duration: '.gmdate('H:i:s', $duration));
    }

    public function updateProgress(Request $request, $taskId)
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $task = Task::findOrFail($taskId);
        $status = \App\Models\Status::find($request->status_id);

        $oldProgress = $task->progress;

        $completed_at = $task->completed_at;
        if ($status && $status->is_completed && ! $completed_at) {
            $completed_at = now();
        } elseif ($status && ! $status->is_completed) {
            $completed_at = null;
        }

        $task->update([
            'progress' => $request->progress,
            'status_id' => $request->status_id,
            'completed_at' => $completed_at,
        ]);

        // If status changed to completed, force progress to 100
        if ($status && $status->is_completed && $request->progress < 100) {
            $task->update(['progress' => 100]);
        }

        // Log the change
        TaskLog::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'note' => 'Updated status to **'.$status->name.'** and progress to **'.$task->progress.'%**.',
            'type' => 'log',
        ]);

        return back()->with('success', 'Task progress updated successfully');
    }
}
