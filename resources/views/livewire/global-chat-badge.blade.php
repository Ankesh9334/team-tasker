<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('global-message-received')]
    #[On('conversationSelected')]
    #[On('socket-messages-read')]
    public function updateCount()
    {
        if (Auth::check()) {
            $this->count = Auth::user()->unreadChatMessagesCount();
        }
    }
}; ?>

<div>
    @if($count > 0)
        <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;">{{ $count }}</span>
    @endif
</div>
