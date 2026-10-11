<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $name = '';
    public $selectedUsers = [];
    public $selectAll = false;
    public $searchUser = '';

    public function with()
    {
        $myId = Auth::id() ?? 0;
        return [
            'users' => User::where('id', '!=', $myId)
                ->when($this->searchUser, function ($query) {
                    $query->where('name', 'like', '%' . $this->searchUser . '%');
                })
                ->limit(50)
                ->get()
        ];
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = $this->with()['users']->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function updatedSelectedUsers()
    {
        $this->selectAll = count($this->selectedUsers) === count($this->with()['users']);
    }

    public function createGroup()
    {
        $this->validate([
            'name' => 'required|min:3',
            'selectedUsers' => 'required|array|min:1',
        ]);

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $this->name,
        ]);

        $participantIds = array_merge([Auth::id()], $this->selectedUsers);
        $conversation->participants()->attach($participantIds);

        $this->dispatch('groupCreated', conversationId: $conversation->id);
        $this->dispatch('close-modal', id: 'createGroupModal');
        $this->reset(['name', 'selectedUsers', 'selectAll']);
    }
}; ?>

<div>
    <x-modal id="createGroupModal" title="Create Group Chat">
        <form wire:submit.prevent="createGroup" id="createGroupForm">
            <div class="mb-3">
                <label class="form-label text-main">Group Name</label>
                <input type="text" wire:model="name" class="form-control" placeholder="e.g. Project Alpha Team" required>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label text-main mb-0">Select Members</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAllUsers">
                        <label class="form-check-label text-main small" for="selectAllUsers">
                            Select All (Visible)
                        </label>
                    </div>
                </div>
                <input type="text" wire:model.live.debounce.300ms="searchUser" class="form-control form-control-sm mb-2" placeholder="Search users...">
                <div class="d-flex flex-column gap-2 overflow-auto" style="max-height: 200px;">
                    @foreach ($users as $user)
                        <label class="d-flex align-items-center gap-2 p-2 rounded cursor-pointer user-item"
                            style="border: 1px solid var(--border-color);">
                            <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user->id }}"
                                class="form-check-input">
                            <span style="color: var(--text-main);">{{ $user->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="createGroupForm" class="btn btn-primary">Create Group</button>
        </x-slot>
    </x-modal>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('close-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const modalEl = document.getElementById(data.id);
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                    }
                }
            });
        });
    </script>
</div>
