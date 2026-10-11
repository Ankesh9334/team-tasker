<?php

namespace App\Contracts;

use App\Models\Meeting;

interface MeetingProviderInterface
{
    /**
     * Create/Configure room name or room credentials.
     */
    public function createRoom(Meeting $meeting): string;

    /**
     * Get join URL for the given meeting.
     */
    public function getJoinUrl(Meeting $meeting): string;

    /**
     * Start meeting.
     */
    public function startMeeting(Meeting $meeting): void;

    /**
     * End meeting.
     */
    public function endMeeting(Meeting $meeting): void;
}
