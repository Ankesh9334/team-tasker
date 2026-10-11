<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $leaveRequest;

    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(AttendanceRequest $leaveRequest, $action)
    {
        $this->leaveRequest = $leaveRequest;
        $this->action = $action; // 'applied', 'approved', 'rejected'
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', Channels\FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage;

        if ($this->action === 'applied') {
            $message->subject('New Leave Request')
                ->line('A new leave request has been submitted by '.$this->leaveRequest->user->name)
                ->action('View Requests', url('/admin/attendance/requests'));
        } else {
            $message->subject('Leave Request '.ucfirst($this->action))
                ->line('Your leave request for '.$this->leaveRequest->start_date.' has been '.$this->action.'.')
                ->action('View Status', url('/admin/attendance/requests'));
        }

        return $message;
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase(object $notifiable): array
    {
        $title = $this->action === 'applied' ? 'New Leave Request' : 'Leave Request '.ucfirst($this->action);
        $body = $this->action === 'applied'
            ? 'New leave request from '.$this->leaveRequest->user->name
            : 'Your leave request has been '.$this->action.'.';

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'request_id' => (string) $this->leaveRequest->id,
                'action' => 'leave_request',
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = $this->action === 'applied'
            ? 'New leave request from '.$this->leaveRequest->user->name
            : 'Your leave request was '.$this->action;

        return [
            'request_id' => $this->leaveRequest->id,
            'message' => $message,
        ];
    }
}
