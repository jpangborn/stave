<?php

use App\Models\Person;
use Flux\Flux;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public $sortBy = "last_name";
    public $sortDirection = "asc";
    public $search = "";

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === "asc" ? "desc" : "asc";
        } else {
            $this->sortBy = $column;
            $this->sortDirection = "asc";
        }
    }

    #[Computed]
    public function people()
    {
        return Person::query()
            ->when($this->search, function ($query): void {
                $query->whereAny(
                    ["first_name", "last_name", "email"],
                    "like",
                    "%{$this->search}%",
                );
            })
            ->tap(
                fn($query) => $this->sortBy
                    ? $query->orderBy($this->sortBy, $this->sortDirection)
                    : $query,
            )
            ->paginate(12);
    }

    public function delete($id)
    {
        Person::findOrFail($id)->delete();
        Flux::modal("delete-person")->close();
        Flux::toast(variant: "danger", text: "Person deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">People</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your members and visitors.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.deep.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:spacer/>
        <flux:button :href="route('people.create')" size="sm" variant="primary" icon="plus">Add Person</flux:button>
    </div>

    <flux:table :paginate="$this->people" class="mt-2">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'last_name'" :direction="$sortDirection" wire:click="sort('last_name')">Person</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'gender'" :direction="$sortDirection" wire:click="sort('gender')">Gender</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Added</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->people as $person)
                <livewire:people.row :$person :key="$person->id" />
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
