# 🚀 LiveKit Integration & Setup Guide - Team Tasker

This document details the configuration and architecture for the **LiveKit** audio, video, and screen sharing integration in Team Tasker.

---

## 🛠️ Step 1: Execute Automated LiveKit Setup

Run the setup command to publish `.env` variables, run database migrations, and refresh system caches:

```bash
php artisan calling:setup
```

---

## ⚙️ Step 2: Environment Variables (`.env`)

```env
LIVEKIT_URL=wss://demo.livekit.cloud
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
CALL_RING_TIMEOUT=30
ENABLE_WEBSOCKETS=true
```

> **For Cloud / Self-hosted LiveKit:**
> Replace `LIVEKIT_URL`, `LIVEKIT_API_KEY`, and `LIVEKIT_API_SECRET` with your LiveKit Cloud or self-hosted LiveKit instance credentials.

---

## 📡 Step 3: Start Node.js Signaling Server

Start the real-time Socket.IO server for incoming call popups and ringtone signaling:

```bash
node socket-server/server.js
```

---

## 💻 Step 4: Run Laravel Application

```bash
php artisan serve
```

---

## 🔑 Key Architecture Details

- **`App\Services\MeetingProviders\LiveKitMeetingProvider`:** Handles room creation and HMAC-SHA256 JWT access token generation for participants.
- **`resources/views/admin/meetings/join.blade.php`:** Powered by the official LiveKit Web SDK (`livekit-client`). Includes custom audio/video grid UI, camera toggle, microphone toggle, screen sharing, and automatic cleanup on call exit.
