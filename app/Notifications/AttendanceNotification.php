<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceNotification extends Notification
{
    use Queueable;

    public Attendance $attendance;

    public string $action; // 'submitted', 'approved', 'rejected', 'resubmitted'

    public ?string $reason;

    public function __construct(Attendance $attendance, string $action, ?string $reason = null)
    {
        $this->attendance = $attendance;
        $this->action = $action;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $dateStr = $this->attendance->attendance_date ? \Carbon\Carbon::parse($this->attendance->attendance_date)->format('d M Y') : 'today';

        $title = match ($this->action) {
            'approved' => "Attendance Approved ({$dateStr})",
            'rejected' => "Attendance Rejected ({$dateStr})",
            'submitted' => "Attendance Submitted ({$dateStr})",
            'resubmitted' => "Attendance Resubmitted ({$dateStr})",
            default => 'Attendance Update',
        };

        $message = match ($this->action) {
            'approved' => "Your attendance for {$dateStr} has been approved.",
            'rejected' => "Your attendance for {$dateStr} was rejected. Reason: ".($this->reason ?? $this->attendance->rejection_reason ?? 'No reason provided.'),
            'submitted' => 'Daily attendance submitted successfully.',
            'resubmitted' => "Your corrected attendance for {$dateStr} has been resubmitted.",
            default => "Attendance update for {$dateStr}",
        };

        return [
            'attendance_id' => $this->attendance->id,
            'action' => $this->action,
            'title' => $title,
            'message' => $message,
            'description' => $this->reason ?? $message,
            'date' => $this->attendance->attendance_date ? \Carbon\Carbon::parse($this->attendance->attendance_date)->format('Y-m-d') : null,
        ];
    }
}
