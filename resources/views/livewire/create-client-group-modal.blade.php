<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $name = '';
    public $selectedClient = '';
    public $selectedStaff = [];
    public $searchClient = '';
    public $searchStaff = '';
    public $selectAllStaff = false;

    public function searchClientsAPI($query)
    {
        return \App\Models\Client::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(20)
            ->get()
            ->map(function ($client) {
                return [
                    'value' => (string) $client->id,
                    'text' => $client->name . ' (' . $client->email . ')'
                ];
            })
            ->toArray();
    }

    public function with()
    {
        $myId = Auth::id() ?? 0;
        return [
            'staff' => User::where('role_id', '!=', 3)
                ->where('id', '!=', $myId)
                ->when($this->searchStaff, function ($query) {
                    $query->where('name', 'like', '%' . $this->searchStaff . '%');
                })
                ->limit(50)
                ->get()
        ];
    }

    public function updatedSelectAllStaff($value)
    {
        if ($value) {
            $this->selectedStaff = $this->with()['staff']->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedStaff = [];
        }
    }

    public function createClientGroup()
    {
        $this->validate([
            'name' => 'required|min:3',
            'selectedClient' => 'required|exists:clients,id',
            'selectedStaff' => 'array',
        ]);

        $conversation = Conversation::create([
            'type' => 'client_group',
            'name' => $this->name,
        ]);

        // Auth user + Selected Staff
        $participantIds = array_unique(array_merge([Auth::id()], $this->selectedStaff));
        $conversation->participants()->attach($participantIds);

        // Selected Client
        $conversation->clientParticipants()->attach([$this->selectedClient]);

        $this->dispatch('groupCreated', conversationId: $conversation->id);
        $this->dispatch('close-modal', id: 'createClientGroupModal');
        $this->reset(['name', 'selectedClient', 'selectedStaff', 'selectAllStaff']);
    }
}; ?>

<div>
    <x-modal id="createClientGroupModal" title="Create Client Group Chat">
        <form wire:submit.prevent="createClientGroup" id="createClientGroupForm">
            <div class="mb-3">
                <label class="form-label text-main">Group Name</label>
                <input type="text" wire:model="name" class="form-control" placeholder="e.g. Client Support - Acme Corp"
                    required>
            </div>

            <div class="mb-3" wire:ignore>
                <label class="form-label text-main">Select Client <span class="text-danger">*</span></label>
                <select x-data="{
                    instance: null,
                    init() {
                        this.instance = new TomSelect(this.$el, {
                            valueField: 'value',
                            labelField: 'text',
                            searchField: 'text',
                            create: false,
                            plugins: ['clear_button'],
                            dropdownParent: 'body',
                            placeholder: 'Type to search for a client...',
                            load: (query, callback) => {
                                if (!query.length) return callback();
                                $wire.searchClientsAPI(query).then(results => {
                                    callback(results);
                                }).catch(() => {
                                    callback();
                                });
                            },
                            onChange: (val) => {
                                $wire.set('selectedClient', val);
                            }
                        });
                    }
                }" required></select>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label text-main mb-0">Add Staff Members</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model.live="selectAllStaff"
                            id="selectAllStaff">
                        <label class="form-check-label text-main small" for="selectAllStaff">
                            Select All (Visible)
                        </label>
                    </div>
                </div>
                <input type="text" wire:model.live.debounce.300ms="searchStaff" class="form-control form-control-sm mb-2" placeholder="Search staff...">
                <div class="d-flex flex-column gap-2 overflow-auto" style="max-height: 150px;">
                    @foreach ($staff as $user)
                        <label class="d-flex align-items-center gap-2 p-2 rounded cursor-pointer user-item"
                            style="border: 1px solid var(--border-color);">
                            <input type="checkbox" wire:model.live="selectedStaff" value="{{ $user->id }}"
                                class="form-check-input">
                            <span style="color: var(--text-main);">{{ $user->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="createClientGroupForm" class="btn btn-primary">Create Client Group</button>
        </x-slot>
    </x-modal>
</div>
