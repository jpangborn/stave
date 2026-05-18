<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $search = '';
    public string $messagingFilter = 'all';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function setMessagingFilter(string $filter): void
    {
        $allowed = ['all', 'all-members', 'only-leaders', 'off'];
        $this->messagingFilter = in_array($filter, $allowed, true) ? $filter : 'all';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function buildOrderByClause(): string
    {
        $allowedColumns = ['name', 'created_at'];
        $column = in_array($this->sortBy, $allowedColumns, true) ? $this->sortBy : 'name';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return "{$column} {$direction}";
    }

    #[Computed]
    public function myGroups(): Collection
    {
        return Auth::user()->groups()
            ->withCount('members')
            ->with([
                'members' => fn ($q) => $q->limit(4),
                'latestConversation.lastComment.commentator',
            ])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function discover(): LengthAwarePaginator
    {
        return Group::query()
            ->where('visibility', GroupVisibility::PUBLIC)
            ->whereDoesntHave('allUsers', fn ($q) => $q
                ->where('users.id', Auth::id())
                ->whereIn('status', [MembershipStatus::ACTIVE->value, MembershipStatus::PENDING->value])
            )
            ->withCount('members')
            ->when($this->search !== '', fn ($q) =>
                $q->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                )
            )
            ->when($this->messagingFilter !== 'all', fn ($q) =>
                $q->where('messaging', $this->messagingFilter)
            )
            ->orderByRaw($this->buildOrderByClause())
            ->paginate(12);
    }

    #[Computed]
    public function filterCounts(): array
    {
        $base = Group::query()
            ->where('visibility', GroupVisibility::PUBLIC)
            ->whereDoesntHave('allUsers', fn ($q) => $q
                ->where('users.id', Auth::id())
                ->whereIn('status', [MembershipStatus::ACTIVE->value, MembershipStatus::PENDING->value])
            );

        $byMessaging = (clone $base)
            ->selectRaw('messaging, COUNT(*) as aggregate')
            ->groupBy('messaging')
            ->pluck('aggregate', 'messaging');

        return [
            'all' => (int) $byMessaging->sum(),
            'all-members' => (int) ($byMessaging[GroupMessaging::ALL_MEMBERS->value] ?? 0),
            'only-leaders' => (int) ($byMessaging[GroupMessaging::ONLY_LEADERS->value] ?? 0),
            'off' => (int) ($byMessaging[GroupMessaging::OFF->value] ?? 0),
        ];
    }

    public function join(int $groupId): void
    {
        $group = Group::findOrFail($groupId);
        $this->authorize('join', $group);

        $existing = $group->allUsers()->where('user_id', Auth::id())->exists();

        if ($existing) {
            $group->allUsers()->updateExistingPivot(Auth::id(), [
                'status' => MembershipStatus::PENDING,
            ]);
        } else {
            $group->allUsers()->attach(Auth::id(), [
                'role' => GroupRole::MEMBER,
                'status' => MembershipStatus::PENDING,
            ]);
        }

        unset($this->myGroups, $this->discover, $this->filterCounts);
        Flux::toast(text: 'Join request sent.');
    }

    public function delete(int $id): void
    {
        $group = Group::findOrFail($id);
        $this->authorize('delete', $group);
        $group->delete();
        Flux::modal('delete-group')->close();
        Flux::toast(variant: 'danger', text: 'Group deleted.');
    }
};
?>

