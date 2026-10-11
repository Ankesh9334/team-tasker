<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', Channels\FirebaseChannel::class];

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
            ->subject('Task Assigned: '.$this->task->title)
            ->line('You have been assigned a new task.')
            ->line('Title: '.$this->task->title)
            ->line('Priority: '.$this->task->priority)
            ->action('View Task', route('details', $this->task->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase(object $notifiable): array
    {
        return [
            'title' => 'Task Assigned',
            'body' => 'You have been assigned a new task: '.$this->task->title,
            'data' => [
                'task_id' => (string) $this->task->id,
                'action' => 'task_assigned',
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
        return [
            'task_id' => $this->task->id,
            'message' => 'New task assigned: '.$this->task->title,
        ];
    }
}
