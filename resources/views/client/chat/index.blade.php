<x-client title="Chat" hideSidebar="true" noPadding="true">
    <style>
        body {
            overflow: hidden !important;
        }
        .main-content-premium {
            height: calc(100vh - 70px) !important;
            height: calc(100dvh - 70px) !important;
            min-height: auto !important;
            overflow: hidden !important;
        }
        #chat-container > div {
            height: 100% !important;
        }
        @media (max-width: 991.98px) {
            .main-content-premium {
                height: calc(100vh - 80px) !important;
                height: calc(100dvh - 80px) !important;
            }
            #chat-container {
                height: calc(100vh - 80px) !important;
                height: calc(100dvh - 80px) !important;
            }
        }
    </style>
    <div id="chat-container" class="w-100 overflow-hidden m-0 p-0" style="height: calc(100vh - 70px) !important; height: calc(100dvh - 70px) !important;">
        <livewire:chat />
    </div>
</x-client>
