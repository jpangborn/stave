<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use App\Notifications\GroupJoinRequestNotification;
use App\Notifications\GroupMemberAddedNotification;
use App\Notifications\GroupMembershipResponseNotification;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public Group $group;

    #[Url]
    public string $tab = 'details';

    public string $memberSearch = '';
    public ?int $selectedUserId = null;

    public function mount(Group $group): void
    {
        $this->authorize('view', $group);
        $this->group = $group;
    }

    #[Computed]
    public function membership(): ?GroupUser
    {
        /** @var ?GroupUser */
        return $this->group->allUsers()->where('user_id', Auth::id())->first()?->pivot;
    }

    #[Computed]
    public function isLeader(): bool
    {
        return $this->group->leaders()->where('user_id', Auth::id())->exists();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function pendingRequests(): Collection
    {
        return $this->group->pendingRequests()->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function activeMembers(): Collection
    {
        return $this->group->members()->get();
    }

    /** @return Collection<int, User>|BaseCollection<int, User> */
    #[Computed]
    public function availableUsers(): Collection|BaseCollection
    {
        if (strlen($this->memberSearch) < 2) {
            return collect();
        }

        $existingUserIds = $this->group->allUsers()
            ->whereIn('group_user.status', [MembershipStatus::ACTIVE, MembershipStatus::PENDING])
            ->pluck('users.id');

        return User::query()
            ->where('name', 'like', "%{$this->memberSearch}%")
            ->whereNotIn('id', $existingUserIds)
            ->limit(20)
            ->get();
    }

    public function join(): void
    {
        $this->authorize('join', $this->group);

        $existing = $this->group->allUsers()->where('user_id', Auth::id())->exists();

        if ($existing) {
            $this->group->allUsers()->updateExistingPivot(Auth::id(), [
                'status' => MembershipStatus::PENDING,
            ]);
        } else {
            $this->group->allUsers()->attach(Auth::id(), [
                'role' => GroupRole::MEMBER,
                'status' => MembershipStatus::PENDING,
            ]);
        }

        $this->notifyLeaders();

        unset($this->membership);
        Flux::toast(text: 'Join request sent.');
    }

    public function cancelRequest(): void
    {
        /** @var ?GroupUser $membership */
        $membership = $this->group->allUsers()->where('user_id', Auth::id())->first()?->pivot;

        if ($membership?->status !== MembershipStatus::PENDING) {
            return;
        }

        $this->group->allUsers()->detach(Auth::id());

        unset($this->membership);
        Flux::toast(text: 'Join request cancelled.');
    }

    public function leave(): void
    {
        $this->authorize('leave', $this->group);

        $this->group->allUsers()->detach(Auth::id());

        unset($this->membership, $this->isLeader, $this->activeMembers);
        Flux::toast(text: 'You left the group.');
    }

    public function approveMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        $user = $this->group->pendingRequests()->whereKey($userId)->firstOrFail();

        $this->group->allUsers()->updateExistingPivot($user->id, [
            'status' => MembershipStatus::ACTIVE,
        ]);

        $user->notify(new GroupMembershipResponseNotification($this->group, approved: true));

        unset($this->pendingRequests, $this->activeMembers);
        Flux::toast(text: "{$user->name} approved.");
    }

    public function rejectMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        $user = $this->group->pendingRequests()->whereKey($userId)->firstOrFail();

        $this->group->allUsers()->updateExistingPivot($user->id, [
            'status' => MembershipStatus::REJECTED,
        ]);

        $user->notify(new GroupMembershipResponseNotification($this->group, approved: false));

        unset($this->pendingRequests);
        Flux::toast(text: "{$user->name} rejected.");
    }

    public function addMember(): void
    {
        $this->authorize('manageMembers', $this->group);

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::findOrFail($this->selectedUserId);

        if ($this->group->members()->where('user_id', $user->id)->exists()) {
            Flux::toast(variant: 'warning', text: "{$user->name} is already a member.");
            $this->reset('selectedUserId', 'memberSearch');

            return;
        }

        $existing = $this->group->allUsers()->where('user_id', $user->id)->exists();

        if ($existing) {
            $this->group->allUsers()->updateExistingPivot($user->id, [
                'status' => MembershipStatus::ACTIVE,
                'role' => GroupRole::MEMBER,
            ]);
        } else {
            $this->group->allUsers()->attach($user->id, [
                'role' => GroupRole::MEMBER,
                'status' => MembershipStatus::ACTIVE,
            ]);
        }

        $user->notify(new GroupMemberAddedNotification($this->group, Auth::user()));

        $this->reset('selectedUserId', 'memberSearch');
        unset($this->activeMembers, $this->pendingRequests, $this->availableUsers);
        Flux::toast(text: "{$user->name} added to group.");
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('manageMembers', $this->group);

        $user = User::findOrFail($userId);

        if ($this->group->leaders()->where('user_id', $userId)->exists()
            && $this->group->leaders()->count() === 1) {
            Flux::toast(variant: 'danger', text: 'Cannot remove the only leader.');

            return;
        }

        $this->group->allUsers()->detach($userId);

        unset($this->activeMembers);
        Flux::toast(text: "{$user->name} removed from group.");
    }

    private function notifyLeaders(): void
    {
        $notification = new GroupJoinRequestNotification($this->group, Auth::user());

        $this->group->leaders->each(fn (User $leader) => $leader->notify($notification));
    }
};
?>

