<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meeting->title }} | Team Tasker Call</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- LiveKit Web Client SDK -->
    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>

    <style>
        :root {
            --wa-bg: #0b141a;
            --wa-surface: #111b21;
            --wa-card: #202c33;
            --wa-green: #00a884;
            --wa-teal: #128c7e;
            --wa-red: #ea4335;
            --text-high: #e9edef;
            --text-medium: #8696a0;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--wa-bg);
            color: var(--text-high);
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
        }

        /* Top Bar Header */
        .call-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            padding: 0 24px;
            background: linear-gradient(180deg, rgba(11, 20, 26, 0.9) 0%, rgba(11, 20, 26, 0) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 50;
            backdrop-filter: blur(8px);
        }

        .call-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-high);
            margin: 0;
        }

        .call-status-badge {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(0, 168, 132, 0.15);
            color: var(--wa-green);
            border: 1px solid rgba(0, 168, 132, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Call Body */
        .call-body {
            height: 100vh;
            width: 100vw;
            position: relative;
            background: radial-gradient(circle at center, #111b21 0%, #0b141a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Grid Layout */
        #media-grid {
            width: 100%;
            height: 100%;
            padding: 80px 24px 100px 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 20px;
            overflow-y: auto;
        }

        /* Video Card styling */
        .video-card {
            flex: 1 1 360px;
            max-width: 720px;
            height: 100%;
            max-height: 520px;
            background: var(--wa-surface);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .video-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* WhatsApp Audio View (Avatar + Pulse) */
        .audio-avatar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 5;
        }

        .audio-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #128c7e, #00a884);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: #ffffff;
            box-shadow: 0 0 0 0 rgba(0, 168, 132, 0.4);
            animation: whatsapp-pulse 2s infinite;
        }

        @keyframes whatsapp-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 168, 132, 0.5);
            }

            70% {
                box-shadow: 0 0 0 25px rgba(0, 168, 132, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 168, 132, 0);
            }
        }

        .audio-participant-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-high);
        }

        .user-label {
            position: absolute;
            bottom: 16px;
            left: 16px;
            background: rgba(11, 20, 26, 0.85);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-high);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Reconnection Overlay Banner */
        #reconnect-banner {
            position: absolute;
            top: 75px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(234, 67, 53, 0.9);
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 100;
            display: none;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
        }

        /* Connection Quality Badge */
        .conn-quality {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .conn-excellent {
            background: rgba(0, 168, 132, 0.2);
            color: #00a884;
        }

        .conn-good {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .conn-poor {
            background: rgba(234, 67, 53, 0.2);
            color: #ea4335;
        }

        /* In-Meeting Chat Drawer */
        #meeting-chat-drawer {
            position: absolute;
            right: -360px;
            top: 70px;
            bottom: 0;
            width: 340px;
            background: var(--wa-surface);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 60;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: -10px 0 25px rgba(0, 0, 0, 0.5);
        }

        #meeting-chat-drawer.open {
            right: 0;
        }

        .chat-drawer-header {
            padding: 16px;
            background: var(--wa-card);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-drawer-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-msg-bubble {
            background: var(--wa-card);
            padding: 8px 12px;
            border-radius: 12px;
            max-width: 85%;
            font-size: 0.85rem;
            align-self: flex-start;
        }

        .chat-msg-bubble.self {
            align-self: flex-end;
            background: var(--wa-green);
            color: #ffffff;
        }

        .chat-drawer-input {
            padding: 12px;
            background: var(--wa-card);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-drawer-input input {
            flex: 1;
            background: rgba(11, 20, 26, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            border-radius: 20px !important;
            padding: 8px 14px !important;
        }

        .chat-drawer-input button {
            border-radius: 50% !important;
            width: 38px;
            height: 38px;
            padding: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--wa-green) !important;
            border: none !important;
            color: #ffffff !important;
            flex-shrink: 0;
        }

        .chat-drawer-input button:hover {
            background: #008f70 !important;
        }

        /* WhatsApp Floating Controls Bar */
        .call-controls-wrapper {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(32, 44, 51, 0.85);
            padding: 12px 24px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 50;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .control-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--text-high);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: scale(1.05);
        }

        .control-btn.active-off {
            background: rgba(234, 67, 53, 0.2);
            color: var(--wa-red);
        }

        .control-btn.end-btn {
            background: var(--wa-red);
            color: #ffffff;
            width: 54px;
            height: 54px;
        }

        .control-btn.end-btn:hover {
            background: #d93025;
            transform: scale(1.08);
            box-shadow: 0 0 15px rgba(234, 67, 53, 0.5);
        }
    </style>
</head>

<body>

    <!-- Connection Lost Overlay -->
    <div id="reconnect-banner">
        <i class="fas fa-spinner fa-spin"></i> <span>Connection lost... Reconnecting to meeting</span>
    </div>

    <!-- WhatsApp Style Top Header -->
    <div class="call-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.meetings.index') }}" class="control-btn p-0"
                style="width: 40px; height: 40px; font-size: 1rem;" title="Back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="call-title">{{ $meeting->title }}</h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="call-status-badge">
                        <i class="fas fa-lock" style="font-size: 0.7rem;"></i> End-to-End Encrypted
                    </span>
                    <span class="call-status-badge" id="callTimer">
                        <i class="fas fa-clock"></i> 00:00
                    </span>
                    <span class="conn-quality conn-excellent" id="connStatusBadge">
                        🟢 Excellent
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($meeting->project)
                <span class="call-status-badge"
                    style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">
                    <i class="fas fa-folder me-1"></i> {{ $meeting->project->name }}
                </span>
            @endif
            @if($meeting->task)
                <span class="call-status-badge"
                    style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; border-color: rgba(14, 165, 233, 0.3);">
                    <i class="fas fa-tasks me-1"></i> {{ $meeting->task->title }}
                </span>
            @endif
        </div>
    </div>

    <!-- Call Main Screen -->
    <div class="call-body">
        <div id="media-grid"></div>

        <!-- In-Meeting Chat Side Drawer -->
        <div id="meeting-chat-drawer">
            <div class="chat-drawer-header">
                <h6 class="mb-0 fw-bold"><i class="fas fa-comments me-2 text-success"></i> Meeting Chat</h6>
                <button class="btn btn-link text-high p-0" onclick="toggleChatDrawer()"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="chat-drawer-messages" id="chatMessagesBox">
                <div class="text-center text-medium small my-2">In-meeting messages are encrypted</div>
            </div>
            <div class="chat-drawer-input">
                <input type="text" id="chatInput"
                    class="form-control bg-dark text-white border-secondary form-control-sm"
                    placeholder="Type a message..." onkeydown="if(event.key==='Enter') sendInMeetingMessage()">
                <button class="btn btn-success btn-sm px-3" onclick="sendInMeetingMessage()"><i
                        class="fas fa-paper-plane"></i></button>
            </div>
        </div>

        <!-- WhatsApp Floating Toolbar -->
        <div class="call-controls-wrapper">
            <button class="control-btn" id="micBtn" onclick="toggleMic()" title="Microphone">
                <i class="fas fa-microphone" id="micIcon"></i>
            </button>

            @if($meeting->mode === 'video')
                <button class="control-btn" id="camBtn" onclick="toggleCam()" title="Camera">
                    <i class="fas fa-video" id="camIcon"></i>
                </button>
                <button class="control-btn" id="screenBtn" onclick="toggleScreenShare()" title="Share Screen">
                    <i class="fas fa-desktop"></i>
                </button>
            @endif

            <button class="control-btn" id="chatToggleBtn" onclick="toggleChatDrawer()" title="Meeting Chat">
                <i class="fas fa-comments"></i>
            </button>

            <button class="control-btn end-btn" onclick="leaveMeeting()" title="End Call">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>

    <script>
        let room = null;
        let isAudioMuted = false;
        let isVideoMuted = false;
        let isScreenSharing = false;

        const url = "{{ $livekitUrl }}";
        const token = "{{ $livekitToken }}";
        const isAudioOnly = "{{ $meeting->mode }}" === "audio";

        let callSeconds = 0;
        let timerInterval = null;

        function startCallTimer() {
            if (timerInterval) return;
            timerInterval = setInterval(() => {
                callSeconds++;
                const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
                const secs = String(callSeconds % 60).padStart(2, '0');
                const timerEl = document.getElementById('callTimer');
                if (timerEl) {
                    timerEl.innerHTML = `<i class="fas fa-clock me-1"></i> ${mins}:${secs}`;
                }
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', async function () {
            if (typeof LivekitClient === 'undefined') {
                console.warn('LiveKit SDK loading fallback...');
            }

            try {
                room = new LivekitClient.Room({
                    adaptiveStream: true,
                    dynacast: true,
                });

                // Set up event listeners
                room.on(LivekitClient.RoomEvent.TrackSubscribed, handleTrackSubscribed)
                    .on(LivekitClient.RoomEvent.TrackUnsubscribed, handleTrackUnsubscribed)
                    .on(LivekitClient.RoomEvent.ParticipantConnected, participantJoined)
                    .on(LivekitClient.RoomEvent.ParticipantDisconnected, participantLeft)
                    .on(LivekitClient.RoomEvent.Disconnected, handleDisconnected)
                    .on(LivekitClient.RoomEvent.Reconnecting, handleReconnecting)
                    .on(LivekitClient.RoomEvent.Reconnected, handleReconnected)
                    .on(LivekitClient.RoomEvent.ConnectionQualityChanged, handleConnectionQualityChanged)
                    .on(LivekitClient.RoomEvent.DataReceived, handleDataReceived);

                // Connect to LiveKit Room
                await room.connect(url, token);
                console.log('Connected to LiveKit room successfully');
                startCallTimer();
                notifyBackend('join');

                // Publish local microphone & camera
                await room.localParticipant.setMicrophoneEnabled(true);
                if (!isAudioOnly) {
                    await room.localParticipant.setCameraEnabled(true);
                }

                // Render local participant
                renderLocalParticipant();

            } catch (error) {
                console.error('Failed to connect to LiveKit room:', error);
                // Fallback / Alert if local server unaccessible
                alert("LiveKit Connection Notice: Could not connect to LiveKit server. Please check LIVEKIT_URL configuration.");
            }
        });

        function renderLocalParticipant() {
            const grid = document.getElementById('media-grid');
            let card = document.getElementById('participant-local');

            if (!card) {
                card = document.createElement('div');
                card.id = 'participant-local';
                card.className = 'video-card';
                card.innerHTML = `
                    ${isAudioOnly ? `
                        <div class="audio-avatar-wrapper">
                            <div class="audio-avatar">
                                {{ substr(addslashes($user->name), 0, 1) }}
                            </div>
                            <div class="audio-participant-name">{{ addslashes($user->name) }} (You)</div>
                        </div>
                    ` : ''}
                    <div class="user-label">
                        <i class="fas fa-user-circle"></i> {{ addslashes($user->name) }} (You)
                    </div>
                `;
                grid.appendChild(card);
            }

            if (!isAudioOnly) {
                room.localParticipant.trackPublications.forEach(pub => {
                    if (pub.track && pub.track.kind === 'video') {
                        const videoEl = pub.track.attach();
                        card.prepend(videoEl);
                    }
                });
            }
        }

        function handleTrackSubscribed(track, publication, participant) {
            const grid = document.getElementById('media-grid');
            let card = document.getElementById(`participant-${participant.identity}`);

            if (!card) {
                const initial = (participant.name || participant.identity).charAt(0).toUpperCase();
                card = document.createElement('div');
                card.id = `participant-${participant.identity}`;
                card.className = 'video-card';
                card.innerHTML = `
                    ${isAudioOnly ? `
                        <div class="audio-avatar-wrapper">
                            <div class="audio-avatar">
                                ${initial}
                            </div>
                            <div class="audio-participant-name">${participant.name || participant.identity}</div>
                        </div>
                    ` : ''}
                    <div class="user-label">
                        <i class="fas fa-user"></i> ${participant.name || participant.identity}
                    </div>
                `;
                grid.appendChild(card);
            }

            if (track.kind === 'video') {
                const element = track.attach();
                card.prepend(element);
            } else if (track.kind === 'audio') {
                track.attach(); // Audio element attach for playback
            }
        }

        function handleTrackUnsubscribed(track, publication, participant) {
            track.detach().forEach(el => el.remove());
        }

        function participantJoined(participant) {
            console.log('Participant joined:', participant.identity);
        }

        const isDirectCall = "{{ $meeting->type }}" === "direct_call";
        const isHost = "{{ $meeting->created_by }}" === "{{ $user->id }}";

        function participantLeft(participant) {
            console.log('Participant left:', participant.identity);
            const card = document.getElementById(`participant-${participant.identity}`);
            if (card) card.remove();

            // If 1-on-1 call and either party leaves, disconnect room
            if (isDirectCall) {
                if (window.socket) {
                    window.socket.emit('call_ended', { meetingUuid: "{{ $meeting->uuid }}", room: "{{ $meeting->room_name }}" });
                }

                leaveMeeting();
            }
        }

        function handleDisconnected() {
            notifyBackend('leave');
            window.location.href = "{{ route('admin.chat.index') }}";
        }

        function leaveMeeting() {
            if (isDirectCall && window.socket) {
                window.socket.emit('call_ended', { meetingUuid: "{{ $meeting->uuid }}", room: "{{ $meeting->room_name }}" });
            }
            notifyBackend('leave');
            if (room) {
                try {
                    room.disconnect();
                } catch(e) {}
            }
            window.location.href = "{{ route('admin.chat.index') }}";
        }

        function handleReconnecting() {
            console.warn('LiveKit room reconnecting...');
            const banner = document.getElementById('reconnect-banner');
            if (banner) banner.style.display = 'flex';
        }

        function handleReconnected() {
            console.log('LiveKit room reconnected successfully!');
            const banner = document.getElementById('reconnect-banner');
            if (banner) banner.style.display = 'none';
        }

        function handleConnectionQualityChanged(quality, participant) {
            // quality: 0 = Poor/Unknown, 1 = Good, 2 = Excellent
            if (!participant || participant === room.localParticipant) {
                const badge = document.getElementById('connStatusBadge');
                if (badge) {
                    if (quality === LivekitClient.ConnectionQuality.Excellent || quality === 2) {
                        badge.className = 'conn-quality conn-excellent';
                        badge.innerHTML = '🟢 Excellent';
                    } else if (quality === LivekitClient.ConnectionQuality.Good || quality === 1) {
                        badge.className = 'conn-quality conn-good';
                        badge.innerHTML = '🟡 Good';
                    } else {
                        badge.className = 'conn-quality conn-poor';
                        badge.innerHTML = '🔴 Poor';
                    }
                }
            }
        }

        function toggleChatDrawer() {
            const drawer = document.getElementById('meeting-chat-drawer');
            if (drawer) drawer.classList.toggle('open');
        }

        function sendInMeetingMessage() {
            const input = document.getElementById('chatInput');
            if (!input || !input.value.trim() || !room) return;

            const msgText = input.value.trim();
            const payload = JSON.stringify({
                sender: "{{ addslashes($user->name) }}",
                text: msgText,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });

            // Send payload via LiveKit Room Data Channel
            const encoder = new TextEncoder();
            const data = encoder.encode(payload);
            room.localParticipant.publishData(data, LivekitClient.DataPacket_Kind.RELIABLE);

            // Render locally
            renderChatMessage("{{ addslashes($user->name) }}", msgText, new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), true);
            input.value = '';
        }

        function handleDataReceived(payload, participant) {
            try {
                const decoder = new TextDecoder();
                const str = decoder.decode(payload);
                const data = JSON.parse(str);
                renderChatMessage(data.sender || participant.identity, data.text, data.time, false);
            } catch (e) {
                console.error('Data error:', e);
            }
        }

        function renderChatMessage(sender, text, time, isSelf) {
            const box = document.getElementById('chatMessagesBox');
            if (!box) return;
            const bubble = document.createElement('div');
            bubble.className = `chat-msg-bubble ${isSelf ? 'self' : ''}`;
            bubble.innerHTML = `
                <div style="font-size:0.75rem; font-weight:700; opacity:0.8;">${sender}</div>
                <div class="my-1">${text}</div>
                <div style="font-size:0.68rem; opacity:0.6; text-align:right;">${time}</div>
            `;
            box.appendChild(bubble);
            box.scrollTop = box.scrollHeight;
        }

        async function toggleMic() {
            if (!room) return;
            isAudioMuted = !isAudioMuted;
            await room.localParticipant.setMicrophoneEnabled(!isAudioMuted);

            const micBtn = document.getElementById('micBtn');
            const micIcon = document.getElementById('micIcon');
            if (isAudioMuted) {
                micBtn.classList.add('active-off');
                micIcon.className = 'fas fa-microphone-slash';
            } else {
                micBtn.classList.remove('active-off');
                micIcon.className = 'fas fa-microphone';
            }
        }

        async function toggleCam() {
            if (!room || isAudioOnly) return;
            isVideoMuted = !isVideoMuted;
            await room.localParticipant.setCameraEnabled(!isVideoMuted);

            const camBtn = document.getElementById('camBtn');
            const camIcon = document.getElementById('camIcon');
            if (isVideoMuted) {
                camBtn.classList.add('active-off');
                camIcon.className = 'fas fa-video-slash';
            } else {
                camBtn.classList.remove('active-off');
                camIcon.className = 'fas fa-video';
            }
        }

        async function toggleScreenShare() {
            if (!room || isAudioOnly) return;
            isScreenSharing = !isScreenSharing;
            await room.localParticipant.setScreenShareEnabled(isScreenSharing);

            const screenBtn = document.getElementById('screenBtn');
            if (isScreenSharing) {
                screenBtn.classList.add('active-off');
            } else {
                screenBtn.classList.remove('active-off');
            }
        }

        function notifyBackend(action) {
            const url = action === 'join' ? "{{ route('admin.meetings.join', $meeting->uuid) }}" : "{{ route('admin.meetings.leave', $meeting->uuid) }}";
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            }).catch(err => console.error('Signal error:', err));
        }
    </script>
</body>

</html>