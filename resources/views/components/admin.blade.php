@props(['title' => 'Admin Dashboard | Team Tasker', 'fullscreen' => false, 'hideSidebar' => false, 'noPadding' => false])
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

    @if(env('ENABLE_WEBSOCKETS', false))
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    @endif

    <!-- PWA Web App Manifest & Meta Tags -->
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#00a884">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $appSettings['app_name'] ?? 'TeamTasker' }}">
    <link rel="apple-touch-icon" href="{{ ($appLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($appLogo)) ? asset('storage/' . $appLogo) : asset('icons/icon-192x192.png') }}">

    @if ($appLogo)
        <link rel="icon" href="{{ asset('storage/' . $appLogo) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- Firebase SDK (Compat) -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

    @livewireStyles

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

    {{-- TomSelect - JS only, we own all CSS --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    {{-- TomSelect Full Custom CSS - fully owned, matches text box design --}}
    <style>
        /* ======= TomSelect Required Base Styles ======= */
        .ts-hidden-accessible {
            clip: rect(0 0 0 0) !important;
            border: 0 !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            padding: 0 !important;
            position: absolute !important;
            white-space: nowrap !important;
            width: 1px !important;
        }

        .ts-wrapper {
            position: relative;
            display: block;
        }

        .ts-control {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            z-index: 1;
        }

        .ts-control>* {
            display: inline-block;
            vertical-align: baseline;
        }

        .ts-control>input {
            flex: 1 1 auto;
            min-width: 7rem;
            max-width: 100%;
            border: 0 !important;
            box-shadow: none !important;
            background: none !important;
            outline: none !important;
            padding: 0 !important;
            margin: 0 !important;
            min-height: 0 !important;
            max-height: none !important;
            line-height: inherit !important;
            color: inherit !important;
        }

        .ts-control>input:focus {
            outline: none !important;
        }

        .ts-wrapper.single .ts-control,
        .ts-wrapper.single .ts-control>input {
            cursor: pointer;
        }

        .ts-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .ts-dropdown-content {
            overflow-y: auto;
            max-height: 220px;
            overflow-x: hidden;
        }

        .ts-dropdown [data-selectable] {
            cursor: pointer;
            overflow: hidden;
        }

        .ts-dropdown [data-selectable].option {
            cursor: pointer;
        }

        /* Plugin: clear_button */
        .plugin-clear_button .clear-button {
            background: transparent !important;
            cursor: pointer;
            opacity: 0;
            position: absolute;
            right: 2.2rem;
            top: 50%;
            transform: translateY(-50%);
            transition: opacity .3s;
            border: none;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
        }

        .plugin-clear_button.focus.has-items .clear-button,
        .plugin-clear_button:not(.disabled):hover.has-items .clear-button {
            opacity: 0.6;
        }

        /* Plugin: remove_button */
        .ts-wrapper.plugin-remove_button .item {
            align-items: center;
            display: inline-flex;
            padding-right: 0 !important;
        }

        .ts-wrapper.plugin-remove_button .item .remove {
            display: inline-block;
            padding: 0 5px;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
        }

        .input-hidden .ts-control>input {
            left: -10000px;
            opacity: 0;
            position: absolute;
        }

        .ts-control,
        .ts-wrapper.single .ts-control,
        .ts-wrapper.multi .ts-control {
            background-color: var(--bg-input) !important;
            background-image: var(--ts-arrow-bg, var(--ts-arrow-dark)) !important;
            background-repeat: no-repeat !important;
            background-position: right 0.85rem center !important;
            background-size: 11px 11px !important;
            border: 1px solid var(--border-main) !important;
            border-radius: var(--radius-md) !important;
            color: var(--text-high) !important;
            padding: 0 2rem 0 0.85rem !important;
            height: 38px !important;
            min-height: 38px !important;
            box-shadow: none !important;
            cursor: pointer !important;
            font-family: 'Outfit', sans-serif !important;
            font-size: 0.85rem !important;
            display: flex !important;
            align-items: center !important;
            box-sizing: border-box !important;
        }

        .ts-wrapper.single .ts-control {
            height: 38px !important;
            min-height: 38px !important;
            max-height: 38px !important;
            line-height: 36px !important;
            padding: 0 2rem 0 0.85rem !important;
            flex-wrap: nowrap !important;
        }

        .ts-wrapper.multi .ts-control {
            min-height: 38px !important;
            height: auto !important;
            padding: 3px 2rem 3px 0.5rem !important;
        }

        .ts-wrapper.focus .ts-control,
        .ts-wrapper.focus.single .ts-control,
        .ts-wrapper.focus.multi .ts-control {
            border-color: var(--primary) !important;
            background-color: var(--bg-surface) !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .ts-wrapper .ts-control input,
        .ts-control input {
            color: var(--text-high) !important;
            background: transparent !important;
            caret-color: var(--text-high) !important;
            font-size: 0.85rem !important;
            height: 36px !important;
            line-height: 36px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* When NOT focused and has items: hide the input so only selected item shows */
        .ts-wrapper.single.has-items:not(.focus):not(.dropdown-active) .ts-control>input {
            width: 0 !important;
            min-width: 0 !important;
            position: absolute !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* When focused / dropdown active: SHOW the input field so user can search options */
        .ts-wrapper.single.focus .ts-control>input,
        .ts-wrapper.single.dropdown-active .ts-control>input {
            width: 100% !important;
            min-width: 60px !important;
            position: relative !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            cursor: text !important;
            display: inline-block !important;
            flex: 1 1 auto !important;
        }

        /* When no option is selected (data-value="") or when typing (input-active): hide item so input field shows cleanly */
        .ts-wrapper.single.focus .ts-control .item[data-value=""],
        .ts-wrapper.single.dropdown-active .ts-control .item[data-value=""],
        .ts-wrapper.single.input-active .ts-control .item {
            display: none !important;
        }

        .ts-wrapper .ts-control input::placeholder {
            color: var(--text-low) !important;
            opacity: 0.7 !important;
            font-size: 0.85rem !important;
            font-family: 'Outfit', sans-serif !important;
        }

        /* Single-select: Plain text, NO pill/tag background */
        .ts-wrapper.single .ts-control .item {
            background: transparent !important;
            color: var(--text-high) !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            display: inline-block !important;
            border: none !important;
            box-shadow: none !important;
            line-height: 36px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: calc(100% - 1.5rem) !important;
        }

        .ts-wrapper.single .ts-control .item .remove {
            display: none !important;
        }

        /* Multi-select Tags */
        .ts-wrapper.multi .ts-control .item {
            background: var(--primary) !important;
            color: #fff !important;
            border-radius: var(--radius-sm) !important;
            padding: 2px 8px !important;
            margin: 2px !important;
            font-size: 0.82rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
        }

        .ts-wrapper.multi .ts-control .item .remove {
            color: #fff !important;
            border-left: 1px solid rgba(255, 255, 255, 0.3) !important;
            margin-left: 5px !important;
            padding-left: 5px !important;
        }

        .ts-dropdown,
        .ts-dropdown.single,
        .ts-dropdown.multi {
            background: #1a2436 !important;
            border: 1px solid var(--border-main) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(20px) !important;
            color: var(--text-high) !important;
            z-index: 2000 !important;
        }

        .ts-dropdown .option,
        .ts-dropdown .ts-dropdown-content .option {
            color: var(--text-high) !important;
            background: transparent !important;
            padding: 0.6rem 1rem !important;
        }

        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
            background: rgba(var(--primary-rgb), 0.12) !important;
            color: var(--primary) !important;
        }

        .ts-dropdown .optgroup-header {
            color: var(--text-low) !important;
            background: transparent !important;
        }

        /* Light mode overrides */
        [data-theme="light"] .ts-control,
        [data-theme="light"] .ts-wrapper.single .ts-control,
        [data-theme="light"] .ts-wrapper.multi .ts-control {
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        [data-theme="light"] .ts-wrapper.focus .ts-control,
        [data-theme="light"] .ts-wrapper.focus.single .ts-control {
            background: #ffffff !important;
            border-color: var(--primary) !important;
        }

        [data-theme="light"] .ts-wrapper .ts-control input {
            color: #0f172a !important;
        }

        [data-theme="light"] .ts-dropdown,
        [data-theme="light"] .ts-dropdown.single {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
            color: #0f172a !important;
        }

        [data-theme="light"] .ts-dropdown .option,
        [data-theme="light"] .ts-dropdown .ts-dropdown-content .option {
            color: #0f172a !important;
        }

        [data-theme="light"] .ts-wrapper.multi .ts-control .item {
            background: rgba(var(--primary-rgb), 0.1) !important;
            color: var(--primary) !important;
        }

        [data-theme="light"] .ts-wrapper.single .ts-control .item {
            background: transparent !important;
            color: #0f172a !important;
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
        [data-theme="dark"] .tox .tox-tbtn--enabled, [data-theme="dark"] .tox .tox-tbtn--enabled:hover {
            background: rgba(var(--primary-rgb), 0.2) !important;
        }
    </style>

    <script>
        // Global Chat Editor Component for Alpine.js
        // Global Chat Editor Component for Alpine.js
        window.chatEditor = (wire, props) => ({
            editorStr: props && props.editorId ? props.editorId : 'message-editor',
            conversationId: props ? props.conversationId : null,
            userId: props ? props.userId : null,
            userName: props ? props.userName : null,
            themeChangeListener: null,
            isTypingSent: false,
            typingTimeout: null,

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
                        editor.on('input change keyup', () => {
                            // Always emit typing event (debounced stop)
                            if (this.conversationId && this.userId) {
                                window.dispatchEvent(new CustomEvent('user-typing-to-node', {
                                    detail: {
                                        room: this.conversationId,
                                        userId: this.userId,
                                        userName: this.userName
                                    }
                                }));
                            }

                            // Reset the stop-typing timer
                            if (this.typingTimeout) clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => {
                                if (this.conversationId && this.userId) {
                                    window.dispatchEvent(new CustomEvent(
                                        'user-stop-typing-to-node', {
                                            detail: {
                                                room: this.conversationId,
                                                userId: this.userId
                                            }
                                        }));
                                }
                            }, 3000);
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

                                    // Stop typing via global CustomEvent
                                    window.dispatchEvent(new CustomEvent(
                                        'user-stop-typing-to-node', {
                                            detail: {
                                                room: this.conversationId,
                                                userId: this.userId
                                            }
                                        }));
                                }
                            }
                        });
                        editor.on('init', function() {
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
                setup: function(editor) {
                    editor.on('init', function() {
                        const container = editor.getContainer();
                        if (container) {
                            container.style.border = isDark ? '1px solid rgba(99, 102, 241, 0.3)' :
                                '1px solid #ced4da';
                            container.style.borderRadius = '8px';
                        }
                    });
                    editor.on('change keyup', function() {
                        const content = editor.getContent();
                        const textarea = editor.getElement();
                        textarea.value = content;
                        textarea.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
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

                window.addEventListener('user-typing-indicator', (event) => {
                    const roomMatch = String(event.detail.room) === String(this.conversationId);
                    const isNotMe = String(event.detail.userId) !== String(this.userId);

                    if (roomMatch && isNotMe) {
                        this.isTyping = true;
                        this.typingUser = event.detail.userName;

                        if (this.typingTimeout) clearTimeout(this.typingTimeout);
                        this.typingTimeout = setTimeout(() => {
                            this.isTyping = false;
                        }, 3000);
                    }
                });

                window.addEventListener('user-stop-typing-indicator', (event) => {
                    const roomMatch = String(event.detail.room) === String(this.conversationId);
                    const isNotMe = String(event.detail.userId) !== String(this.userId);

                    if (roomMatch && isNotMe) {
                        this.isTyping = false;
                    }
                });
            },

            scrollToBottom() {
                const el = document.getElementById('chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            }
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Mobile Navigation Fixes */
        .mobile-toggle-premium {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: var(--bg-input);
            border: 1px solid var(--border-main);
            color: var(--text-medium);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-base);
            margin-right: var(--space-3);
        }

        .mobile-toggle-premium:hover {
            border-color: var(--primary);
            color: var(--primary);
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

            .top-bar-premium {
                padding-left: var(--space-3) !important;
                padding-right: var(--space-3) !important;
            }

            .header-search {
                width: 100% !important;
                max-width: none !important;
                margin-right: var(--space-2);
            }

            .main-content-premium {
                margin-left: 0 !important;
                padding-top: var(--space-2) !important;
            }

            .layout-header-premium {
                left: 0 !important;
            }

            .mobile-hide {
                display: none !important;
            }
        }

        /* NEW GLOBAL HEADER STYLES */
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

            .header-icon-btn,
            .header-utils .btn-premium {
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
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')
</head>

<body>
    @php
        $notificationCount = Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
        $globalActiveTimer = Auth::check() ? \App\Models\TimeLog::where('user_id', Auth::id())->whereNull('end_time')->first() : null;
    @endphp

    @if(!$fullscreen)
    <header class="layout-header-premium" style="border-top: 3px solid {{ $globalActiveTimer ? 'var(--danger)' : 'var(--primary)' }};">
        @php
            $appSettings = \App\Models\Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
            $appName = $appSettings['app_name'] ?? 'TeamTasker';
            $appLogo = $appSettings['app_logo'] ?? null;
        @endphp
        <div class="d-flex align-items-center gap-2">
            <button class="mobile-toggle-premium d-lg-none" id="mobileSidebarToggle" style="margin-right: 0;">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('dashboard') }}" class="sidebar-brand text-decoration-none mt-2 border-0 bg-transparent">
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
        @if (!request()->routeIs('admin.chat.index'))
        <form action="{{ route('search.global') }}" method="GET" class="header-search-premium">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Search tasks, tickets, users..."
                value="{{ request('q') }}">
        </form>
        @endif

        <div class="header-utils">
            @php
                $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
                $currentAuthId = auth()->id() ?? auth('admin')->id();
                $myAttendance = \App\Models\Attendance::where('user_id', $currentAuthId)
                    ->where(function($q) use ($todayDate) {
                        $q->where('attendance_date', $todayDate)
                          ->orWhere('date', $todayDate);
                    })->first();
            @endphp
            
            {{-- Clock In / Clock Out (Icon Only) --}}
            @if(!$myAttendance || (!$myAttendance->check_in && !$myAttendance->clock_in))
                <button type="button" class="btn-premium btn-premium-primary btn-sm p-0 d-inline-flex align-items-center justify-content-center" 
                    data-bs-toggle="modal" data-bs-target="#globalClockInModal" 
                    title="Clock In" 
                    style="width: 38px; height: 38px; border-radius: 8px;">
                    <i class="fas fa-sign-in-alt"></i>
                </button>
            @elseif(!$myAttendance->check_out && !$myAttendance->clock_out)
                <button type="button" class="btn-premium btn btn-danger btn-sm p-0 d-inline-flex align-items-center justify-content-center m-0" 
                    data-bs-toggle="modal" data-bs-target="#globalClockOutModal" 
                    title="Clock Out" 
                    style="width: 38px; height: 38px; border-radius: 8px;">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            @else
                <button type="button" class="btn-premium btn-sm p-0 d-inline-flex align-items-center justify-content-center" 
                    disabled 
                    title="Shift Ended" 
                    style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fas fa-check-circle"></i>
                </button>
            @endif

            {{-- Submit Work Report to Super Admin (Icon Only) --}}
            @if($myAttendance && $myAttendance->isApproved())
                <button type="button" class="btn-premium btn-sm p-0 d-inline-flex align-items-center justify-content-center m-0" 
                    data-bs-toggle="modal" data-bs-target="#globalSubmitReportModal" 
                    title="Daily Work Report Approved by Super Admin - Click to View" 
                    style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fas fa-check-double text-success"></i>
                </button>
            @elseif($myAttendance && $myAttendance->isPending())
                <button type="button" class="btn-premium btn-sm p-0 d-inline-flex align-items-center justify-content-center m-0" 
                    data-bs-toggle="modal" data-bs-target="#globalSubmitReportModal" 
                    title="Daily Work Report Submitted - Pending Super Admin Approval" 
                    style="width: 38px; height: 38px; border-radius: 8px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <i class="fas fa-hourglass-half text-warning"></i>
                </button>
            @elseif($myAttendance && $myAttendance->isRejected())
                <button type="button" class="btn-premium btn btn-danger btn-sm p-0 d-inline-flex align-items-center justify-content-center m-0 text-white" 
                    data-bs-toggle="modal" data-bs-target="#globalSubmitReportModal" 
                    title="Daily Work Report Rejected by Super Admin - Click to Resubmit" 
                    style="width: 38px; height: 38px; border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </button>
            @else
                <button type="button" class="btn-premium btn-premium-primary btn-sm p-0 d-inline-flex align-items-center justify-content-center m-0" 
                    data-bs-toggle="modal" data-bs-target="#globalSubmitReportModal" 
                    title="Submit Daily Work Report for Super Admin Approval" 
                    style="width: 38px; height: 38px; border-radius: 8px;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            @endif

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
                myStatus: localStorage.getItem('user_presence_status_' + {{ Auth::id() }}) || 'online',
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
                        if (e.detail && Number(e.detail.userId) === Number({{ Auth::id() }})) {
                            updateStatus(e.detail.status);
                        }
                    });
                    window.addEventListener('all-user-statuses-updated', (e) => {
                        const saved = localStorage.getItem('user_presence_status_' + {{ Auth::id() }});
                        if (saved) updateStatus(saved);
                    });
                }
            }">
                <div class="user-profile-premium p-0 bg-transparent border-0 pe-1" data-bs-toggle="dropdown"
                    style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                    <div class="avatar-premium position-relative"
                        style="width: 38px; height: 38px; border: 1px solid var(--border-main);">
                        @if (Auth::user()->profile_image)
                            <img alt="team-tasker" src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="Profile">
                        @else
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        @endif
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-premium mt-3"
                    style="min-width: 200px; border-radius: 12px; border: 1px solid var(--border-subtle);">
                    <li class="px-3 py-3"
                        style="border-bottom: 1px solid var(--border-subtle); background: var(--bg-input); border-radius: 12px 12px 0 0;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-high);">
                            {{ Auth::user()->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-low);">{{ Auth::user()->email }}</div>
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
            @if (Auth::user()->hasPermission('dashboard.view') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('dashboard') }}"
                    class="nav-link-premium {{ request()->routeIs('dashboard') || request()->is('admin/tasks/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            @endif

            @if (Auth::user()->hasPermission('projects.view|projects.create|projects.edit|projects.delete') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('admin.projects.index') }}"
                    class="nav-link-premium {{ request()->routeIs('admin.projects.*') || request()->is('admin/projects*') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i> Projects
                </a>
            @endif

            @if (Auth::user()->hasPermission('tasks.view|tasks.create|tasks.edit|tasks.my_tasks|tasks.todo|tasks.activity|tasks.board|tasks.calendar|tasks.gantt') || Auth::user()->hasRole('super-admin'))
                @php
                    $isTasksActive = (request()->is('admin/tasks*') && !request()->is('admin/tasks/dashboard')) 
                                  || request()->is('admin/todos*') 
                                  || request()->routeIs('index', 'create', 'edit', 'details', 'update', 'delete', 'tasks.*', 'admin.todos.*');
                    $isMyTasksActive = request()->routeIs('index', 'create', 'edit', 'details', 'update', 'delete') 
                                    || (request()->is('admin/tasks') || request()->is('admin/tasks/create') || request()->is('admin/tasks/edit/*') || request()->is('admin/tasks/details/*'));
                @endphp
                <div class="nav-item">
                    <a href="javascript:void(0)"
                        class="nav-link-premium nav-dropdown {{ $isTasksActive ? 'active' : '' }}"
                        onclick="toggleSubmenu(this)">
                        <i class="fas fa-tasks"></i>
                        <span>Tasks</span>
                        <i
                            class="fas fa-chevron-right ms-auto toggle-icon-premium {{ $isTasksActive ? 'rotate' : '' }}"></i>
                    </a>
                    <div
                        class="nav-submenu-premium {{ $isTasksActive ? 'show' : '' }}">

                        @if (Auth::user()->hasPermission('tasks.my_tasks|tasks.view|tasks.create|tasks.edit') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('index') }}"
                                class="nav-link-premium sub-link-premium {{ $isMyTasksActive ? 'active' : '' }}">
                                <i class="fas fa-list"></i> My Tasks
                            </a>
                        @endif
                        @if (Auth::user()->hasPermission('tasks.todo') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('admin.todos.index') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.todos.*') || request()->is('admin/todos*') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-list"></i> My To-Do List
                            </a>
                        @endif
                        @if (Auth::user()->hasPermission('tasks.activity') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('tasks.activity') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('tasks.activity*') || request()->is('admin/tasks/activity*') ? 'active' : '' }}">
                                <i class="fas fa-stream"></i> My Task Activity
                            </a>
                        @endif
                        @if (Auth::user()->hasPermission('tasks.board') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('tasks.board') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('tasks.board*') || request()->is('admin/tasks/board*') ? 'active' : '' }}">
                                <i class="fas fa-columns"></i> Task Board
                            </a>
                        @endif
                        @if (Auth::user()->hasPermission('tasks.calendar') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('tasks.calendar') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('tasks.calendar*') || request()->is('admin/tasks/calendar*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-alt"></i> Calendar
                            </a>
                        @endif
                        @if (Auth::user()->hasPermission('tasks.gantt') || Auth::user()->hasRole('super-admin'))
                            <a href="{{ route('tasks.gantt') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('tasks.gantt*') || request()->is('admin/tasks/gantt*') ? 'active' : '' }}">
                                <i class="fas fa-chart-bar"></i> Gantt Chart
                            </a>
                        @endif
                    </div>
                </div>
            @endif


            @if (Auth::user()->hasPermission('chat.view') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('admin.chat.index') }}"
                    class="nav-link-premium d-flex align-items-center {{ request()->routeIs('admin.chat.*') || request()->is('admin/chat*') ? 'active' : '' }}">
                    <i class="fas fa-comments"></i><span>Team Chat</span>
                    <livewire:global-chat-badge />
                </a>
            @endif

            @if (Auth::user()->hasPermission('meetings.view') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('admin.meetings.index') }}"
                    class="nav-link-premium {{ request()->routeIs('admin.meetings.*') || request()->is('admin/meetings*') ? 'active' : '' }}">
                    <i class="fas fa-video"></i> <span>Meetings & Calls</span>
                </a>
            @endif

            @if (Auth::user()->hasPermission('tickets.view') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('admin.tickets.index') }}"
                    class="nav-link-premium {{ request()->routeIs('admin.tickets.*') || request()->is('admin/tickets*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt"></i> Tickets
                </a>
            @endif

            @if (Auth::user()->hasPermission('clients.view') || Auth::user()->hasRole('super-admin'))
                <a href="{{ route('admin.clients.index') }}"
                    class="nav-link-premium {{ request()->routeIs('admin.clients.*') || request()->is('admin/clients*') ? 'active' : '' }}">
                    <i class="fas fa-user-tie"></i> Clients
                </a>
            @endif

            @if (Auth::user()->hasPermission('attendance.view') || Auth::user()->hasPermission('attendance.punch') || Auth::user()->hasPermission('attendance.daily') || Auth::user()->hasPermission('attendance.monthly') || Auth::user()->hasPermission('attendance.dashboard_overview') || Auth::user()->hasPermission('attendance.settings'))
            @php
                $isAttendanceActive = request()->routeIs('admin.attendance.*');
            @endphp
            <div class="nav-item">
                <a href="javascript:void(0)"
                    class="nav-link-premium nav-dropdown {{ $isAttendanceActive ? 'active' : '' }}"
                    onclick="toggleSubmenu(this)">
                    <i class="fas fa-clock"></i>
                    <span>Attendance</span>
                    <i class="fas fa-chevron-right ms-auto toggle-icon-premium {{ $isAttendanceActive ? 'rotate' : '' }}"></i>
                </a>
                <div class="nav-submenu-premium {{ $isAttendanceActive ? 'show' : '' }}">
                    @if(Auth::user()->hasPermission('attendance.dashboard_overview') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.dashboard') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.daily') || Auth::user()->hasPermission('attendance.approve') || Auth::user()->hasPermission('attendance.manage') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.daily') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.daily') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice"></i> Daily Work Reports
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.monthly') || Auth::user()->hasPermission('attendance.report') || Auth::user()->hasPermission('attendance.manage') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.monthly') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.monthly') ? 'active' : '' }}">
                        <i class="fas fa-calendar-days"></i> Monthly Attendance
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.punch') || Auth::user()->hasPermission('attendance.submit') || Auth::user()->hasPermission('attendance.view'))
                    <a href="{{ route('admin.attendance.myDaily') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.myDaily') ? 'active' : '' }}">
                        <i class="fas fa-user-clock"></i> My Attendance
                    </a>
                    <a href="{{ route('admin.attendance.myMonthly') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.myMonthly') ? 'active' : '' }}">
                        <i class="fas fa-user-check"></i> My Monthly Report
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.calendar_all') || Auth::user()->hasPermission('attendance.view') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.calendar') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.calendar') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.requests_manage') || Auth::user()->hasPermission('attendance.view') || Auth::user()->hasPermission('hr.leave_requests') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.requests') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.requests') ? 'active' : '' }}">
                        <i class="fas fa-envelope-open-text"></i> Leave Requests
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('attendance.reports') || Auth::user()->hasPermission('attendance.report') || Auth::user()->hasPermission('attendance.export') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.reports') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.reports') ? 'active' : '' }}">
                        <i class="fas fa-file-export"></i> Reports
                    </a>
                    @endif
                    
                    @if(Auth::user()->hasPermission('attendance.settings') || Auth::user()->hasPermission('attendance.manage') || Auth::user()->hasRole('super-admin'))
                    <a href="{{ route('admin.attendance.settings') }}"
                        class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.attendance.settings') ? 'active' : '' }}">
                        <i class="fas fa-business-time"></i> Attendance Settings
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('settings.view') || Auth::user()->hasPermission('hr.view') || Auth::user()->hasPermission('hr.manage') || Auth::user()->hasPermission('users.view') || Auth::user()->hasPermission('roles.view') || Auth::user()->hasPermission('attendance.requests_manage'))
                @php
                    $isHrActive = request()->routeIs('admin.hr.*') || 
                                  request()->routeIs('admin.users.*') || 
                                  request()->routeIs('admin.roles.*') || 
                                  request()->routeIs('admin.settings.autostop');
                @endphp
                <div class="nav-item">
                    <a href="javascript:void(0)"
                        class="nav-link-premium nav-dropdown {{ $isHrActive ? 'active' : '' }}"
                        onclick="toggleSubmenu(this)">
                        <i class="fas fa-user-gear"></i>
                        <span>HR Settings</span>
                        <i class="fas fa-chevron-right ms-auto toggle-icon-premium {{ $isHrActive ? 'rotate' : '' }}"></i>
                    </a>
                    <div class="nav-submenu-premium {{ $isHrActive ? 'show' : '' }}">
                        @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('settings.view') || Auth::user()->hasPermission('hr.view'))
                            <a href="{{ route('admin.hr.settings') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.hr.settings') ? 'active' : '' }}">
                                <i class="fas fa-sliders-h"></i> HR Overview
                            </a>
                        @endif
                        @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('users.view'))
                            <a href="{{ route('admin.users.index') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="fas fa-users"></i> Users & Staff
                            </a>
                        @endif
                        @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('roles.view'))
                            <a href="{{ route('admin.roles.index') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <i class="fas fa-shield-halved"></i> Roles & Permissions
                            </a>
                        @endif
                        @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('settings.view') || Auth::user()->hasPermission('hr.manage'))
                            <a href="{{ route('admin.settings.autostop') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.autostop') ? 'active' : '' }}">
                                <i class="fas fa-stopwatch"></i> Shift & Timer Rules
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('settings.view'))
                @php
                    $isSettingsActive = (request()->routeIs('admin.settings.*') && !request()->routeIs('admin.hr.*') && !request()->routeIs('admin.settings.autostop'));
                @endphp
                <div class="nav-item">
                    <a href="javascript:void(0)"
                        class="nav-link-premium nav-dropdown {{ $isSettingsActive ? 'active' : '' }}"
                        onclick="toggleSubmenu(this)">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                        <i
                            class="fas fa-chevron-right ms-auto toggle-icon-premium {{ $isSettingsActive ? 'rotate' : '' }}"></i>
                    </a>
                    <div
                        class="nav-submenu-premium {{ $isSettingsActive ? 'show' : '' }}">
                        <a href="{{ route('admin.settings.general') }}"
                            class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.general') ? 'active' : '' }}">
                            <i class="fas fa-sliders-h"></i> General
                        </a>
                        <a href="{{ route('admin.settings.statuses') }}"
                            class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.statuses') ? 'active' : '' }}">
                            <i class="fas fa-check-circle"></i> Task Statuses
                        </a>
                        <a href="{{ route('admin.settings.tags') }}"
                            class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.tags') ? 'active' : '' }}">
                            <i class="fas fa-tags"></i> Tags
                        </a>
                        <a href="{{ route('admin.settings.email') }}"
                            class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.email') ? 'active' : '' }}">
                            <i class="fas fa-envelope"></i> Email Integration
                        </a>
                        @if (Auth::user()->hasRole('super-admin') || Auth::user()->hasPermission('settings.view'))
                            <a href="{{ route('admin.settings.chat-permissions') }}"
                                class="nav-link-premium sub-link-premium {{ request()->routeIs('admin.settings.chat-permissions') ? 'active' : '' }}">
                                <i class="fas fa-comments"></i> Chat Permissions
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </nav>
    </aside>
    @endif

    <main class="main-content-premium" style="{{ $fullscreen ? 'margin: 0 !important; padding: 0 !important; max-width: 100% !important;' : ($hideSidebar ? 'margin-left: 0 !important; max-width: 100% !important;' : '') }} {{ $noPadding ? 'padding: 0 !important;' : '' }}">
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
                            <form action="{{ route('notifications.markAsRead') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="btn btn-link text-primary text-decoration-none p-0 small"
                                    style="font-size: 0.75rem;">Mark all as read</button>
                            </form>
                        @endif
                    </div>
                    <div class="notification-list-wrapper" style="max-height: 400px; overflow-y: auto;">
                        @forelse (Auth::user()->notifications()->latest()->take(20)->get() as $notification)
                            @php
                                $type = 'task';
                                $icon = 'fa-tasks';
                                $url = route('notifications.readAndRedirect', $notification->id);

                                if (!empty($notification->data['conversation_id']) || str_contains($notification->type, 'Chat')) {
                                    $type = 'chat';
                                    $icon = 'fa-comment-dots';
                                } elseif (!empty($notification->data['ticket_id']) || str_contains($notification->type, 'Ticket')) {
                                    $type = 'ticket';
                                    $icon = 'fa-ticket-alt';
                                } elseif (str_contains($notification->type, 'Attendance')) {
                                    $type = 'attendance';
                                    $icon = 'fa-clock';
                                } elseif (str_contains($notification->type, 'Leave')) {
                                    $type = 'leave';
                                    $icon = 'fa-calendar-alt';
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
        formAction="{{ route('profile.update') }}" enctype="multipart/form-data">
        <div class="text-center mb-4">
            <div class="avatar-premium mx-auto mb-3"
                style="width: 72px; height: 72px; font-size: 1.75rem; border: 3px solid var(--border-main);">
                @if (Auth::user()->profile_image)
                    <img alt="team-tasker" src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="Profile"
                        style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                @endif
            </div>
            <div class="text-low small mb-2">Update your profile picture</div>
            <input type="file" name="profile_image" class="form-premium-control py-2" style="font-size: 0.8rem;">
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Full Name</label>
            <input type="text" name="name" value="{{ Auth::user()->name }}" class="form-premium-control"
                required>
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">Email Address</label>
            <input type="email" name="email" value="{{ Auth::user()->email }}" class="form-premium-control"
                required>
        </div>
        <div class="mb-3">
            <label class="heading-label mb-2" style="font-size: 0.7rem;">New Password <span class="text-low"
                    style="font-weight: 400;">(leave blank to keep current)</span></label>
            <input type="password" name="password" class="form-premium-control" placeholder="••••••••••••">
        </div>
    </x-modal>

    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "success",
                    title: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "error",
                    title: "{{ session('error') }}",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toasts handled by SweetAlert

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
            document.addEventListener('click', function() {
                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            }, {
                once: true
            });

            @if(env('ENABLE_WEBSOCKETS', false))
            // Socket.IO Initialization
            window.onlineUsers = [];
            const socketUrl = window.location.protocol + '//' + window.location.hostname + ':3000';
            window.socket = io(socketUrl);

            window.socket.on('connect', () => {
                console.log('Connected to Socket.IO server:', window.socket.id);
                @php
                    $isClientG = Auth::guard('client')->check();
                    $currentAuthUser = $isClientG ? Auth::guard('client')->user() : Auth::user();
                    $currentAuthId = $currentAuthUser ? $currentAuthUser->id : null;
                @endphp
                @if ($currentAuthUser)
                    window.socket.emit('user_connected', {{ $currentAuthId }});
                    window.socket.emit('get_user_statuses');

                    const savedStatus = localStorage.getItem('user_presence_status_' + {{ $currentAuthId }});
                    if (savedStatus) {
                        window.socket.emit('update_status', { userId: {{ $currentAuthId }}, status: savedStatus });
                    } else {
                        window.socket.emit('update_status', { userId: {{ $currentAuthId }}, status: 'online' });
                    }

                    // Auto-join all user's conversation rooms
                    @php
                        $userConversationIds = $currentAuthUser->conversations()->pluck('conversations.id')->toArray();
                    @endphp
                    const myRooms = @json($userConversationIds);
                    myRooms.forEach(roomId => {
                        window.socket.emit('join_room', roomId);
                        console.log('Auto-joined room:', roomId);
                    });
                @endif
            });

            window.socket.on('online_users', (users) => {
                window.onlineUsers = users;
                window.dispatchEvent(new CustomEvent('online-users-updated', {
                    detail: users
                }));
            });

            window.socket.on('all_user_statuses', (statuses) => {
                window.userStatuses = statuses;
                window.dispatchEvent(new CustomEvent('all-user-statuses-updated', {
                    detail: statuses
                }));
            });

            window.socket.on('user_status_changed', (data) => {
                if (!window.userStatuses) window.userStatuses = {};
                if (data && data.userId) {
                    window.userStatuses[data.userId] = data.status;
                }
                window.dispatchEvent(new CustomEvent('user-status-changed-event', {
                    detail: data
                }));
            });

            window.socket.on('receive_message', (data) => {
                Livewire.dispatch('socket-message-received', {
                    data
                });
                // Refresh the chat-list unread counts
                Livewire.dispatch('global-message-received');
            });

            // When the other user is online in the room, mark as delivered (double gray tick)
            window.socket.on('message_delivered', (data) => {
                Livewire.dispatch('socket-message-delivered', {
                    data
                });
            });

            window.socket.on('user_typing', (data) => {
                console.log('Socket typing event:', data);
                window.dispatchEvent(new CustomEvent('user-typing-indicator', {
                    detail: data
                }));
            });

            window.socket.on('user_stop_typing', (data) => {
                console.log('Socket stop typing event:', data);
                window.dispatchEvent(new CustomEvent('user-stop-typing-indicator', {
                    detail: data
                }));
            });

            window.socket.on('messages_read_by_user', (data) => {
                Livewire.dispatch('socket-messages-read', {
                    data
                });
            });

            // Global Call Listeners inside Socket Connection
            window.socket.on('incoming_call', function(data) {
                console.log('Incoming call received in browser:', data);
                currentCallData = data;

                const callerNameEl = document.getElementById('incomingCallerName');
                const callTypeEl = document.getElementById('incomingCallType');
                const callIconEl = document.getElementById('incomingCallIcon');

                if (callerNameEl) callerNameEl.innerText = data.callerName || 'Unknown Caller';
                if (callTypeEl) callTypeEl.innerText = data.mode === 'audio' ? '📞 Audio Call' : '📹 Video Call';
                if (callIconEl) callIconEl.className = data.mode === 'audio' ? 'fas fa-phone-alt' : 'fas fa-video';
                
                playRingtone();

                // 1. Show Bootstrap Modal
                if (incomingModal) {
                    incomingModal.show();
                }

                const callTitle = data.isGroup ? `Incoming Group ${data.mode === 'audio' ? 'Audio' : 'Video'} Call` : `Incoming ${data.mode === 'audio' ? 'Audio' : 'Video'} Call`;
                const callText = data.isGroup ? `<strong>${data.callerName}</strong> is calling group <strong>${data.groupName}</strong>...` : `<strong>${data.callerName}</strong> is calling you...`;

                // 2. Show SweetAlert Pickup Popup
                Swal.fire({
                    title: callTitle,
                    html: `<div class="py-2"><h4 class="fw-bold mb-1">${data.isGroup ? data.groupName : data.callerName}</h4><p class="text-muted">${callText}</p></div>`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-phone-alt me-1"></i> Accept Call',
                    cancelButtonText: '<i class="fas fa-phone-slash me-1"></i> Decline',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#ef4444',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        popup: 'glass-card border-main shadow-lg text-center'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        acceptIncomingCall();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        rejectIncomingCall();
                    }
                });
            });

            window.socket.on('call_accepted', function(data) {
                if (currentCallData && currentCallData.meeting.uuid === data.meetingUuid) {
                    stopOutgoingRingtone();
                    Swal.close();
                    window.location.href = currentCallData.join_url;
                }
            });

            window.socket.on('call_rejected', function(data) {
                stopOutgoingRingtone();
                Swal.fire('Call Rejected', 'The user declined your call.', 'warning');
                currentCallData = null;
            });

            window.socket.on('call_cancelled', function(data) {
                stopRingtone();
                stopOutgoingRingtone();
                incomingModal?.hide();
                Swal.close();
                Swal.fire('Call Cancelled', 'The caller cancelled the call.', 'info');
                currentCallData = null;
            });

            window.socket.on('call_ended', function(data) {
                stopRingtone();
                stopOutgoingRingtone();
                incomingModal?.hide();
                Swal.close();
                currentCallData = null;
                if (window.location.pathname.includes('/meetings/')) {
                    Swal.fire('Call Ended', 'The call was ended by the host.', 'info').then(() => {
                        window.location.href = "{{ route('admin.meetings.index') }}";
                    });
                }
            });
            @endif

            // Catch dispatch from Livewire to emit to Node
            window.addEventListener('join-chat-room', event => {
                if (window.socket) {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    window.socket.emit('join_room', data);
                }
            });

            window.addEventListener('send-message-to-node', event => {
                if (window.socket) {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    window.socket.emit('send_message', data);
                }
            });

            window.addEventListener('user-typing-to-node', event => {
                if (window.socket) {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    window.socket.emit('typing', data);
                }
            });

            window.addEventListener('user-stop-typing-to-node', event => {
                if (window.socket) {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    window.socket.emit('stop_typing', data);
                }
            });

            window.addEventListener('messages-read-to-node', event => {
                if (window.socket) {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    window.socket.emit('messages_read', data);
                }
            });

            // Submenu Toggle JS
            window.toggleSubmenu = function(el) {
                if (!el) return;
                const submenu = el.nextElementSibling;
                const icon = el.querySelector('.toggle-icon-premium') || el.querySelector('.toggle-icon');

                if (submenu) {
                    if (submenu.classList.contains('show')) {
                        submenu.classList.remove('show');
                        if (icon) icon.classList.remove('rotate');
                    } else {
                        submenu.classList.add('show');
                        if (icon) icon.classList.add('rotate');
                    }
                }
            };

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
                            window.location.href = "{{ route('admin.chat.index') }}?conversation_id=" + incomingConversationId;
                        } else if (payload.data?.task_id) {
                            window.location.href = "/admin/tasks/details/" + payload.data.task_id;
                        } else if (payload.data?.ticket_id) {
                            window.location.href = "/admin/tickets/" + payload.data.ticket_id;
                        }
                    };

                    if (window.Livewire) {
                        window.Livewire.dispatch('notification-received');
                    }
                });
            }
        });
    </script>
    <div id="sidebarOverlay" class="sidebar-overlay d-lg-none"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                mobileToggle.addEventListener('click', function() {
                    const isOpen = sidebar.classList.toggle('mobile-open');
                    if (isOpen) {
                        overlay.classList.add('show');
                    } else {
                        overlay.classList.remove('show');
                    }
                    updateToggleIcon(isOpen);
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('show');
                    updateToggleIcon(false);
                });
            }
            
            window.updateSidebarToggleIcons = updateToggleIcon;
            
            @if(empty($myAttendance))
            if (sessionStorage.getItem('punchInModalDismissed') !== 'true') {
                var punchModalElement = document.getElementById('globalClockInModal');
                if (punchModalElement) {
                    var punchModal = new bootstrap.Modal(punchModalElement);
                    punchModal.show();

                    punchModalElement.addEventListener('hidden.bs.modal', function () {
                        sessionStorage.setItem('punchInModalDismissed', 'true');
                    });
                }
            }
            @endif
        });
    </script>
    {{-- Page-specific scripts pushed via @push('scripts') --}}
    @stack('scripts')
    @livewireScripts
    
    <!-- Global Clock In Modal -->
    <div class="modal fade" id="globalClockInModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high">Punch In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="punchInForm" action="{{ route('admin.attendance.clockIn') }}" method="POST">
                    @csrf
                    <input type="hidden" name="latitude" id="punch_latitude">
                    <input type="hidden" name="longitude" id="punch_longitude">
                    <input type="hidden" name="location" id="punch_location">
                    
                    <div class="modal-body">
                        <p class="text-medium mb-2">You haven't punched in for today yet. Please record your attendance.</p>
                        
                        <div class="mb-3 p-2 rounded border border-subtle bg-subtle d-flex align-items-center gap-2" id="locationStatusContainer" style="font-size: 0.8rem;">
                            <i class="fas fa-map-marker-alt text-primary" id="locIcon"></i>
                            <span id="locationStatusText" class="text-low">Detecting your location...</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Note (Optional)</label>
                            <textarea name="notes" class="form-premium-control w-100" rows="3" placeholder="Working remotely, running late, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="punchInSubmitBtn" class="btn-premium btn-premium-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Punch In Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Clock Out Modal -->
    <div class="modal fade" id="globalClockOutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high">Confirm Clock Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="punchOutForm" action="{{ route('admin.attendance.clockOut') }}" method="POST">
                    @csrf
                    <input type="hidden" name="latitude" id="punch_out_latitude">
                    <input type="hidden" name="longitude" id="punch_out_longitude">
                    <input type="hidden" name="location" id="punch_out_location">

                    <div class="modal-body">
                        <p class="text-medium mb-2">Are you sure you want to clock out? This will end your shift for the day.</p>
                        
                        <div class="mb-3 p-2 rounded border border-subtle bg-subtle d-flex align-items-center gap-2" id="outLocationStatusContainer" style="font-size: 0.8rem;">
                            <i class="fas fa-map-marker-alt text-danger" id="outLocIcon"></i>
                            <span id="outLocationStatusText" class="text-low">Ready to punch out</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-high fw-semibold">Closing Note (Optional)</label>
                            <textarea name="notes" class="form-premium-control w-100" rows="3" placeholder="Completed tasks, handing over, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="punchOutSubmitBtn" class="btn-premium btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Confirm Clock Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Submit Work Report Modal -->
    <div class="modal fade" id="globalSubmitReportModal" tabindex="-1" aria-labelledby="globalSubmitReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main">
                <div class="modal-header border-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(0, 168, 132, 0.15); color: #00a884;">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-high m-0" id="globalSubmitReportModalLabel">Daily Work Report</h5>
                            <small class="text-low">{{ \Carbon\Carbon::today()->format('l, d F Y') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                @if($myAttendance && $myAttendance->isApproved())
                    <div class="modal-body">
                        <div class="p-3 rounded border border-success border-opacity-25 bg-success-subtle text-success text-center mb-3">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h6 class="fw-bold mb-1">Report Approved by Super Admin</h6>
                            <p class="small mb-0">Your work report for today was approved by <strong>{{ $myAttendance->approver_name ?? 'Super Admin' }}</strong>.</p>
                        </div>
                        @if($myAttendance->remarks)
                            <div class="p-3 rounded bg-subtle border border-subtle">
                                <label class="text-low small fw-semibold text-uppercase d-block mb-1">Submitted Work Summary</label>
                                <p class="text-high mb-0 small" style="white-space: pre-wrap;">{{ $myAttendance->remarks }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                @elseif($myAttendance && $myAttendance->isPending())
                    <div class="modal-body">
                        <div class="p-3 rounded border border-warning border-opacity-25 bg-warning-subtle text-warning text-center mb-3">
                            <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                            <h6 class="fw-bold mb-1">Pending Super Admin Approval</h6>
                            <p class="small mb-0">
                                Submitted at {{ $myAttendance->submitted_at ? $myAttendance->submitted_at->format('h:i A') : 'Today' }}.
                                Awaiting Super Admin review and approval.
                            </p>
                        </div>
                        @if($myAttendance->remarks)
                            <div class="p-3 rounded bg-subtle border border-subtle">
                                <label class="text-low small fw-semibold text-uppercase d-block mb-1">Submitted Work Summary</label>
                                <p class="text-high mb-0 small" style="white-space: pre-wrap;">{{ $myAttendance->remarks }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-subtle">
                        <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                @else
                    <form action="{{ route('admin.attendance.submitTodayReport') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if($myAttendance && $myAttendance->isRejected())
                                <div class="alert alert-danger border-0 p-3 mb-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-exclamation-circle fa-lg mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Report Rejected by Super Admin</h6>
                                            <p class="mb-1 small"><strong>Reason:</strong> {{ $myAttendance->rejection_reason }}</p>
                                            <small class="text-low">Please adjust your remarks or clarify details below and resubmit.</small>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="p-3 rounded bg-subtle border border-subtle mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-clock text-primary"></i>
                                            <div>
                                                <span class="text-low small d-block">Shift Status</span>
                                                <span class="text-high fw-semibold small">
                                                    {{ ($myAttendance && ($myAttendance->check_in || $myAttendance->clock_in)) ? \Carbon\Carbon::parse($myAttendance->check_in ?? $myAttendance->clock_in)->format('h:i A') : 'Active' }}
                                                    -
                                                    {{ ($myAttendance && ($myAttendance->check_out || $myAttendance->clock_out)) ? \Carbon\Carbon::parse($myAttendance->check_out ?? $myAttendance->clock_out)->format('h:i A') : 'End of Day' }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($myAttendance && $myAttendance->working_minutes > 0)
                                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                                {{ $myAttendance->formatted_working_hours }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label text-high fw-semibold">
                                    Daily Work Summary / Tasks Completed <span class="text-danger">*</span>
                                </label>
                                <textarea name="remarks" class="form-premium-control w-100" rows="4" required placeholder="Detail the tasks completed, projects worked on, blockers faced, and hours spent today...">{{ $myAttendance?->remarks }}</textarea>
                                <small class="text-low d-block mt-1">
                                    <i class="fas fa-shield-alt text-primary me-1"></i> This report will be sent to the Super Admin for daily verification and approval.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer border-subtle">
                            <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-premium btn-premium-primary">
                                <i class="fas fa-paper-plane me-2"></i> {{ ($myAttendance && $myAttendance->isRejected()) ? 'Resubmit Corrected Report' : 'Submit to Super Admin' }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Optional background location detection - strictly non-blocking
            const modalEl = document.getElementById('globalClockInModal');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function () {
                    const statusText = document.getElementById('locationStatusText');
                    const latInput = document.getElementById('punch_latitude');
                    const lngInput = document.getElementById('punch_longitude');
                    const locInput = document.getElementById('punch_location');
                    
                    if (navigator.geolocation && statusText) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            if (latInput) latInput.value = lat;
                            if (lngInput) lngInput.value = lng;
                            
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                                .then(response => response.json())
                                .then(data => {
                                    const address = data.display_name || `Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                    if (locInput) locInput.value = address;
                                    if (statusText) statusText.innerText = "📍 " + address;
                                })
                                .catch(() => {
                                    if (locInput) locInput.value = `Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                    if (statusText) statusText.innerText = `📍 Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                });
                        }, function() {
                            if (statusText) statusText.innerText = "Location optional (not provided)";
                        }, { timeout: 4000 });
                    }
                });
            }

            const outModalEl = document.getElementById('globalClockOutModal');
            if (outModalEl) {
                outModalEl.addEventListener('show.bs.modal', function () {
                    const statusText = document.getElementById('outLocationStatusText');
                    const latInput = document.getElementById('punch_out_latitude');
                    const lngInput = document.getElementById('punch_out_longitude');
                    const locInput = document.getElementById('punch_out_location');

                    if (navigator.geolocation && statusText) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            if (latInput) latInput.value = lat;
                            if (lngInput) lngInput.value = lng;
                            
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                                .then(response => response.json())
                                .then(data => {
                                    const address = data.display_name || `Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                    if (locInput) locInput.value = address;
                                    if (statusText) statusText.innerText = "📍 " + address;
                                })
                                .catch(() => {
                                    if (locInput) locInput.value = `Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                    if (statusText) statusText.innerText = `📍 Lat: ${lat.toFixed(5)}, Long: ${lng.toFixed(5)}`;
                                });
                        }, function() {
                            if (statusText) statusText.innerText = "Location optional (not provided)";
                        }, { timeout: 4000 });
                    }
                });
            }
        });
    </script>
    <!-- Incoming Call Modal -->
    <div class="modal fade" id="incomingCallModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main shadow-lg text-center p-4" style="background: var(--bg-surface);">
                <div class="mb-3">
                    <div class="avatar-premium mx-auto rounded-circle d-flex align-items-center justify-content-center pulse-animation" 
                        style="width: 80px; height: 80px; font-size: 2.2rem; background: rgba(var(--primary-rgb), 0.15); color: var(--primary);">
                        <i id="incomingCallIcon" class="fas fa-video"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-high mb-1" id="incomingCallTitle">Incoming Call</h4>
                <p class="text-medium mb-1 fs-5" id="incomingCallerName">John Doe</p>
                <p class="text-low small mb-4" id="incomingCallType">📹 Video Call</p>

                <audio id="incomingRingTone" loop src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
                <audio id="outgoingRingTone" loop src="https://assets.mixkit.co/active_storage/sfx/1360/1360-preview.mp3" preload="auto"></audio>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-danger btn-lg px-4 rounded-pill d-flex align-items-center gap-2" id="rejectCallBtn">
                        <i class="fas fa-phone-slash"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success btn-lg px-4 rounded-pill d-flex align-items-center gap-2" id="acceptCallBtn">
                        <i class="fas fa-phone-alt"></i> Accept
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pulse-animation {
            box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0.7);
            animation: pulse-ring 1.5s infinite cubic-bezier(0.66, 0, 0, 1);
        }
        @keyframes pulse-ring {
            to {
                box-shadow: 0 0 0 20px rgba(var(--primary-rgb), 0);
            }
        }
    </style>

    <script>
        let currentCallData = null;
        let ringtoneAudio = null;
        let incomingModal = null;

        let outgoingAudio = null;

        document.addEventListener('DOMContentLoaded', function() {
            ringtoneAudio = document.getElementById('incomingRingTone');
            outgoingAudio = document.getElementById('outgoingRingTone');
            const modalEl = document.getElementById('incomingCallModal');
            if (modalEl) {
                incomingModal = new bootstrap.Modal(modalEl);
            }

            document.getElementById('acceptCallBtn')?.addEventListener('click', acceptIncomingCall);
            document.getElementById('rejectCallBtn')?.addEventListener('click', rejectIncomingCall);
        });

        // Initiate a 1-on-1 Call
        window.initiateDirectCall = function(receiverId, mode, receiverName, conversationId = null) {
            const routeUrl = mode === 'audio' ? "{{ route('admin.calls.audio') }}" : "{{ route('admin.calls.video') }}";
            
            playOutgoingRingtone();

            Swal.fire({
                title: `Calling ${receiverName}...`,
                text: 'Please wait for the user to accept.',
                icon: 'info',
                showCancelButton: true,
                cancelButtonText: 'End Call',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then((result) => {
                stopOutgoingRingtone();
                if (result.dismiss === Swal.DismissReason.cancel && currentCallData) {
                    cancelCall(currentCallData.meeting.uuid);
                }
            });

            fetch(routeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    receiver_id: receiverId,
                    mode: mode
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentCallData = data;
                    if (window.socket) {
                        window.socket.emit('call_initiated', {
                            meeting: data.meeting,
                            receiverId: receiverId,
                            callerId: "{{ auth()->id() }}",
                            callerName: "{{ auth()->user() ? auth()->user()->name : 'User' }}",
                            mode: mode,
                            conversationId: conversationId
                        });
                    }
                } else {
                    stopOutgoingRingtone();
                    Swal.fire('Error', data.message || 'Could not initiate call', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                stopOutgoingRingtone();
                Swal.fire('Error', 'Failed to initiate call.', 'error');
            });
        };

        // Initiate a Group Call
        window.initiateGroupCall = function(conversationId, mode, groupName) {
            playOutgoingRingtone();

            Swal.fire({
                title: `Calling Group (${groupName})...`,
                text: 'Waiting for participants to join...',
                icon: 'info',
                showCancelButton: true,
                cancelButtonText: 'End Group Call',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then((result) => {
                stopOutgoingRingtone();
                if (result.dismiss === Swal.DismissReason.cancel && currentCallData) {
                    cancelCall(currentCallData.meeting.uuid);
                }
            });

            fetch("{{ route('admin.meetings.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    title: `Group ${mode === 'audio' ? 'Audio' : 'Video'} Call - ${groupName}`,
                    type: 'group_call',
                    mode: mode,
                    provider: 'livekit'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.meeting) {
                    const meeting = data.meeting || data;
                    const joinUrl = data.join_url || `/admin/meetings/${meeting.uuid}/join`;
                    currentCallData = { meeting: meeting, join_url: joinUrl };
                    
                    if (window.socket) {
                        window.socket.emit('call_initiated', {
                            meeting: meeting,
                            callerId: "{{ auth()->id() }}",
                            callerName: "{{ auth()->user() ? auth()->user()->name : 'User' }}",
                            mode: mode,
                            conversationId: conversationId,
                            isGroup: true,
                            groupName: groupName
                        });
                    }

                    // Host immediately redirects to join meeting room
                    stopOutgoingRingtone();
                    Swal.close();
                    window.location.href = joinUrl;
                } else {
                    stopOutgoingRingtone();
                    Swal.fire('Error', data.message || 'Could not initiate group call', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                stopOutgoingRingtone();
                Swal.fire('Error', 'Failed to initiate group call.', 'error');
            });
        };

        function acceptIncomingCall() {
            if (!currentCallData) return;
            stopRingtone();
            incomingModal?.hide();
            Swal.close();

            const acceptUrl = "{{ route('admin.meetings.accept', ':uuid') }}".replace(':uuid', currentCallData.meeting.uuid);

            fetch(acceptUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.socket) {
                        window.socket.emit('call_accepted', {
                            meetingUuid: currentCallData.meeting.uuid,
                            callerId: currentCallData.callerId
                        });
                    }
                    window.location.href = data.join_url;
                }
            });
        }

        function rejectIncomingCall() {
            if (!currentCallData) return;
            stopRingtone();
            incomingModal?.hide();
            Swal.close();

            const rejectUrl = "{{ route('admin.meetings.reject', ':uuid') }}".replace(':uuid', currentCallData.meeting.uuid);

            fetch(rejectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (window.socket) {
                window.socket.emit('call_rejected', {
                    meetingUuid: currentCallData.meeting.uuid,
                    callerId: currentCallData.callerId
                });
            }
            currentCallData = null;
        }

        function cancelCall(meetingUuid) {
            stopOutgoingRingtone();
            const cancelUrl = "{{ route('admin.meetings.cancel', ':uuid') }}".replace(':uuid', meetingUuid);

            fetch(cancelUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (window.socket && currentCallData) {
                window.socket.emit('call_cancelled', {
                    meetingUuid: meetingUuid,
                    receiverId: currentCallData.receiverId
                });
            }
            currentCallData = null;
        }

        function playRingtone() {
            if (ringtoneAudio) {
                ringtoneAudio.currentTime = 0;
                ringtoneAudio.play().catch(e => console.log('Ringtone autoplay prevented:', e));
            }
        }

        function stopRingtone() {
            if (ringtoneAudio) {
                ringtoneAudio.pause();
                ringtoneAudio.currentTime = 0;
            }
        }

        function playOutgoingRingtone() {
            if (outgoingAudio) {
                outgoingAudio.currentTime = 0;
                outgoingAudio.play().catch(e => console.log('Outgoing ringtone autoplay prevented:', e));
            }
        }

        function stopOutgoingRingtone() {
            if (outgoingAudio) {
                outgoingAudio.pause();
                outgoingAudio.currentTime = 0;
            }
        }

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register("{{ asset('sw.js') }}")
                    .then(reg => console.log('PWA ServiceWorker registered with scope:', reg.scope))
                    .catch(err => console.warn('PWA ServiceWorker registration failed:', err));
            });
        }
    </script>
</body>

</html>
