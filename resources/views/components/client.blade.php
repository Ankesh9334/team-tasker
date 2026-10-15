@props(['title' => 'Client Dashboard | Team Tasker', 'fullscreen' => false, 'hideSidebar' => false, 'noPadding' => false])
@php
    $appSettings = \App\Models\Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
    $appLogo = $appSettings['app_logo'] ?? null;
    $currentTheme = request()->cookie('theme', 'dark');
    if (!in_array($currentTheme, ['dark', 'light'])) {
        $currentTheme = 'dark';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme }}" data-bs-theme="{{ $currentTheme }}" style="background-color: {{ $currentTheme === 'light' ? '#f1f5f9' : '#0f172a' }}; color-scheme: {{ $currentTheme }};">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin Dashboard | Team Tasker' }}</title>

    <!-- Synchronous Anti-FOUC Theme Pre-initializer: Runs before any CSS or DOM rendering -->
    <script>
        (function() {
            try {
                var stored = localStorage.getItem('theme');
                var theme = stored || document.documentElement.getAttribute('data-theme') || 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.documentElement.style.backgroundColor = (theme === 'light' ? '#f1f5f9' : '#0f172a');
                document.documentElement.style.colorScheme = theme;
                if (stored && document.cookie.indexOf('theme=' + stored) === -1) {
                    document.cookie = 'theme=' + stored + ';path=/;max-age=31536000;SameSite=Lax';
                }
            } catch (e) {}
        })();
    </script>

    @if ($appLogo)
        <link rel="icon" href="{{ asset('storage/' . $appLogo) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    @livewireStyles

    <!-- Firebase SDK (Compat) -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap & Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/tinymce@5/tinymce.min.js' referrerpolicy="origin"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <script>
        // Global Chat Editor Component for Alpine.js
        // Global Chat Editor Component for Alpine.js
        window.chatEditor = (wire, props) => ({
            editorStr: props && props.editorId ? props.editorId : 'message-editor',
            conversationId: props ? props.conversationId : null,
            userId: props ? props.userId : null,
            userName: props ? props.userName : null,
            themeChangeListener: null,

            init() {
                this.mountEditor();

                // Listen for theme changes
                this.themeChangeListener = (e) => {
                    const newTheme = e.detail && e.detail.theme ? e.detail.theme : localStorage.getItem(
                        'theme');
                    this.mountEditor(newTheme);
                };
                window.addEventListener('theme-changed', this.themeChangeListener);
            },

            mountEditor(theme = null) {
                // Ensure TinyMCE is loaded
                if (typeof tinymce === 'undefined') {
                    console.error('TinyMCE not loaded');
                    return;
                }

                const savedTheme = theme || localStorage.getItem('theme') || 'dark';
                const isDark = savedTheme === 'dark';

                // Remove existing instance if any (safety check)
                let existingEditor = tinymce.get(this.editorStr);
                let currentContent = '';

                if (existingEditor) {
                    try {
                        currentContent = existingEditor.getContent();
                        existingEditor.remove();
                    } catch (e) {
                        console.warn('Error removing editor:', e);
                    }
                }


                tinymce.init({
                    selector: '#' + this.editorStr,
                    height: 100,
                    skin: isDark ? 'oxide-dark' : 'oxide',
                    content_css: isDark ? 'dark' : 'default',
                    branding: false,
                    placeholder: 'Type a message...',
                    plugins: [
                        'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor',
                        'pagebreak',
                        'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code',
                        'fullscreen',
                        'insertdatetime', 'media', 'table', 'emoticons', 'help'
                    ],
                    menubar: false,
                    statusbar: false,
                    resize: false,
                    toolbar_mode: 'sliding',
                    toolbar: 'undo redo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | forecolor backcolor | table | bullist numlist',
                    extended_valid_elements: 'i[class|style],table[class|style],th[class|style],td[class|style],h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style]',
                    valid_elements: '*[*]',
                    entity_encoding: 'raw',
                    remove_trailing_brs: false,
                    valid_children: '+body[style|i]',
                    content_style: `
                        body { 
                            background: ${isDark ? '#0f172a' : '#ffffff'} !important; 
                            color: ${isDark ? '#f8fafc' : '#0f172a'}; 
                            font-family: 'Outfit', sans-serif; 
                            font-size: 14px; 
                            margin: 0; 
                            padding: 10px 0px; 
                            line-height: 1.5; 
                        } 
                        p { margin: 0; } 
                        i { font-style: italic; } 
                        .mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before { 
                            color: ${isDark ? 'rgba(255, 255, 255, 0.4)' : 'rgba(0, 0, 0, 0.3)'} !important; 
                            font-family: 'Outfit', sans-serif;
                            font-style: normal;
                        }
                    `,
                    setup: (editor) => {
                        editor.on('change keyup', () => {
                            let content = editor.getContent();
                            if (wire) wire.set('body', content);
                            // Emit typing event to socket
                            if (window.socket && this.conversationId) {
                                window.socket.emit('typing', {
                                    room: 'chat.' + this.conversationId,
                                    userId: this.userId,
                                    userName: this.userName
                                });
                            }
                        });
                        editor.on('keydown', async (e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                const content = editor.getContent();
                                // Check if the preview element exists (indicates attachment is present)
                                const hasAttachment = document.querySelector(
                                    '.chat-media-preview') !== null;

                                // Send if text exists OR attachment exists
                                if ((content && content.trim() !== '') || hasAttachment) {
                                    if (wire) {
                                        await wire.sendMessage(content);
                                    }
                                    editor.resetContent();
                                }
                            }
                        });
                        editor.on('init', function () {
                            const container = editor.getContainer();
                            if (container) {
                                container.style.border = 'none';
                                container.style.background = 'transparent';
                            }
                            // Restore content if reloading
                            if (currentContent) {
                                editor.setContent(currentContent);
                            }
                        });
                    }
                });
            }
        });

        // Global Initialization for other editors (Task Description, etc.)
        window.initGlobalEditors = (theme = null) => {
            if (typeof tinymce === 'undefined') return;

            const savedTheme = theme || localStorage.getItem('theme') || 'dark';
            const isDark = savedTheme === 'dark';

            // Remove existing instances to avoid duplicates on re-init
            document.querySelectorAll('.rich-editor').forEach(el => {
                if (el.id) {
                    const ed = tinymce.get(el.id);
                    if (ed) ed.remove();
                }
            });

            tinymce.init({
                selector: '.rich-editor',
                height: 400,
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                branding: false,
                placeholder: 'Describe in detail...',
                plugins: [
                    'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor',
                    'pagebreak',
                    'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code', 'fullscreen',
                    'insertdatetime',
                    'media', 'table', 'emoticons', 'help'
                ],
                menubar: true,
                toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | styleselect',
                extended_valid_elements: 'i[class|style],table[class|style],th[class|style],td[class|style],h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style]',
                valid_elements: '*[*]',
                content_style: isDark ?
                    'body { background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent), #1a2436; color: rgba(255, 255, 255, 0.8); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; } i { font-style: italic; } body.mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before { color: rgba(255, 255, 255, 0.4); }' :
                    'body { background: #ffffff; color: #333; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; } i { font-style: italic; }',
                entity_encoding: 'raw',
                remove_trailing_brs: false,
                valid_children: '+body[style|i]',
                setup: function (editor) {
                    editor.on('init', function () {
                        const container = editor.getContainer();
                        if (container) {
                            container.style.border = isDark ? '1px solid rgba(99, 102, 241, 0.3)' :
                                '1px solid #ced4da';
                            container.style.borderRadius = '8px';
                        }
                    });
                }
            });
        };

        // Initialize on load if editors exist
        document.addEventListener('DOMContentLoaded', () => {
            if (document.querySelector('.rich-editor')) {
                window.initGlobalEditors();
            }
        });

        // Listen for theme changes globally
        window.addEventListener('theme-changed', (e) => {
            const newTheme = e.detail && e.detail.theme ? e.detail.theme : localStorage.getItem('theme');
            window.initGlobalEditors(newTheme);
        });

        // ── Rich Editor Form Submit Fix ──────────────────────────────────────────
        // TinyMCE hides the original <textarea>, so `required` blocks submission.
        // Before any form submits, we: 1) sync all TinyMCE instances to their
        // underlying textarea, 2) check data-required fields are non-empty.
        document.addEventListener('submit', function (e) {
            if (typeof tinymce === 'undefined') return;

            const form = e.target;

            // 1. Sync every TinyMCE editor that lives inside this form
            tinymce.editors.forEach(function (editor) {
                const el = document.getElementById(editor.id);
                if (el && form.contains(el)) {
                    editor.save(); // copies editor content into the textarea
                }
            });

            // 2. Validate data-required textareas (the ones we stripped `required` from)
            const requiredEditors = form.querySelectorAll('textarea[data-required="true"]');
            for (let ta of requiredEditors) {
                const val = (ta.value || '').replace(/<[^>]*>/g, '').trim(); // strip HTML tags
                if (!val) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Highlight the TinyMCE container visually
                    const editorContainer = ta.nextElementSibling;
                    if (editorContainer && editorContainer.classList.contains('tox-tinymce')) {
                        editorContainer.style.border = '1px solid #ef4444';
                        editorContainer.style.borderRadius = '8px';
                        setTimeout(() => {
                            editorContainer.style.border = '';
                            editorContainer.style.borderRadius = '';
                        }, 3000);
                    }
                    // Show a SweetAlert if available, else a plain alert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Required Field', text: 'Please fill in the description field.', confirmButtonColor: '#6366f1' });
                    } else {
                        alert('Please fill in the description field.');
                    }
                    return false;
                }
            }
        }, true); // `true` = capture phase, fires before native validation


        // Global Chat Messages Component
        window.chatMessages = (wire, props) => ({
            conversationId: props.conversationId,
            userId: props.userId,
            receiverId: props.receiverId,
            isTyping: false,
            typingUser: '',
            typingTimeout: null,
            socket: null,

            init() {
                this.scrollToBottom();
                if (wire && wire.messages) {
                    this.$watch('$wire.messages', () => {
                        setTimeout(() => this.scrollToBottom(), 100)
                    });
                }

                if (typeof io !== 'undefined' && {{ env('ENABLE_WEBSOCKETS', false) ? 'true' : 'false' }}) {
                    const host = window.location.hostname;
                    this.socket = window.socket || io(`http://${host}:3000`);
                    const roomId = `chat.${this.conversationId}`;

                    this.socket.emit('join_room', roomId);

                    const onReceiveMessage = (data) => {
                        if (data.action === 'delete') {
                            // Find the deleted message and either remove it from DOM or trigger Livewire reload
                            if (wire) wire.call('loadConversation', this.conversationId);
                        } else if (data.user_id != this.userId) {
                            if (wire) wire.call('loadConversation', this.conversationId);
                        }
                    };

                    const onUserTyping = (data) => {
                        if (data.userId != this.userId) {
                            this.isTyping = true;
                            this.typingUser = data.userName;
                            clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => {
                                this.isTyping = false;
                            }, 3000);
                        }
                    };

                    const onUserStopTyping = (data) => {
                        if (data.userId != this.userId) {
                            this.isTyping = false;
                        }
                    };

                    this.socket.on('receive_message', onReceiveMessage);
                    this.socket.on('user_typing', onUserTyping);
                    this.socket.on('user_stop_typing', onUserStopTyping);

                    const onSendMessageToNode = (event) => {
                        const data = event.detail[0] || event.detail;
                        if (data && data.room && data.message) {
                            this.socket.emit('send_message', data);
                            this.socket.emit('stop_typing', {
                                room: roomId,
                                userId: this.userId
                            });
                        }
                    };
                    window.addEventListener('send-message-to-node', onSendMessageToNode);

                    // Cleanup function
                    return () => {
                        this.socket.off('receive_message', onReceiveMessage);
                        this.socket.off('user_typing', onUserTyping);
                        this.socket.off('user_stop_typing', onUserStopTyping);
                        window.removeEventListener('send-message-to-node', onSendMessageToNode);
                        this.socket.emit('leave_room', roomId);
                    };
                }
            },

            scrollToBottom() {
                const el = document.getElementById('chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            }
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* GLOBAL HEADER & LAYOUT REFINEMENTS */
        .layout-header-premium {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
            z-index: 1050;
            backdrop-filter: blur(10px);
        }

        .sidebar-premium {
            top: 70px !important;
            height: calc(100vh - 70px) !important;
            border-top: none !important;
            z-index: 1045;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border-subtle) transparent;
        }

        .sidebar-premium::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-premium::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-premium::-webkit-scrollbar-thumb {
            background: var(--border-subtle);
            border-radius: 10px;
        }

        .main-content-premium {
            margin-top: 70px;
            margin-left: 280px;
            /* Sidebar width */
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-base);
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* TinyMCE Dark Mode Overrides */
        [data-theme="dark"] .tox-tinymce {
            border: 1px solid var(--border-main) !important;
        }

        [data-theme="dark"] .tox .tox-toolbar,
        [data-theme="dark"] .tox .tox-toolbar__overflow,
        [data-theme="dark"] .tox .tox-toolbar__primary {
            background: var(--bg-surface) !important;
        }

        [data-theme="dark"] .tox .tox-tbtn {
            color: var(--text-high) !important;
        }

        [data-theme="dark"] .tox .tox-tbtn:hover {
            background: var(--bg-input) !important;
        }

        [data-theme="dark"] .tox .tox-tbtn--enabled,
        [data-theme="dark"] .tox .tox-tbtn--enabled:hover {
            background: rgba(var(--primary-rgb), 0.2) !important;
        }

        .header-utils {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-medium);
            background: var(--bg-input);
            border: 1px solid var(--border-subtle);
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .header-icon-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--bg-surface);
        }

        .header-search-premium {
            flex-grow: 1;
            max-width: 500px;
            margin: 0 40px;
            position: relative;
        }

        .header-search-premium i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-low);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .header-search-premium input {
            width: 100%;
            padding: 9px 16px 9px 40px;
            background: var(--bg-input);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            color: var(--text-high);
            font-size: 0.85rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .header-search-premium input:focus {
            border-color: var(--primary);
            background: var(--bg-surface);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        @media (max-width: 991.98px) {
            .sidebar-premium {
                transform: translateX(-100%);
                transition: transform var(--transition-base);
                z-index: 1045 !important;
            }

            .sidebar-premium.mobile-open {
                transform: translateX(0) !important;
                box-shadow: 20px 0 50px rgba(0, 0, 0, 0.5);
            }

            .main-content-premium {
                margin-left: 0 !important;
                padding-top: var(--space-2) !important;
            }

            .layout-header-premium {
                left: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .header-search-premium {
                display: none;
            }

            .main-content-premium {
                padding-top: var(--space-2) !important;
                margin-left: 0 !important;
            }
        }

        @media (max-width: 576px) {
            .layout-header-premium {
                padding: 0 10px !important;
            }

            .header-utils {
                gap: 8px !important;
            }

            .header-icon-btn {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                border-radius: 8px !important;
            }

            .mobile-toggle-premium {
                width: 36px !important;
                height: 36px !important;
                margin-right: 4px !important;
            }

            .avatar-premium {
                width: 34px !important;
                height: 34px !important;
                font-size: 0.8rem !important;
            }
        }

        @media (max-width: 420px) {
            .header-utils {
                gap: 5px !important;
            }

            .sidebar-brand-text {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        $notificationCount = Auth::guard('client')->check() ? Auth::guard('client')->user()->unreadNotifications->count() : 0;
    @endphp

    @if(!$fullscreen)
        <header class="layout-header-premium">
            @php
                $appSettings = \App\Models\Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
                $appName = $appSettings['app_name'] ?? 'TeamTasker';
                $appLogo = $appSettings['app_logo'] ?? null;
            @endphp
            <div class="d-flex align-items-center gap-2">
                <button class="mobile-toggle-premium d-lg-none" id="mobileSidebarToggle" style="margin-right: 0;">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="{{ route('client.dashboard') }}"
                    class="sidebar-brand text-decoration-none p-0 border-0 bg-transparent">
                    @if ($appLogo)
                        <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo"
                            style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px;">
                    @else
                        <i class="fas fa-layer-group text-primary" style="font-size: 1.5rem;"></i>
                    @endif
                    <span class="text-high fw-bold sidebar-brand-text"
                        style="font-size: 1.25rem; letter-spacing: -0.5px;">{{ $appName }}</span>
                </a>
            </div>

            <!-- Global Search -->
            @if (!request()->routeIs('client.chat.index'))
                <form action="{{ route('search.global') }}" method="GET" class="header-search-premium">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Search tickets, tasks..." value="{{ request('q') }}">
                </form>
            @endif

            <div class="header-utils">
                <!-- Theme Toggle -->
                <button class="header-icon-btn" id="themeToggle" title="Toggle Theme">
                    <i class="fas {{ $currentTheme === 'dark' ? 'fa-moon' : 'fa-sun' }}"></i>
                </button>

                <!-- Notification Placeholder -->
                <button class="header-icon-btn position-relative" title="Notifications" data-bs-toggle="modal"
                    data-bs-target="#notificationsModal">
                    <i class="far fa-bell"></i>
                    @if ($notificationCount > 0)
                        <span class="notification-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
                    @endif
                </button>

                <!-- User Profile -->
                <div class="dropdown" x-data="{
                        myStatus: localStorage.getItem('user_presence_status_' + {{ Auth::guard('client')->id() }}) || 'online',
                        getStatusColor() {
                            if (this.myStatus === 'away') return '#f59e0b';
                            if (this.myStatus === 'busy') return '#ea4335';
                            if (this.myStatus === 'offline') return '#6b7280';
                            return '#00a884';
                        },
                        init() {
                            const updateStatus = (st) => {
                                this.myStatus = st;
                            };
                            window.addEventListener('user-status-changed-event', (e) => {
                                if (e.detail && Number(e.detail.userId) === Number({{ Auth::guard('client')->id() }})) {
                                    updateStatus(e.detail.status);
                                }
                            });
                            window.addEventListener('all-user-statuses-updated', (e) => {
                                const saved = localStorage.getItem('user_presence_status_' + {{ Auth::guard('client')->id() }});
                                if (saved) updateStatus(saved);
                            });
                        }
                    }">
                    <div class="user-profile-premium p-0 bg-transparent border-0 pe-1" data-bs-toggle="dropdown"
                        style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                        <div class="avatar-premium position-relative"
                            style="width: 38px; height: 38px; border: 1px solid var(--border-main);">
                            @if (Auth::guard('client')->user()->profile_image)
                                <img alt="team-tasker"
                                    src="{{ asset('storage/' . Auth::guard('client')->user()->profile_image) }}" alt="Profile">
                            @else
                                {{ substr(Auth::guard('client')->user()->name ?? 'U', 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-premium mt-3"
                        style="min-width: 200px; border-radius: 12px; border: 1px solid var(--border-subtle);">
                        <li class="px-3 py-3"
                            style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-input); border-radius: 12px 12px 0 0;">
                            <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-high);">
                                {{ Auth::guard('client')->user()->name }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-low);">
                                {{ Auth::guard('client')->user()->email }}
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2 mt-1" href="#" data-bs-toggle="modal"
                                data-bs-target="#profileModal"><i class="fas fa-user-edit me-2 text-primary"></i> Edit
                                Profile</a></li>
                        <li>
                            <hr class="dropdown-divider" style="opacity: 0.1;">
                        </li>
                        <li><a class="dropdown-item text-danger py-2 mb-1" href="{{ route('logout') }}"><i
                                    class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
    @endif

    @if(!$fullscreen)
        <aside class="sidebar-premium {{ $hideSidebar ? 'd-lg-none' : '' }}">

            <nav>
                <a href="{{ route('client.dashboard') }}"
                    class="nav-link-premium {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> Dashboard
                    <a href="{{ route('client.dashboard') }}#tickets"
                        class="nav-link-premium {{ request()->routeIs('client.tickets.*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i> Tickets
                    </a>
                    <a href="{{ route('client.dashboard') }}#tasks"
                        class="nav-link-premium {{ request()->routeIs('client.tasks.*') ? 'active' : '' }}">
                        <i class="fas fa-tasks"></i> Tasks
                    </a>
                    <a href="{{ route('client.projects.index') }}"
                        class="nav-link-premium {{ request()->routeIs('client.projects.*') ? 'active' : '' }}">
                        <i class="fas fa-project-diagram"></i> Projects
                    </a>
                    <a href="{{ route('client.chat.index') }}"
                        class="nav-link-premium d-flex align-items-center {{ request()->routeIs('client.chat.index') ? 'active' : '' }}">
                        <i class="fas fa-comments"></i> Chats
                        <livewire:global-chat-badge />
                    </a>
            </nav>
        </aside>
    @endif

    <main class="main-content-premium"
        style="{{ $fullscreen ? 'margin: 0 !important; padding: 0 !important; max-width: 100% !important;' : ($hideSidebar ? 'margin-left: 0 !important; max-width: 100% !important;' : '') }} {{ $noPadding ? 'padding: 0 !important;' : '' }}">
        {{ $slot }}
    </main>

    <!-- Notifications Modal -->
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-premium"
                style="border-radius: 20px; border: 1px solid var(--border-subtle); background: var(--bg-surface); backdrop-filter: blur(20px);">
                <div class="modal-header border-0 px-4 pt-4 pb-0">
                    <h5 class="modal-title fw-bold text-high">Notifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="px-4 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-low small">{{ $notificationCount }} Unread</span>
                        @if ($notificationCount > 0)
                            <form action="{{ route('client.notifications.markAsRead') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-link text-primary text-decoration-none p-0 small"
                                    style="font-size: 0.75rem;">Mark all as read</button>
                            </form>
                        @endif
                    </div>
                    <div class="notification-list-wrapper" style="max-height: 400px; overflow-y: auto;">
                        @forelse (Auth::guard('client')->user()->notifications()->latest()->take(20)->get() as $notification)
                            @php
                                $type = 'task';
                                $icon = 'fa-tasks';
                                $url = route('client.notifications.readAndRedirect', $notification->id);

                                if (!empty($notification->data['conversation_id']) || str_contains($notification->type, 'Chat')) {
                                    $type = 'chat';
                                    $icon = 'fa-comment-dots';
                                } elseif (!empty($notification->data['ticket_id']) || str_contains($notification->type, 'Ticket')) {
                                    $type = 'ticket';
                                    $icon = 'fa-ticket-alt';
                                } elseif (str_contains($notification->type, 'Sla')) {
                                    $type = 'alert';
                                    $icon = 'fa-exclamation-triangle';
                                }
                            @endphp
                            <a href="{{ $url }}"
                                class="notification-item-premium {{ $notification->unread() ? 'unread' : '' }}">
                                <div class="notification-icon-wrapper notification-icon-{{ $type }}">
                                    <i class="fas {{ $icon }}"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        {{ $notification->data['message'] ?? 'New Notification' }}
                                    </div>
                                    <div class="notification-description">
                                        {{ $notification->data['title'] ?? ($notification->data['description'] ?? 'No additional details') }}
                                    </div>
                                    <div class="notification-time">{{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                @if ($notification->unread())
                                    <div class="unread-indicator-dot"></div>
                                @endif
                            </a>
                        @empty
                            <div class="p-5 text-center">
                                <i class="far fa-bell-slash text-low mb-3" style="font-size: 2rem;"></i>
                                <div class="text-low small">No notifications found</div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-premium-secondary btn-sm w-100"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Edit Modal -->
    <x-modal id="profileModal" title="Edit Profile" submitText="Save Changes"
        formAction="{{ route('client.profile.update') }}" enctype="multipart/form-data">
        <div class="text-center mb-4">
            <div class="avatar-premium mx-auto mb-3"
                style="width: 72px; height: 72px; font-size: 1.75rem; border: 3px solid var(--border-main);">
                @if (Auth::guard('client')->user()->profile_image)
                    <img alt="team-tasker" src="{{ asset('storage/' . Auth::guard('client')->user()->profile_image) }}"
                        alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    {{ substr(Auth::guard('client')->user()->name ?? 'U', 0, 1) }}
                @endif
            </div>
            <div class="text-low small mb-2">Update your profile picture</div>
            <input type="file" name="profile_image" class="form-premium-control py-2" style="font-size: 0.8rem;">
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Full Name</label>
            <input type="text" name="name" value="{{ Auth::guard('client')->user()->name }}"
                class="form-premium-control" required>
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Email Address</label>
            <input type="email" name="email" value="{{ Auth::guard('client')->user()->email }}"
                class="form-premium-control" required>
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">New Password <span class="text-low"
                    style="font-weight: 400;">(leave blank to keep current)</span></label>
            <input type="password" name="password" class="form-premium-control"
                placeholder="ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¢">
        </div>
    </x-modal>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        @if (session('success'))
            <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-toast="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div id="errorToast" class="toast align-items-center text-white bg-danger border-0 shadow-lg" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Theme Toggle Logic
            const themeToggle = document.getElementById('themeToggle');
            const icon = themeToggle ? themeToggle.querySelector('i') : null;
            const html = document.documentElement;

            // Sync saved theme or default
            const savedTheme = localStorage.getItem('theme') || html.getAttribute('data-theme') || 'dark';
            html.setAttribute('data-theme', savedTheme);
            html.setAttribute('data-bs-theme', savedTheme);
            html.style.backgroundColor = savedTheme === 'light' ? '#f1f5f9' : '#0f172a';
            html.style.colorScheme = savedTheme;
            updateIcon(savedTheme);

            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const currentTheme = html.getAttribute('data-theme') || 'dark';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    html.classList.add('theme-toggling');
                    html.setAttribute('data-theme', newTheme);
                    html.setAttribute('data-bs-theme', newTheme);
                    html.style.backgroundColor = newTheme === 'light' ? '#f1f5f9' : '#0f172a';
                    html.style.colorScheme = newTheme;
                    localStorage.setItem('theme', newTheme);
                    document.cookie = 'theme=' + newTheme + ';path=/;max-age=31536000;SameSite=Lax';
                    updateIcon(newTheme);

                    setTimeout(() => {
                        html.classList.remove('theme-toggling');
                    }, 300);

                    // Dispatch event for components like TinyMCE to react
                    window.dispatchEvent(new CustomEvent('theme-changed', {
                        detail: {
                            theme: newTheme
                        }
                    }));
                });
            }

            function updateIcon(theme) {
                if (!icon) return;
                if (theme === 'dark') {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                } else {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }

            // Request Notification Permission on user interaction
            document.addEventListener('click', function () {
                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            }, {
                once: true
            });

            // Sidebar Mobile Toggle & Icon Switcher
            const sidebar = document.querySelector('.sidebar-premium');
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            function updateToggleIcon(isOpen) {
                const toggles = document.querySelectorAll('#mobileSidebarToggle, #chatMobileSidebarToggle');
                toggles.forEach(btn => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        if (isOpen) {
                            icon.classList.remove('fa-bars');
                            icon.classList.add('fa-times');
                        } else {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    }
                });
            }

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function () {
                    const isOpen = sidebar.classList.toggle('mobile-open');
                    if (isOpen) {
                        if (overlay) overlay.classList.add('show');
                    } else {
                        if (overlay) overlay.classList.remove('show');
                    }
                    updateToggleIcon(isOpen);
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function () {
                    if (sidebar) sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('show');
                    updateToggleIcon(false);
                });
            }

            window.updateSidebarToggleIcons = updateToggleIcon;

            // Socket.IO Connection
            if (typeof io !== 'undefined' && {{ env('ENABLE_WEBSOCKETS', false) ? 'true' : 'false' }}) {
                const userId = {{ auth()->id() }};
                const host = window.location.hostname;
                const socket = io(`http://${host}:3000`);
                window.socket = socket; // Make available globally

                socket.on('connect', () => {
                    console.log('Connected to Socket.IO server');
                    socket.emit('user_connected', userId);
                });

                // Online Users Tracking
                socket.on('online_users', (users) => {
                    // Update UI for online users
                    document.querySelectorAll('[data-user-id]').forEach(el => {
                        const uid = parseInt(el.getAttribute('data-user-id'));
                        const dot = el.querySelector('.status-dot');
                        if (users.includes(uid)) {
                            if (dot) dot.classList.remove('bg-secondary');
                            if (dot) dot.classList.add('bg-success');
                        } else {
                            if (dot) dot.classList.remove('bg-success');
                            if (dot) dot.classList.add('bg-secondary');
                        }
                    });
                });
            }
        });

        // Firebase Cloud Messaging Integration
        const firebaseConfig = {
            apiKey: "{{ config('services.firebase.api_key') }}",
            authDomain: "{{ config('services.firebase.auth_domain') }}",
            projectId: "{{ config('services.firebase.project_id') }}",
            storageBucket: "{{ config('services.firebase.storage_bucket') }}",
            messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
            appId: "{{ config('services.firebase.app_id') }}",
            measurementId: "{{ config('services.firebase.measurement_id') }}"
        };

        if (firebaseConfig.apiKey) {
            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();

            function requestPermission() {
                console.log('Requesting FCM permission...');
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        console.log('Notification permission granted.');
                        messaging.getToken({
                            vapidKey: "{{ config('services.firebase.vapid_key') }}"
                        }).then((currentToken) => {
                            if (currentToken) {
                                sendTokenToServer(currentToken);
                            } else {
                                console.warn(
                                    'No registration token available. Request permission to generate one.'
                                );
                            }
                        }).catch((err) => {
                            console.error('An error occurred while retrieving token. ', err);
                        });
                    } else {
                        console.warn('Notification permission denied.');
                    }
                });
            }

            function sendTokenToServer(token) {
                fetch("{{ route('update.fcm_token') }}", {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        token: token
                    })
                })
                    .then(response => response.json())
                    .then(data => console.log('FCM Token sync:', data))
                    .catch(err => console.error('Error syncing FCM token:', err));
            }

            if (Notification.permission === 'granted') {
                requestPermission();
            } else {
                document.addEventListener('click', () => {
                    if (Notification.permission === 'default') {
                        requestPermission();
                    }
                }, {
                    once: true
                });
            }

            messaging.onMessage((payload) => {
                console.log('Message received. ', payload);

                const incomingConversationId = payload.data?.conversation_id;
                const activeConversationInput = document.getElementById('active-conversation-id');
                const activeConversationId = activeConversationInput ? activeConversationInput.value : null;

                if (document.visibilityState === 'visible' && incomingConversationId && String(activeConversationId) === String(incomingConversationId)) {
                    return;
                }

                const msgId = payload.messageId || new Date().getTime();
                const storageKey = 'fcm_msg_' + msgId;
                if (localStorage.getItem(storageKey)) {
                    return;
                }
                localStorage.setItem(storageKey, '1');
                setTimeout(() => localStorage.removeItem(storageKey), 10000);

                const notificationTitle = payload.notification ? payload.notification.title : (payload.data?.title || 'New Notification');
                const notificationOptions = {
                    body: payload.notification ? payload.notification.body : (payload.data?.body || ''),
                    icon: '/images/logo.png',
                    data: payload.data || {}
                };

                const notif = new Notification(notificationTitle, notificationOptions);
                notif.onclick = function(event) {
                    event.preventDefault();
                    window.focus();
                    if (incomingConversationId) {
                        window.location.href = "{{ route('client.chat.index') }}?conversation_id=" + incomingConversationId;
                    } else if (payload.data?.task_id) {
                        window.location.href = "/client/tasks/" + payload.data.task_id;
                    } else if (payload.data?.ticket_id) {
                        window.location.href = "/client/tickets/" + payload.data.ticket_id;
                    }
                };

                if (window.Livewire) {
                    window.Livewire.dispatch('notification-received');
                }
            });
        }
    </script>
    @livewireScripts
</body>

</html>