<section class="w-full">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $group->name }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">
                <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
                <flux:badge size="sm" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
                <span class="ml-2">{{ $this->activeMembers->count() }} {{ str('member')->plural($this->activeMembers->count()) }}</span>
            </flux:subheading>
        </div>

        <div>
            @if ($this->membership === null && $group->visibility === GroupVisibility::PUBLIC)
                <flux:button wire:click="join" variant="primary" icon="user-plus">Join Group</flux:button>
            @elseif ($this->membership?->status === MembershipStatus::PENDING)
                <div class="flex items-center gap-2">
                    <flux:badge color="amber">Request Pending</flux:badge>
                    <flux:button wire:click="cancelRequest" variant="ghost" size="sm">Cancel</flux:button>
                </div>
            @elseif ($this->membership?->status === MembershipStatus::ACTIVE && ! $this->isLeader)
                <flux:button wire:click="leave" variant="danger" icon="arrow-right-start-on-rectangle"
                    wire:confirm="Are you sure you want to leave this group?">Leave Group</flux:button>
            @elseif ($this->membership?->status === MembershipStatus::REJECTED)
                <flux:button wire:click="join" variant="primary" icon="user-plus">Request to Join Again</flux:button>
            @endif
        </div>
    </div>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="information-circle">Details</flux:tab>
            @if ($this->isLeader)
                <flux:tab name="members" icon="user-group">
                    Members
                    @if ($this->pendingRequests->isNotEmpty())
                        <flux:badge size="sm" color="amber" class="ml-1">{{ $this->pendingRequests->count() }}</flux:badge>
                    @endif
                </flux:tab>
            @endif
        </flux:tabs>

        <flux:tab.panel name="details">
            <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Group Details</flux:heading>
                    <flux:subheading>Information about this group.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input type="text" :value="$group->name" variant="filled" readonly />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea :value="$group->description" variant="filled" readonly rows="3" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Visibility</flux:label>
                        <flux:input type="text" :value="$group->visibility->label()" variant="filled" readonly />
                    </flux:field>

                    <flux:field>
                        <flux:label>Messaging</flux:label>
                        <flux:input type="text" :value="$group->messaging->label()" variant="filled" readonly />
                    </flux:field>
                </div>
            </div>
        </flux:tab.panel>

        @if ($this->isLeader)
            <flux:tab.panel name="members">
                {{-- Pending Requests --}}
                @if ($this->pendingRequests->isNotEmpty())
                    <div class="mb-8">
                        <flux:heading size="lg" class="mb-3">Pending Requests</flux:heading>

                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Name</flux:table.column>
                                <flux:table.column>Requested</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->pendingRequests as $user)
                                    <flux:table.row :key="$user->id">
                                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                                        <flux:table.cell class="whitespace-nowrap">{{ $user->pivot->created_at->diffForHumans() }}</flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex gap-2 justify-end">
                                                <flux:button wire:click="approveMember({{ $user->id }})" variant="primary" size="sm" icon="check">Approve</flux:button>
                                                <flux:button wire:click="rejectMember({{ $user->id }})" variant="danger" size="sm" icon="x-mark">Reject</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif

                {{-- Active Members --}}
                <div class="mb-8">
                    <flux:heading size="lg" class="mb-3">Members</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column>Role</flux:table.column>
                            <flux:table.column>Joined</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->activeMembers as $user)
                                <flux:table.row :key="$user->id">
                                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" inset="top bottom" :color="$user->pivot->role->color()">
                                            {{ $user->pivot->role->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $user->pivot->created_at->diffForHumans() }}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        @if ($user->id !== Auth::id())
                                            <flux:button wire:click="removeMember({{ $user->id }})" variant="ghost" size="sm" icon="trash"
                                                wire:confirm="Remove {{ $user->name }} from this group?" />
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Add Member --}}
                <div>
                    <flux:heading size="lg" class="mb-3">Add Member</flux:heading>

                    <form wire:submit="addMember" class="flex items-end gap-3 max-w-md">
                        <div class="flex-1">
                            <flux:select wire:model="selectedUserId" variant="combobox" :filter="false" placeholder="Search users..." clearable>
                                <x-slot name="input">
                                    <flux:select.input wire:model.live.debounce.300ms="memberSearch" placeholder="Search by name..." />
                                </x-slot>

                                @foreach ($this->availableUsers as $user)
                                    <flux:select.option :value="$user->id" wire:key="add-{{ $user->id }}">{{ $user->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:button type="submit" variant="primary" icon="user-plus">Add</flux:button>
                    </form>
                </div>
            </flux:tab.panel>
        @endif
    </flux:tab.group>
</section>
