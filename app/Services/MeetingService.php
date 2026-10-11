<?php

namespace App\Services;

use App\Contracts\MeetingProviderInterface;
use App\Models\Conversation;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\MeetingProviders\LiveKitMeetingProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MeetingService
{
    protected MeetingProviderInterface $provider;

    public function __construct(?MeetingProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new LiveKitMeetingProvider;
    }

    /**
     * Initiate a 1-on-1 audio or video call.
     */
    public function initiateCall(Authenticatable $caller, User $receiver, string $mode = 'video'): Meeting
    {
        // Check for active/ringing calls between caller and receiver to prevent duplicates
        $existingCall = Meeting::whereIn('type', [Meeting::TYPE_DIRECT_CALL, Meeting::TYPE_GROUP_CALL])
            ->whereIn('status', [Meeting::STATUS_RINGING, Meeting::STATUS_ACTIVE])
            ->whereHas('participants', function ($q) use ($caller) {
                $q->where('user_id', $caller->id);
            })
            ->whereHas('participants', function ($q) use ($receiver) {
                $q->where('user_id', $receiver->id);
            })
            ->first();

        if ($existingCall) {
            return $existingCall;
        }

        return DB::transaction(function () use ($caller, $receiver, $mode) {
            $meeting = new Meeting([
                'title' => ucfirst($mode).' Call with '.$receiver->name,
                'type' => Meeting::TYPE_DIRECT_CALL,
                'mode' => $mode,
                'provider' => 'livekit',
                'created_by' => $caller->id,
                'status' => Meeting::STATUS_RINGING,
            ]);

            $meeting->uuid = (string) Str::uuid();
            $meeting->room_name = $this->provider->createRoom($meeting);
            $meeting->save();

            // Add caller as host
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $caller->id,
                'role' => MeetingParticipant::ROLE_HOST,
                'status' => MeetingParticipant::STATUS_ACCEPTED,
                'invited_at' => now(),
            ]);

            // Add receiver as participant
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $receiver->id,
                'role' => MeetingParticipant::ROLE_PARTICIPANT,
                'status' => MeetingParticipant::STATUS_RINGING,
                'invited_at' => now(),
            ]);

            return $meeting->load(['createdBy', 'participants.user']);
        });
    }

    /**
     * Create a scheduled or instant meeting (project/task/standalone).
     */
    public function createMeeting(array $data, Authenticatable $creator): Meeting
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = $data['type'] ?? Meeting::TYPE_SCHEDULED_MEETING;
            $status = ! empty($data['scheduled_at']) ? Meeting::STATUS_SCHEDULED : Meeting::STATUS_ACTIVE;

            $meeting = new Meeting([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $type,
                'mode' => $data['mode'] ?? Meeting::MODE_VIDEO,
                'provider' => $data['provider'] ?? 'livekit',
                'created_by' => $creator->id,
                'project_id' => $data['project_id'] ?? null,
                'task_id' => $data['task_id'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'duration' => $data['duration'] ?? null,
                'status' => $status,
                'started_at' => $status === Meeting::STATUS_ACTIVE ? now() : null,
            ]);

            $meeting->uuid = (string) Str::uuid();
            $meeting->room_name = $this->provider->createRoom($meeting);
            $meeting->save();

            // Creator as Host
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $creator->id,
                'role' => MeetingParticipant::ROLE_HOST,
                'status' => MeetingParticipant::STATUS_ACCEPTED,
                'invited_at' => now(),
            ]);

            // Add extra participants if specified
            if (! empty($data['participant_ids']) && is_array($data['participant_ids'])) {
                foreach ($data['participant_ids'] as $pId) {
                    if ($pId != $creator->id) {
                        MeetingParticipant::create([
                            'meeting_id' => $meeting->id,
                            'user_id' => $pId,
                            'role' => MeetingParticipant::ROLE_PARTICIPANT,
                            'status' => MeetingParticipant::STATUS_INVITED,
                            'invited_at' => now(),
                        ]);
                    }
                }
            }

            return $meeting->load(['createdBy', 'project', 'task', 'participants.user']);
        });
    }

    /**
     * Accept an incoming call.
     */
    public function acceptCall(Meeting $meeting, Authenticatable $user): bool
    {
        if (! in_array($meeting->status, [Meeting::STATUS_RINGING, Meeting::STATUS_SCHEDULED, Meeting::STATUS_ACTIVE])) {
            return false;
        }

        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        DB::transaction(function () use ($meeting, &$participant, $user) {
            if (! $participant) {
                $participant = MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'user_id' => $user->id,
                    'role' => MeetingParticipant::ROLE_PARTICIPANT,
                    'status' => MeetingParticipant::STATUS_ACCEPTED,
                    'invited_at' => now(),
                ]);
            } else {
                $participant->update([
                    'status' => MeetingParticipant::STATUS_ACCEPTED,
                ]);
            }

            if ($meeting->status === Meeting::STATUS_RINGING) {
                $meeting->update([
                    'status' => Meeting::STATUS_ACTIVE,
                    'started_at' => now(),
                ]);
                $this->provider->startMeeting($meeting);
            }
        });

        return true;
    }

    /**
     * Reject an incoming call.
     */
    public function rejectCall(Meeting $meeting, Authenticatable $user): bool
    {
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        DB::transaction(function () use ($meeting, $participant) {
            $participant->update([
                'status' => MeetingParticipant::STATUS_REJECTED,
            ]);

            // If it's a 1-on-1 direct call and receiver rejects, mark meeting as rejected
            if ($meeting->type === Meeting::TYPE_DIRECT_CALL) {
                $meeting->update([
                    'status' => Meeting::STATUS_REJECTED,
                    'ended_at' => now(),
                ]);
                $this->postSystemCallMessage($meeting, 'rejected');
            }
        });

        return true;
    }

    /**
     * Cancel call or scheduled meeting.
     */
    public function cancelMeeting(Meeting $meeting, Authenticatable $user): bool
    {
        if ($meeting->created_by != $user->id && ! $user->hasPermission('projects.edit')) {
            return false;
        }

        DB::transaction(function () use ($meeting) {
            $meeting->update([
                'status' => Meeting::STATUS_CANCELLED,
                'ended_at' => now(),
            ]);

            MeetingParticipant::where('meeting_id', $meeting->id)
                ->whereIn('status', [MeetingParticipant::STATUS_RINGING, MeetingParticipant::STATUS_INVITED])
                ->update(['status' => MeetingParticipant::STATUS_REJECTED]);

            if ($meeting->type === Meeting::TYPE_DIRECT_CALL) {
                $this->postSystemCallMessage($meeting, 'cancelled');
            }
        });

        return true;
    }

    /**
     * End active meeting/call.
     */
    public function endMeeting(Meeting $meeting, Authenticatable $user): bool
    {
        if (! in_array($meeting->status, [Meeting::STATUS_RINGING, Meeting::STATUS_ACTIVE])) {
            return false;
        }

        DB::transaction(function () use ($meeting) {
            $meeting->update([
                'status' => Meeting::STATUS_COMPLETED,
                'ended_at' => now(),
            ]);

            $this->provider->endMeeting($meeting);

            // Mark left timestamp for active participants
            MeetingParticipant::where('meeting_id', $meeting->id)
                ->where('status', MeetingParticipant::STATUS_JOINED)
                ->update([
                    'status' => MeetingParticipant::STATUS_LEFT,
                    'left_at' => now(),
                ]);

            if (in_array($meeting->type, [Meeting::TYPE_DIRECT_CALL, Meeting::TYPE_GROUP_CALL])) {
                $this->postSystemCallMessage($meeting, 'completed');
            }
        });

        return true;
    }

    /**
     * Mark participant joined Jitsi room.
     */
    public function joinMeeting(Meeting $meeting, Authenticatable $user): bool
    {
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        // If user is allowed to join project/task meeting, auto-create participant record if missing
        if (! $participant && $this->canUserJoin($meeting, $user)) {
            $participant = MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => MeetingParticipant::ROLE_PARTICIPANT,
                'status' => MeetingParticipant::STATUS_JOINED,
                'invited_at' => now(),
                'joined_at' => now(),
            ]);
        } elseif ($participant) {
            $participant->update([
                'status' => MeetingParticipant::STATUS_JOINED,
                'joined_at' => now(),
            ]);
        } else {
            return false;
        }

        if ($meeting->status !== Meeting::STATUS_ACTIVE) {
            $meeting->update([
                'status' => Meeting::STATUS_ACTIVE,
                'started_at' => $meeting->started_at ?? now(),
            ]);
        }

        return true;
    }

    /**
     * Mark participant left Jitsi room.
     */
    public function leaveMeeting(Meeting $meeting, Authenticatable $user): bool
    {
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => MeetingParticipant::STATUS_LEFT,
                'left_at' => now(),
            ]);
        }

        // Check if all participants left
        $joinedCount = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('status', MeetingParticipant::STATUS_JOINED)
            ->count();

        if ($joinedCount === 0 && $meeting->status === Meeting::STATUS_ACTIVE) {
            $this->endMeeting($meeting, $user);
        }

        return true;
    }

    /**
     * Check call timeout (missed call).
     */
    public function checkCallTimeout(Meeting $meeting): bool
    {
        if ($meeting->status === Meeting::STATUS_RINGING) {
            $timeoutSeconds = config('jitsi.call_ring_timeout', 30);
            if ($meeting->created_at->diffInSeconds(now()) >= $timeoutSeconds) {
                DB::transaction(function () use ($meeting) {
                    $meeting->update([
                        'status' => Meeting::STATUS_MISSED,
                        'ended_at' => now(),
                    ]);

                    MeetingParticipant::where('meeting_id', $meeting->id)
                        ->where('status', MeetingParticipant::STATUS_RINGING)
                        ->update(['status' => MeetingParticipant::STATUS_REJECTED]);

                    if ($meeting->type === Meeting::TYPE_DIRECT_CALL) {
                        $this->postSystemCallMessage($meeting, 'missed');
                    }
                });

                return true;
            }
        }

        return false;
    }

    /**
     * Validate if a user is permitted to view/join a meeting.
     */
    public function canUserJoin(Meeting $meeting, Authenticatable $user): bool
    {
        // 1. Super admin bypass
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Creator
        if ($meeting->created_by == $user->id) {
            return true;
        }

        // 3. Explicitly invited participant
        $isParticipant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isParticipant) {
            return true;
        }

        // 4. Project meeting check
        if ($meeting->project_id) {
            $project = $meeting->project;
            if ($project && ($project->user_id == $user->id || $project->users()->where('users.id', $user->id)->exists())) {
                return true;
            }
        }

        // 5. Task meeting check
        if ($meeting->task_id) {
            $task = $meeting->task;
            if ($task && ($task->user_id == $user->id || $task->assigned_to == $user->id || $task->users()->where('users.id', $user->id)->exists())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Post a clean system message into the chat conversation when a call ends/is missed/etc.
     */
    protected function postSystemCallMessage(Meeting $meeting, string $eventState): void
    {
        try {
            $participants = $meeting->participants->pluck('user_id')->toArray();
            if (count($participants) < 2) {
                return;
            }

            // Find private conversation between caller and receiver
            $conversation = Conversation::where('type', 'private')
                ->whereHas('participants', function ($q) use ($participants) {
                    $q->where('user_id', $participants[0]);
                })
                ->whereHas('participants', function ($q) use ($participants) {
                    $q->where('user_id', $participants[1]);
                })
                ->first();

            if (! $conversation) {
                return;
            }

            $icon = $meeting->mode === Meeting::MODE_AUDIO ? '📞' : '📹';
            $modeLabel = ucfirst($meeting->mode).' Call';

            if ($eventState === 'completed') {
                $startedAt = $meeting->started_at;
                $endedAt = $meeting->ended_at ?? now();

                if ($startedAt) {
                    $seconds = max(1, $endedAt->diffInSeconds($startedAt));
                } else {
                    $seconds = max(1, $endedAt->diffInSeconds($meeting->created_at));
                }

                if ($seconds < 60) {
                    $durationStr = " · {$seconds} sec";
                } else {
                    $mins = floor($seconds / 60);
                    $secs = $seconds % 60;
                    $durationStr = $secs > 0 ? " · {$mins}m {$secs}s" : " · {$mins} min";
                }
                $body = "{$icon} {$modeLabel}{$durationStr}";
            } elseif ($eventState === 'missed') {
                $body = "{$icon} Missed {$modeLabel}";
            } elseif ($eventState === 'rejected') {
                $body = "{$icon} {$modeLabel} Declined";
            } elseif ($eventState === 'cancelled') {
                $body = "{$icon} {$modeLabel} Cancelled";
            } else {
                $body = "{$icon} {$modeLabel}";
            }

            $conversation->messages()->create([
                'user_id' => $meeting->created_by,
                'body' => $body,
            ]);
        } catch (\Throwable $e) {
            // Fail safe, do not crash call state updates if chat message fails
        }
    }
}
