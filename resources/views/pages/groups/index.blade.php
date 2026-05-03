<?php

use App\Enums\GroupVisibility;
use App\Models\Group;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $search = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
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
        return Auth::user()->groups;
    }

    #[Computed]
    public function groups()
    {
        return Group::query()
            ->where('visibility', GroupVisibility::PUBLIC)
            ->when($this->search, function ($query): void {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderByRaw($this->buildOrderByClause())
            ->paginate(12);
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->myGroups as $group)
                    <a href="{{ route('groups.show', $group) }}" wire:navigate class="group block">
                        <flux:card class="space-y-2 h-full transition group-hover:border-zinc-300 dark:group-hover:border-white/20">
                            <flux:heading size="lg">{{ $group->name }}</flux:heading>
                            <div class="flex gap-2">
                                <flux:badge size="sm" :color="$group->visibility->color()">{{ $group->visibility->label() }}</flux:badge>
                                <flux:badge size="sm" :color="$group->messaging->color()">{{ $group->messaging->label() }}</flux:badge>
                            </div>
                        </flux:card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <flux:heading size="xl" level="2">Discover Groups</flux:heading>
        <flux:subheading size="lg" class="mb-6">Find and join public groups.</flux:subheading>

        <div class="flex space-x-4 items-center">
            <flux:input wire:model.live="search" size="sm" placeholder="Search groups..." icon="magnifying-glass" class="max-w-96" clearable/>
            <flux:spacer/>
            <flux:button :href="route('groups.create')" size="sm" variant="primary" icon="plus">Add Group</flux:button>
        </div>

        <flux:table :paginate="$this->groups" class="mt-2">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
                <flux:table.column>Visibility</flux:table.column>
                <flux:table.column>Messaging</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->groups as $group)
                    <livewire:groups.row :$group :key="$group->id"/>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</section>
