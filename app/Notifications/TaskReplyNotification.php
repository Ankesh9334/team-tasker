<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;

    public $log;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, TaskLog $log)
    {
        $this->task = $task;
        $this->log = $log;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $host = config('mail.mailers.smtp.host');
        if (! empty($host) && $host !== 'mailpit' && $host !== '127.0.0.1' && $host !== 'localhost') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Task #'.$this->task->id.'] New Update: '.$this->task->title)
            ->line('A new update has been posted to the task.')
            ->line('Updated By: '.($this->log->user ? $this->log->user->name : 'System'))
            ->line('---')
            ->line(strip_tags($this->log->note))
            ->line('---')
            ->action('View Task', route('details', $this->task->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'message' => 'New update on task #'.$this->task->id,
            'log_id' => $this->log->id,
        ];
    }
}
