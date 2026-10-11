<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
    }

    public function getAccessToken()
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            storage_path('app/firebase/firebase-service-account.json')
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token'];
    }

    public function sendNotification($deviceToken, $title, $body, $extraData = [])
    {
        $accessToken = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if (! empty($extraData)) {
            $message['data'] = $extraData;
        }

        return Http::withToken($accessToken)
            ->post($url, [
                'message' => $message,
            ])
            ->json();
    }
}
