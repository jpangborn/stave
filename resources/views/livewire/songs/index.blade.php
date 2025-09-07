<?php

use Flux\Flux;
use App\Models\Song;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public $sortBy = "name";
    public $sortDirection = "asc";
    public $search = "";

    public function sort($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === "asc" ? "desc" : "asc";
        } else {
            $this->sortBy = $column;
            $this->sortDirection = "asc";
        }
    }

    private function buildOrderByClause(): string
    {
        $allowedColumns = ['name', 'ccli_number', 'last_used_date', 'created_at'];
        $column = in_array($this->sortBy, $allowedColumns, true) ? $this->sortBy : 'name';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        if ($column === 'last_used_date') {
            return "CASE WHEN last_used_date IS NULL THEN 0 ELSE 1 END {$direction}, {$column} {$direction}";
        }

        return "{$column} {$direction}";
    }

    #[Computed]
    public function songs()
    {
        return Song::query()
            ->withLastUsedDate()
            ->when($this->search, function ($query): void {
                $query->whereAny(
                    ["name", "ccli_number"],
                    "like",
                    "%{$this->search}%",
                );
            })
            ->orderByRaw($this->buildOrderByClause())
            ->paginate(12);
    }

    public function delete($id): void
    {
        Song::findOrFail($id)->delete();
        Flux::modal("delete-song")->close();
        Flux::toast(variant: "danger", text: "Song deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Hymns and Spiritual Songs</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your worship music library.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:spacer/>
        <flux:button :href="route('songs.create')" size="sm" variant="primary" icon="plus">Add Song</flux:button>
    </div>

    <flux:table :paginate="$this->songs" class="mt-4">
        <flux:table.columns>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'ccli_number'" :direction="$sortDirection" wire:click="sort('ccli_number')">CCLI Number</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'last_used_date'" :direction="$sortDirection" wire:click="sort('last_used_date')">Last Used</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Added</flux:table.column>
            <flux:table.column class="font-semibold"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->songs as $song)
                <livewire:songs.row :$song :key="$song->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
