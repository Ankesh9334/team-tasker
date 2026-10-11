<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public $conversationId;
    public $name = '';
    public $selectedUsers = [];
    public $searchUser = '';

    public function with()
    {
        $selected = is_array($this->selectedUsers) ? $this->selectedUsers : [];
        if (empty($selected)) {
            $selected = [-1]; // Prevent empty IN clause
        }
        $selectedStr = implode(',', $selected);

        return [
            'users' => User::where('id', '!=', Auth::id())
                ->when($this->searchUser, function ($query) use ($selected) {
                    $query->where(function ($q) use ($selected) {
                        $q->where('name', 'like', '%' . $this->searchUser . '%')
                          ->orWhereIn('id', $selected);
                    });
                })
                ->orderByRaw("id IN ($selectedStr) DESC")
                ->limit(50)
                ->get()
        ];
    }

    #[On('openEditGroupModal')]
    public function loadGroup($conversationId)
    {
        $conversation = Conversation::with('participants')->find($conversationId);
        if ($conversation && $conversation->type === 'group') {
            $this->conversationId = $conversation->id;
            $this->name = $conversation->name;
            
            $this->selectedUsers = $conversation->participants
                ->pluck('id')
                ->reject(fn($id) => $id == Auth::id())
                ->map(fn($id) => (string)$id)
                ->values()
                ->toArray();

            $this->dispatch('open-modal', id: 'editGroupModal');
        }
    }

    public function updateGroup()
    {
        $this->validate([
            'name' => 'required|min:3',
            'selectedUsers' => 'required|array|min:1', // At least one other person
        ]);

        $conversation = Conversation::find($this->conversationId);

        if ($conversation && $conversation->type === 'group') {
            $conversation->update(['name' => $this->name]);

            // Sync participants: Auth user + selected users
            $participantIds = array_merge([Auth::id()], $this->selectedUsers);
            $conversation->participants()->sync($participantIds);

            $this->dispatch('groupUpdated', conversationId: $conversation->id);
            $this->dispatch('close-modal', id: 'editGroupModal');
        }
    }
}; ?>

<div>
    <x-modal id="editGroupModal" title="Edit Group Chat">
        <form wire:submit.prevent="updateGroup">
            <div class="mb-3">
                <label class="form-label text-main">Group Name</label>
                <input type="text" wire:model="name" class="form-control" placeholder="Group Name" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-main">Members</label>
                <input type="text" wire:model.live.debounce.300ms="searchUser" class="form-control form-control-sm mb-2" placeholder="Search members...">
                <div class="d-flex flex-column gap-2 overflow-auto" style="max-height: 200px;">
                    @foreach ($users as $user)
                        <label class="d-flex align-items-center gap-2 p-2 rounded cursor-pointer user-item"
                            style="border: 1px solid var(--border-color);">
                            <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}"
                                class="form-check-input">
                            <span style="color: var(--text-main);">{{ $user->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const modalEl = document.getElementById(data.id);
                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
            Livewire.on('close-modal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const modalEl = document.getElementById(data.id);
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                    }
                    if (!modal) {
                        const bsModal = new bootstrap.Modal(modalEl);
                        bsModal.hide();
                    }
                }
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            });
        });
    </script>
</div>
