<?php

namespace App\Services\MeetingProviders;

use App\Contracts\MeetingProviderInterface;
use App\Models\Meeting;
use Illuminate\Support\Str;

class LiveKitMeetingProvider implements MeetingProviderInterface
{
    public function createRoom(Meeting $meeting): string
    {
        $prefix = config('app.name', 'TeamTasker');
        $cleanPrefix = Str::slug($prefix);

        return $cleanPrefix.'-'.$meeting->type.'-'.$meeting->uuid;
    }

    public function getJoinUrl(Meeting $meeting): string
    {
        return route('admin.meetings.join', $meeting->uuid);
    }

    public function startMeeting(Meeting $meeting): void
    {
        $meeting->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function endMeeting(Meeting $meeting): void
    {
        $startedAt = $meeting->started_at ?? $meeting->created_at;
        $durationSeconds = $startedAt ? now()->diffInSeconds($startedAt) : 0;
        $durationMinutes = max(1, (int) ceil($durationSeconds / 60));

        $meeting->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration' => $durationMinutes,
        ]);
    }

    /**
     * Generate LiveKit JWT Access Token for client joining the room.
     */
    public function generateToken(Meeting $meeting, $user): string
    {
        $apiKey = config('livekit.api_key', 'devkey');
        $apiSecret = config('livekit.api_secret', 'secret');
        $roomName = $meeting->room_name;
        $identity = (string) $user->id;
        $name = $user->name ?? 'User';

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $ttl = 3600 * 6; // 6 hours expiration

        $isHost = ($meeting->created_by == $user->id) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin'));
        $canPublish = $isHost || (method_exists($user, 'hasPermission') ? $user->hasPermission('meetings.create') || $user->hasPermission('meetings.join') : true);
        $canSubscribe = true;
        $canPublishData = true;

        $payload = [
            'iss' => $apiKey,
            'sub' => $identity,
            'name' => $name,
            'nbf' => $now - 5,
            'exp' => $now + $ttl,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => $canPublish,
                'canSubscribe' => $canSubscribe,
                'canPublishData' => $canPublishData,
                'roomAdmin' => $isHost,
            ],
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader.'.'.$base64UrlPayload, $apiSecret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader.'.'.$base64UrlPayload.'.'.$base64UrlSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
