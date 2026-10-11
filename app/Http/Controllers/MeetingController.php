<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitiateCallRequest;
use App\Http\Requests\StoreMeetingRequest;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    protected MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Display list of meetings.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'all');

        $query = Meeting::with(['createdBy', 'project', 'task', 'participants.user']);

        // Non-super-admin users only see meetings they created, participate in, or are associated with via project
        $isSuperAdmin = $user->role && $user->role->slug === 'super-admin';
        if (! $isSuperAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('participants', function ($pq) use ($user) {
                        $pq->where('user_id', $user->id);
                    })
                    ->orWhereHas('project', function ($prq) use ($user) {
                        $prq->where('user_id', $user->id)
                            ->orWhereHas('users', function ($u) use ($user) {
                                $u->where('users.id', $user->id);
                            });
                    });
            });
        }

        if ($tab === 'upcoming') {
            $query->whereIn('status', [Meeting::STATUS_SCHEDULED, Meeting::STATUS_RINGING, Meeting::STATUS_ACTIVE])
                ->orderBy('scheduled_at', 'asc');
        } elseif ($tab === 'history') {
            $query->whereIn('status', [Meeting::STATUS_COMPLETED, Meeting::STATUS_CANCELLED, Meeting::STATUS_REJECTED, Meeting::STATUS_MISSED]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->input('mode'));
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->input('created_at'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $perPage = (int) $request->get('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $meetings = $query->latest()->paginate($perPage)->withQueryString();
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('admin.meetings.index', compact('meetings', 'tab', 'perPage', 'users'));
    }

    /**
     * Show form to create/schedule a meeting.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        $projects = Project::where('user_id', $user->id)
            ->orWhereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->get();

        $tasks = Task::where('user_id', $user->id)
            ->orWhere('assigned_to', $user->id)
            ->get();

        $users = User::where('id', '!=', $user->id)->get();

        $selectedProjectId = $request->get('project_id');
        $selectedTaskId = $request->get('task_id');

        return view('admin.meetings.create', compact('projects', 'tasks', 'users', 'selectedProjectId', 'selectedTaskId'));
    }

    /**
     * Store new meeting.
     */
    public function store(StoreMeetingRequest $request)
    {
        $meeting = $this->meetingService->createMeeting($request->validated(), Auth::user());

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'meeting' => $meeting,
                'join_url' => route('admin.meetings.join', $meeting->uuid),
            ]);
        }

        return redirect()->route('admin.meetings.show', $meeting->uuid)
            ->with('success', 'Meeting created successfully.');
    }

    /**
     * Show meeting details.
     */
    public function show(Meeting $meeting)
    {
        $this->authorize('view', $meeting);

        $meeting->load(['createdBy', 'project', 'task', 'participants.user']);

        return view('admin.meetings.show', compact('meeting'));
    }

    /**
     * Dedicated LiveKit meeting join view.
     */
    public function join(Meeting $meeting)
    {
        $this->authorize('join', $meeting);

        $user = Auth::user();
        $this->meetingService->joinMeeting($meeting, $user);

        $livekitProvider = new \App\Services\MeetingProviders\LiveKitMeetingProvider;
        $livekitUrl = config('livekit.url', 'wss://demo.livekit.cloud');
        $livekitToken = $livekitProvider->generateToken($meeting, $user);

        return view('admin.meetings.join', compact('meeting', 'livekitUrl', 'livekitToken', 'user'));
    }

    /**
     * Accept meeting/call.
     */
    public function accept(Meeting $meeting)
    {
        $success = $this->meetingService->acceptCall($meeting, Auth::user());

        if (! $success) {
            return response()->json(['success' => false, 'message' => 'Unable to accept meeting.'], 400);
        }

        return response()->json([
            'success' => true,
            'join_url' => route('admin.meetings.join', $meeting->uuid),
        ]);
    }

    /**
     * Reject meeting/call.
     */
    public function reject(Meeting $meeting)
    {
        $success = $this->meetingService->rejectCall($meeting, Auth::user());

        return response()->json(['success' => $success]);
    }

    /**
     * Cancel meeting/call.
     */
    public function cancel(Meeting $meeting)
    {
        $this->authorize('cancel', $meeting);

        $success = $this->meetingService->cancelMeeting($meeting, Auth::user());

        if (request()->wantsJson()) {
            return response()->json(['success' => $success]);
        }

        return redirect()->back()->with('success', 'Meeting cancelled successfully.');
    }

    /**
     * End active meeting/call.
     */
    public function end(Meeting $meeting)
    {
        $this->authorize('end', $meeting);

        $success = $this->meetingService->endMeeting($meeting, Auth::user());

        if (request()->wantsJson()) {
            return response()->json(['success' => $success]);
        }

        return redirect()->route('admin.meetings.index')->with('success', 'Meeting ended.');
    }

    /**
     * Leave meeting (Jitsi event listener).
     */
    public function leave(Meeting $meeting)
    {
        $success = $this->meetingService->leaveMeeting($meeting, Auth::user());

        return response()->json(['success' => $success]);
    }

    /**
     * Initiate 1-on-1 audio call.
     */
    public function initiateAudioCall(InitiateCallRequest $request)
    {
        $receiver = User::findOrFail($request->receiver_id);
        $meeting = $this->meetingService->initiateCall(Auth::user(), $receiver, Meeting::MODE_AUDIO);

        return response()->json([
            'success' => true,
            'meeting' => $meeting,
            'join_url' => route('admin.meetings.join', $meeting->uuid),
        ]);
    }

    /**
     * Initiate 1-on-1 video call.
     */
    public function initiateVideoCall(InitiateCallRequest $request)
    {
        $receiver = User::findOrFail($request->receiver_id);
        $meeting = $this->meetingService->initiateCall(Auth::user(), $receiver, Meeting::MODE_VIDEO);

        return response()->json([
            'success' => true,
            'meeting' => $meeting,
            'join_url' => route('admin.meetings.join', $meeting->uuid),
        ]);
    }

    /**
     * API to check timeout status for a ringing call.
     */
    public function checkTimeout(Meeting $meeting)
    {
        $timedOut = $this->meetingService->checkCallTimeout($meeting);

        return response()->json([
            'timed_out' => $timedOut,
            'status' => $meeting->fresh()->status,
        ]);
    }
}
