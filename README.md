# Team Tasker - Enterprise ERP & Support Platform

**Team Tasker** is a state-of-the-art, Laravel-powered Enterprise Resource Planning (ERP), Task Management, and Unified Collaboration Platform designed for high-performance teams. It combines strategic task management with real-time WhatsApp-style chat, location-aware attendance tracking, integrated video/audio meetings, support ticketing, and a installable Progressive Web App (PWA) experience.

---

## 🚀 Key Modules & Features

### 📍 Geolocation Attendance Tracking (Location-Aware Punch In/Out)
- **Mandatory Location Enforcement**: Employees must grant location access (latitude/longitude) before they can Punch In or Punch Out.
- **Reverse Geocoding**: Automatically converts raw GPS coordinates into human-readable street addresses via OpenStreetMap.
- **Location Auditing**: Admins can inspect Punch In and Punch Out geographic addresses across Daily/Monthly attendance reports and edit modals.

### 📱 Progressive Web App (PWA) Support
- **Mobile & Desktop Installable**: Fully customizable PWA experience with native app launcher support (`manifest.json`).
- **Dynamic Manifest & Branding Generator**: Application title, start URLs, themes, and 192x192 / 512x512 icons automatically sync when updated via Admin Settings.
- **Standalone Mobile Experience**: Optimized navigation and layout when launched as an installed app.

### 🎥 Native Audio / Video Meetings (Livekit Integration)
- **Instant Video Collaboration**: Integrated secure video conferencing powered by Livekit Meet.
- **Meeting Management**: Schedule meetings, send automated user invites, track meeting logs, and launch video calls directly within the portal.

### 💬 WhatsApp-Style Unified Team Chat
- **Real-Time Messaging**: Integrated live chat with audio/video call integration and individual/group messaging.
- **Status & Presence Picker**: Real-time user status indicators (**Online**, **Away**, **Busy**, **Offline**).
- **Attachment & Media Sharing**: Share images, documents, and code snippets within chat threads.

### 📝 Strategic Task Management
- **Interactive Views**: Manage tasks via **List**, **Kanban Board**, **Calendar**, and **Gantt Chart** views.
- **Smart Logic**: Recurring tasks, status tracking with customizable colors, priority sorting, and automated activity logs.
- **Time Tracking**: Billed hour tracking and work log history.

### 🎫 Advanced Support Ticket System
- **Omnichannel Support**: Centralized ticket management with priority levels, custom status flows, and SLA breach tracking.
- **Client Ticket Portal**: Allows clients to submit support tickets, view updates, and convert tickets into active tasks.

### 🚪 Dedicated Client Portal & RBAC
- **Single Portal Entry**: Consolidated authentication routing Master Admins, Staff, and Clients to their respective workspaces.
- **Air-Tight Security**: Granular Role-Based Access Control (RBAC) preventing unauthorized access across modules.

---

## 🛠️ Technical Stack

- **Backend Framework**: Laravel 12 (PHP 8.2+)
- **Frontend & Reactivity**: Livewire 3, Alpine.js, Bootstrap 5.3
- **Styling & Themes**: Custom Dark / Light Mode with Glassmorphism Design System
- **Real-Time Communication**: WebSockets / Reverb / Firebase Cloud Messaging (FCM)
- **Video Conferencing**: Livekit Meet Web API
- **Maps & Geolocation**: Browser Geolocation API + OpenStreetMap Nominatim Reverse Geocoding
- **PWA**: Web App Manifest & Service Worker

---

## 🔧 Installation & Setup

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / MariaDB

### Step-by-Step Installation

1. **Clone & Install Dependencies:**
   ```bash
   git clone https://github.com/softlancerIn/team-tasker.git
   cd team-tasker
   composer install
   npm install
   ```

2. **Environment Configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding:**
   ```bash
   php artisan migrate --seed
   ```

4. **Asset Compilation:**
   ```bash
   npm run build
   ```

5. **Serve the Application:**
   ```bash
   php artisan serve
   ```

---

## 🎨 Branding & Settings Management
Team Tasker includes a comprehensive **Settings Module** in the Admin Panel:
- **PWA & App Customization**: Dynamically update application name, logos, favicons, and auto-generated PWA app launcher icons.
- **Office WiFi & Geolocation Security**: Define allowed office IP addresses and office start/end timings for strict attendance enforcement.
- **Custom Statuses**: Configure custom workflows and color themes for tasks and support tickets.

---

## 🤝 Contributing
We welcome contributions to Team Tasker. Please ensure code standards are maintained before submitting pull requests.

## 📄 License
Team Tasker is open-source software licensed under the [MIT License](LICENSE).

## 📮 Contact
**Softlancer Pvt Ltd** - [softlancer.in@gmail.com](mailto:softlancer.in@gmail.com)  
Noida, Uttar Pradesh, India