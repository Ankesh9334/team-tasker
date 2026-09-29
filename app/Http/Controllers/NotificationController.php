<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead()
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function readAndRedirect($id)
    {
        $user = Auth::user() ?? Auth::guard('admin')->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            $data = $notification->data ?? [];

            if (! empty($data['conversation_id'])) {
                return redirect()->route('admin.chat.index', ['conversation_id' => $data['conversation_id']]);
            }
            if (! empty($data['user_id']) && str_contains($notification->type, 'Chat')) {
                return redirect()->route('admin.chat.index', ['user_id' => $data['user_id']]);
            }
            if (! empty($data['task_id'])) {
                return redirect()->route('details', $data['task_id']);
            }
            if (! empty($data['ticket_id'])) {
                return redirect()->route('admin.tickets.show', $data['ticket_id']);
            }
            if (str_contains($notification->type, 'Attendance')) {
                return redirect()->route('admin.attendance.daily');
            }
            if (str_contains($notification->type, 'Leave')) {
                return redirect()->route('admin.attendance.requests');
            }
        }

        return redirect()->back();
    }
}
