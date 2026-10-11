<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $selectedUser = null;
    public $staffAccessType = 'all'; // 'all' or 'particular'
    public $clientAccessType = 'all'; // 'all' or 'particular'
    public $allowedUsers = [];
    public $allowedClients = [];
    public $searchUser = '';
    public $searchContact = '';
    public $successMessage = '';

    public $userLetter = '';
    public $contactLetter = '';

    public $userLimit = 10;
    public $contactLimit = 20;

    public function updatedSearchUser()
    {
        $this->userLimit = 10;
    }

    public function updatedUserLetter()
    {
        $this->userLimit = 10;
    }

    public function updatedSearchContact()
    {
        $this->contactLimit = 20;
    }

    public function updatedContactLetter()
    {
        $this->contactLimit = 20;
    }

    public function loadMoreUsers()
    {
        $this->userLimit += 10;
    }

    public function loadMoreContacts()
    {
        $this->contactLimit += 20;
    }

    public function with()
    {
        $usersQuery = User::where('name', 'like', '%' . $this->searchUser . '%');
        if ($this->userLetter) {
            $usersQuery->where('name', 'like', $this->userLetter . '%');
        }

        $contactsQuery = User::where('name', 'like', '%' . $this->searchContact . '%');
        if ($this->contactLetter) {
            $contactsQuery->where('name', 'like', $this->contactLetter . '%');
        }

        $clientsQuery = \App\Models\Client::where('name', 'like', '%' . $this->searchContact . '%');
        if ($this->contactLetter) {
            $clientsQuery->where('name', 'like', $this->contactLetter . '%');
        }

        return [
            'users' => (clone $usersQuery)->orderBy('name')->limit($this->userLimit)->get(),
            'totalUsers' => $usersQuery->count(),
            'contacts' => (clone $contactsQuery)->orderBy('name')->limit($this->contactLimit)->get(),
            'totalContacts' => $contactsQuery->count(),
            'clients' => (clone $clientsQuery)->orderBy('name')->limit($this->contactLimit)->get(),
            'roles' => \App\Models\Role::all()
        ];
    }

    public function selectUser($userId)
    {
        $this->selectedUser = $userId;
        $this->successMessage = ''; // Clear message on user change

        $permissions = DB::table('chat_user_permissions')->where('user_id', $userId)->get();

        $userPermissions = $permissions->whereNotNull('allowed_user_id');
        $clientPermissions = $permissions->whereNotNull('allowed_client_id');

        $this->allowedUsers = $userPermissions
            ->pluck('allowed_user_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $this->allowedClients = $clientPermissions
            ->pluck('allowed_client_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // If no user_id entries exist in permissions table, access type is 'all'
        $this->staffAccessType = $userPermissions->isEmpty() ? 'all' : 'particular';
        // If no client_id entries exist in permissions table, access type is 'all'
        $this->clientAccessType = $clientPermissions->isEmpty() ? 'all' : 'particular';
    }

    public function updatePermissions()
    {
        if (!$this->selectedUser)
            return;

        DB::table('chat_user_permissions')->where('user_id', $this->selectedUser)->delete();

        $data = [];

        if ($this->staffAccessType === 'particular') {
            foreach ($this->allowedUsers as $allowedId) {
                $data[] = [
                    'user_id' => $this->selectedUser,
                    'allowed_user_id' => $allowedId,
                    'allowed_client_id' => null,
                    'client_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($this->clientAccessType === 'particular') {
            foreach ($this->allowedClients as $allowedId) {
                $data[] = [
                    'user_id' => $this->selectedUser,
                    'allowed_user_id' => null,
                    'allowed_client_id' => $allowedId,
                    'client_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (count($data) > 0) {
            DB::table('chat_user_permissions')->insert($data);
        }

        $this->successMessage = "Chat permissions saved successfully!";
    }
};
?>

<div>
    @if($successMessage)
        <div x-data="{ show: true }" x-show="show"
            x-init="setTimeout(() => { show = false; $wire.set('successMessage', '') }, 3000)"
            class="alert alert-success d-flex align-items-center mb-4" role="alert"
            style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 8px;">
            <i class="fas fa-check-circle me-2 fs-5"></i>
            <div>{{ $successMessage }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card d-flex flex-column h-100" style="border: 1px solid var(--border-main);">
                <h5 class="fw-bold mb-3" style="color: var(--text-high);">Select User</h5>
                <div class="mb-3 d-flex gap-2">
                    <input type="text" wire:model.live.debounce.300ms="searchUser" class="form-premium-control w-100"
                        placeholder="Search user..."
                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px;">
                    <select wire:model.live="userLetter" class="form-select form-select-sm"
                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px; width: 80px;">
                        <option value="">A-Z</option>
                        @foreach(range('A', 'Z') as $letter)
                            <option value="{{ $letter }}">{{ $letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="list-group list-group-flush rounded border border-main flex-grow-1 overflow-auto mb-3"
                    style="max-height: 500px;">
                    @foreach($users as $u)
                        <button wire:click="selectUser({{ $u->id }})"
                            class="list-group-item list-group-item-action bg-transparent border-bottom border-main py-3 {{ $selectedUser == $u->id ? 'active text-primary fw-bold' : 'text-high' }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-premium"
                                    style="width: 32px; height: 32px; font-size: 0.8rem; background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div class="text-start">
                                    <div class="mb-0">{{ $u->name }}</div>
                                    <div class="text-low small fw-normal">{{ $u->email }}</div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
                @if ($users->count() < $totalUsers)
                    <div class="text-center py-2">
                        <button wire:click="loadMoreUsers" class="btn btn-sm btn-link text-decoration-none"
                            style="color: var(--primary);">
                            Load More Users
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card h-100" style="border: 1px solid var(--border-main);">
                @if($selectedUser)
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0" style="color: var(--text-high);">Allowed Contacts</h5>
                        <button wire:click="updatePermissions" class="btn-premium btn-premium-primary px-4 py-2">
                            Save Permissions
                        </button>
                    </div>

                    <!-- Staff Members Access Section -->
                    <div class="card p-3 mb-4"
                        style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-subtle); border-radius: 10px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0 text-high text-uppercase"
                                style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                <i class="fas fa-users text-primary me-2"></i>Staff Members Access
                            </h6>
                            <select wire:model.live="staffAccessType" class="form-select form-select-sm w-auto"
                                style="background: rgba(255,255,255,0.08); color: var(--text-main); border: 1px solid var(--border-subtle); border-radius: 6px; font-weight: 500;">
                                <option value="all">All Staff Members (All Access)</option>
                                <option value="particular">Particular Staff Members</option>
                            </select>
                        </div>

                        @if($staffAccessType === 'all')
                            <div class="p-3 rounded mt-2 d-flex align-items-center gap-2"
                                style="background: rgba(16, 185, 129, 0.08); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);">
                                <i class="fas fa-check-circle"></i>
                                <span style="font-size: 0.85rem;">This user can see and chat with <strong>ALL Staff
                                        Members</strong>.</span>
                            </div>
                        @else
                            <div class="mt-3">
                                <div class="mb-3 d-flex gap-2">
                                    <input type="text" wire:model.live.debounce.300ms="searchContact"
                                        class="form-premium-control w-100" placeholder="Search staff members to allow..."
                                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px;">
                                    <select wire:model.live="contactLetter" class="form-select form-select-sm"
                                        style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-subtle); padding: 8px 12px; border-radius: 6px; width: 80px;">
                                        <option value="">A-Z</option>
                                        @foreach(range('A', 'Z') as $letter)
                                            <option value="{{ $letter }}">{{ $letter }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-3">
                                    @foreach($contacts as $contact)
                                        @if($contact->id != $selectedUser)
                                            <div class="col-md-4">
                                                <div class="form-check custom-checkbox p-3 rounded"
                                                    style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle);">
                                                    <input class="form-check-input ms-0 me-3 mt-2" type="checkbox"
                                                        wire:model.live="allowedUsers" value="{{ $contact->id }}"
                                                        id="contact-{{ $contact->id }}" style="transform: scale(1.2);">
                                                    <label class="form-check-label w-100" for="contact-{{ $contact->id }}">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="avatar-premium"
                                                                style="width: 28px; height: 28px; font-size: 0.7rem; background: var(--bg-input); color: var(--text-high);">
                                                                {{ substr($contact->name, 0, 1) }}
                                                            </div>
                                                            <div>
                                                                <div class="text-high fw-medium" style="font-size: 0.85rem;">
                                                                    {{ $contact->name }}</div>
                                                                <div class="text-low" style="font-size: 0.75rem;">
                                                                    {{ $contact->role->name ?? 'User' }}</div>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Clients Access Section -->
                    <div class="card p-3 mb-4"
                        style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-subtle); border-radius: 10px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0 text-high text-uppercase"
                                style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                <i class="fas fa-user-tie text-info me-2"></i>Clients Access
                            </h6>
                            <select wire:model.live="clientAccessType" class="form-select form-select-sm w-auto"
                                style="background: rgba(255,255,255,0.08); color: var(--text-main); border: 1px solid var(--border-subtle); border-radius: 6px; font-weight: 500;">
                                <option value="all">All Clients (All Access)</option>
                                <option value="particular">Particular Clients</option>
                            </select>
                        </div>

                        @if($clientAccessType === 'all')
                            <div class="p-3 rounded mt-2 d-flex align-items-center gap-2"
                                style="background: rgba(59, 130, 246, 0.08); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);">
                                <i class="fas fa-check-circle"></i>
                                <span style="font-size: 0.85rem;">This user can see and chat with <strong>ALL
                                        Clients</strong>.</span>
                            </div>
                        @else
                            <div class="mt-3">
                                <div class="row g-3">
                                    @foreach($clients as $client)
                                        <div class="col-md-4">
                                            <div class="form-check custom-checkbox p-3 rounded"
                                                style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle);">
                                                <input class="form-check-input ms-0 me-3 mt-2" type="checkbox"
                                                    wire:model.live="allowedClients" value="{{ $client->id }}"
                                                    id="client-{{ $client->id }}" style="transform: scale(1.2);">
                                                <label class="form-check-label w-100" for="client-{{ $client->id }}">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar-premium"
                                                            style="width: 28px; height: 28px; font-size: 0.7rem; background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                                                            {{ substr($client->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <div class="text-high fw-medium" style="font-size: 0.85rem;">
                                                                {{ $client->name }}</div>
                                                            <div class="text-low" style="font-size: 0.75rem;">Client</div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    @if (($staffAccessType === 'particular' || $clientAccessType === 'particular') && $contacts->count() < $totalContacts)
                        <div class="text-center py-2">
                            <button wire:click="loadMoreContacts" class="btn btn-sm btn-link text-decoration-none"
                                style="color: var(--primary);">
                                Load More Contacts
                            </button>
                        </div>
                    @endif
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-low">
                        <i class="fas fa-user-shield fa-4x mb-3 text-muted" style="opacity: 0.3;"></i>
                        <h5>Select a User</h5>
                        <p>Choose a user from the left to configure their chat permissions.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .custom-checkbox input:checked+label .text-high {
            color: var(--primary) !important;
        }

        .custom-checkbox input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</div>