<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Services\MeetingService;
use Illuminate\Contracts\Auth\Authenticatable;

class MeetingPolicy
{
    protected MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission('meetings.view') || $user->hasRole('super-admin');
    }

    public function view(Authenticatable $user, Meeting $meeting): bool
    {
        if (! $user->hasPermission('meetings.view') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return $this->meetingService->canUserJoin($meeting, $user);
    }

    public function join(Authenticatable $user, Meeting $meeting): bool
    {
        if (! $user->hasPermission('meetings.join') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return $this->meetingService->canUserJoin($meeting, $user);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission('meetings.create') || $user->hasRole('super-admin');
    }

    public function update(Authenticatable $user, Meeting $meeting): bool
    {
        return $meeting->created_by == $user->id || $user->hasPermission('meetings.create') || $user->hasRole('super-admin');
    }

    public function cancel(Authenticatable $user, Meeting $meeting): bool
    {
        return $meeting->created_by == $user->id || $user->hasPermission('meetings.cancel') || $user->hasRole('super-admin');
    }

    public function end(Authenticatable $user, Meeting $meeting): bool
    {
        return $this->meetingService->canUserJoin($meeting, $user);
    }

    public function delete(Authenticatable $user, Meeting $meeting): bool
    {
        return $meeting->created_by == $user->id || $user->hasPermission('meetings.delete') || $user->hasRole('super-admin');
    }
}
