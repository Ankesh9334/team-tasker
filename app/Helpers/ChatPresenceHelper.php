<?php

namespace App\Helpers;

use Carbon\Carbon;

class ChatPresenceHelper
{
    /**
     * Format a last_seen_at timestamp into WhatsApp-style string.
     *
     * @param \Carbon\Carbon|string|null $lastSeenAt
     * @param bool $isOnline
     * @return string
     */
    public static function formatLastSeen($lastSeenAt, bool $isOnline = false): string
    {
        if ($isOnline) {
            return 'Online';
        }

        if (!$lastSeenAt) {
            return 'last seen recently';
        }

        $date = $lastSeenAt instanceof Carbon ? $lastSeenAt : Carbon::parse($lastSeenAt);
        $now = Carbon::now();

        // If seen within the last 2 minutes, consider active online
        if ($date->gt($now->copy()->subMinutes(2))) {
            return 'Online';
        }

        $timeStr = $date->format('g:i A');

        if ($date->isToday()) {
            return 'last seen today at ' . $timeStr;
        }

        if ($date->isYesterday()) {
            return 'last seen yesterday at ' . $timeStr;
        }

        if ($date->isCurrentYear()) {
            return 'last seen ' . $date->format('d M') . ' at ' . $timeStr;
        }

        return 'last seen ' . $date->format('d/m/Y') . ' at ' . $timeStr;
    }

    /**
     * Check if timestamp indicates recent activity (< 2 minutes).
     */
    public static function isRecentlyActive($lastSeenAt): bool
    {
        if (!$lastSeenAt) {
            return false;
        }
        $date = $lastSeenAt instanceof Carbon ? $lastSeenAt : Carbon::parse($lastSeenAt);
        return $date->gt(Carbon::now()->subMinutes(2));
    }
}
