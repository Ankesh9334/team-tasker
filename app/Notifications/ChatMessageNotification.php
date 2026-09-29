<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
        // Ensure user and client relationships are loaded for the sender's name
        if (! $this->message->relationLoaded('user')) {
            $this->message->load('user');
        }
        if (! $this->message->relationLoaded('client')) {
            $this->message->load('client');
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // 1. Refresh the message to check latest is_read/delivered status
        $this->message->refresh();

        // 2. Check if the user has already read the message via real-time socket
        $isClient = $notifiable instanceof \App\Models\Client;
        $query = \Illuminate\Support\Facades\DB::table('conversation_participants')
            ->where('conversation_id', $this->message->conversation_id);

        if ($isClient) {
            $query->where('client_id', $notifiable->id);
        } else {
            $query->where('user_id', $notifiable->id);
        }
        $participant = $query->first();

        if ($participant && $participant->last_read_at) {
            $lastRead = \Carbon\Carbon::parse($participant->last_read_at);
            $msgCreated = $this->message->created_at;

            \Illuminate\Support\Facades\Log::info('ChatMessageNotification: Notifiable '.$notifiable->id.' last_read_at: '.$lastRead.', Message created_at: '.$msgCreated);

            if ($lastRead->addSeconds(5)->gte($msgCreated)) {
                \Illuminate\Support\Facades\Log::info('ChatMessageNotification: Suppressing notification because user recently viewed the chat (within 5s buffer).');

                return [];
            }
        }

        \Illuminate\Support\Facades\Log::info('ChatMessageNotification: Dispatching through database and FirebaseChannel.');

        return ['database', Channels\FirebaseChannel::class];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase(object $notifiable): array
    {
        $senderName = $this->message->user->name ?? $this->message->client->name ?? 'User';
        $body = strip_tags($this->message->body);

        if (empty($body) && $this->message->attachments()->count() > 0) {
            $body = 'sent an attachment';
        }

        return [
            'title' => 'New Message from '.$senderName,
            'body' => $body,
            'data' => [
                'conversation_id' => (string) $this->message->conversation_id,
                'action' => 'new_message',
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
        $senderName = $this->message->user->name ?? $this->message->client->name ?? 'User';
        $body = strip_tags($this->message->body);
        if (empty($body) && $this->message->attachments()->count() > 0) {
            $body = 'Sent an attachment';
        }

        return [
            'conversation_id' => $this->message->conversation_id,
            'message' => 'New message from '.$senderName,
            'title' => $senderName,
            'description' => \Illuminate\Support\Str::limit($body, 60),
            'sender_id' => $this->message->user_id ?? $this->message->client_id,
            'sender_name' => $senderName,
        ];
    }
}