<section class="w-full space-y-10">
    <div>
        <flux:heading size="xl" level="1">My Groups</flux:heading>
        <flux:subheading size="lg" class="mb-6">Groups you belong to.</flux:subheading>

        @if ($this->myGroups->isEmpty())
            <flux:callout icon="user-group" class="max-w-lg">
                <flux:callout.heading>No groups yet</flux:callout.heading>
                <flux:callout.text>You're not a member of any groups yet.</flux:callout.text>
            </flux:callout>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                @foreach ($this->myGroups as $group)
                    <x-groups.card :group="$group"/>
                @endforeach
            </div>
        @endif
    </div>

    <div x-data="{
        layout: localStorage.getItem('groups-discover-layout') || 'table',
        setLayout(value) {
            this.layout = value;
            localStorage.setItem('groups-discover-layout', value);
        }
    }">
        <flux:heading size="xl" level="2">Discover Groups</flux:heading>
        <flux:subheading size="lg" class="mb-6">Find and join public groups.</flux:subheading>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <flux:input
                wire:model.live.debounce.250ms="search"
                size="sm"
                placeholder="Search groups..."
                icon="magnifying-glass"
                class="max-w-96"
                clearable
            />

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter by messaging">
                @php
                    $chips = [
                        'all' => ['label' => 'All', 'count' => $this->filterCounts['all']],
                        'all-members' => ['label' => 'All Members', 'count' => $this->filterCounts['all-members']],
                        'only-leaders' => ['label' => 'Only Leaders', 'count' => $this->filterCounts['only-leaders']],
                        'off' => ['label' => 'Off', 'count' => $this->filterCounts['off']],
                    ];
                @endphp
                @foreach ($chips as $value => $chip)
                    @php $active = $messagingFilter === $value; @endphp
                    <button
                        type="button"
                        wire:click="setMessagingFilter('{{ $value }}')"
                        @class([
                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition cursor-pointer',
                            'bg-accent/10 text-accent border-accent/30' => $active,
                            'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800' => ! $active,
                        ])
                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                    >
                        <span>{{ $chip['label'] }}</span>
                        <span @class([
                            'text-[10px] font-semibold',
                            'text-accent/70' => $active,
                            'text-zinc-400' => ! $active,
                        ])>{{ $chip['count'] }}</span>
                    </button>
                @endforeach
            </div>

            <flux:spacer/>

            <div class="flex items-center gap-2">
                <div class="inline-flex items-center gap-0.5 rounded-lg border border-zinc-200 dark:border-zinc-700 p-0.5" role="group" aria-label="Discover layout">
                    <button
                        type="button"
                        @click="setLayout('table')"
                        :class="layout === 'table'
                            ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100'
                            : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold transition cursor-pointer"
                        :aria-pressed="layout === 'table'"
                        aria-label="Table layout"
                    >
                        <flux:icon.list-bullet variant="micro" class="size-3.5"/>
                        <span>Table</span>
                    </button>
                    <button
                        type="button"
                        @click="setLayout('cards')"
                        :class="layout === 'cards'
                            ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100'
                            : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold transition cursor-pointer"
                        :aria-pressed="layout === 'cards'"
                        aria-label="Cards layout"
                    >
                        <flux:icon.squares-2x2 variant="micro" class="size-3.5"/>
                        <span>Cards</span>
                    </button>
                </div>

                <flux:button :href="route('groups.create')" size="sm" variant="primary" icon="plus">Add Group</flux:button>
            </div>
        </div>

        <div class="mt-4">
            @if ($this->discover->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 px-4 py-10 text-center">
                    <p class="text-sm text-zinc-500">
                        @if ($search !== '' || $messagingFilter !== 'all')
                            No groups match that search.
                        @else
                            No public groups available right now.
                        @endif
                    </p>
                </div>
            @else
                <div x-show="layout === 'table'" x-cloak>
                    <flux:table :paginate="$this->discover">
                        <flux:table.columns>
                            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
                            <flux:table.column>Visibility</flux:table.column>
                            <flux:table.column>Messaging</flux:table.column>
                            <flux:table.column align="end">Members</flux:table.column>
                            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->discover as $group)
                                <livewire:groups.row :$group :key="'row-'.$group->id"/>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div x-show="layout === 'cards'" x-cloak>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @foreach ($this->discover as $group)
                            @include('livewire.groups.discover-card', ['group' => $group])
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $this->discover->links() }}
                    </div>
                </div>

                <p class="mt-3.5 text-xs text-zinc-500">
                    Showing {{ $this->discover->firstItem() }} to {{ $this->discover->lastItem() }} of {{ $this->discover->total() }} results
                </p>
            @endif
        </div>
    </div>
</section>
