<?php

namespace App\Notifications\Channels;

use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toFirebase')) {
            \Illuminate\Support\Facades\Log::warning('FirebaseChannel: Notification '.get_class($notification).' is missing toFirebase method.');

            return;
        }

        $message = $notification->toFirebase($notifiable);

        if (! $message) {
            return;
        }

        $fcmTokens = $notifiable->fcm_token;

        if ($fcmTokens) {
            $tokens = json_decode($fcmTokens, true);
            if (! is_array($tokens)) {
                $tokens = [$fcmTokens];
            }

            foreach ($tokens as $token) {
                \Illuminate\Support\Facades\Log::info('FirebaseChannel: Sending notification to token '.substr($token, 0, 10).'...');
                try {
                    $this->firebaseService->sendNotification(
                        $token,
                        $message['title'],
                        $message['body'],
                        $message['data'] ?? []
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FirebaseChannel: Error sending to token '.substr($token, 0, 10).' - '.$e->getMessage());
                }
            }
        } else {
            \Illuminate\Support\Facades\Log::warning('FirebaseChannel: User has no FCM token.');
        }
    }
}
