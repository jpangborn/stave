<?php

use App\Enums\GroupVisibility;
use App\Models\Group;
use Flux\Flux;
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
        Group::findOrFail($id)->delete();
        Flux::modal('delete-group')->close();
        Flux::toast(variant: 'danger', text: 'Group deleted.');
    }
};
?>

<section class="w-full space-y-10">
    <div>
        <flux:heading size="xl" level="1">My Groups</flux:heading>
        <flux:subheading size="lg" class="mb-6">Groups you belong to.</flux:subheading>

        <flux:callout icon="user-group" class="max-w-lg">
            <flux:callout.heading>No groups yet</flux:callout.heading>
            <flux:callout.text>You're not a member of any groups yet.</flux:callout.text>
        </flux:callout>
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
