<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $selectedConversationId = null;

    public function mount()
    {
        $this->selectedConversationId = request('conversation_id') ?? request('conversation');
    }

    #[On('conversationSelected')]
    public function handleConversationSelected($conversationId)
    {
        \Illuminate\Support\Facades\Log::info('handleConversationSelected', ['id' => $conversationId]);
        $this->selectedConversationId = $conversationId;
    }

    #[On('backToUserList')]
    public function handleBackToUserList()
    {
        $this->selectedConversationId = null;
    }
}; ?>


    <div class="row g-0 m-0 w-100 h-100 overflow-hidden rounded shadow-sm" style="border: 1px solid var(--border-color);">
        <div class="col-12 col-md-4 col-lg-3 border-end h-100 d-flex flex-column {{ $selectedConversationId ? 'd-none d-md-flex' : '' }}"
            style="border-color: var(--border-color) !important;">
            <livewire:chat-list :selectedConversationId="$selectedConversationId" wire:key="chat-list-sidebar" />
        </div>
        <div
            class="col-12 col-md-8 col-lg-9 h-100 d-flex flex-column {{ $selectedConversationId ? '' : 'd-none d-md-flex' }}">
            <livewire:chat-box :selectedConversationId="$selectedConversationId" wire:key="chat-box-details-{{ $selectedConversationId ?? 'none' }}" />
        </div>
    </div>
