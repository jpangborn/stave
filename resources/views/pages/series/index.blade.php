<?php

use Flux\Flux;
use App\Models\Series;
use Livewire\Component;
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

    #[Computed]
    public function series()
    {
        return Series::query()
            ->withCount('readings')
            ->when($this->search, function ($query): void {
                $query->whereLike("name", "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    public function delete($id): void
    {
        Series::findOrFail($id)->delete();
        Flux::modal("delete-series")->close();
        Flux::toast(variant: "danger", text: "Series deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Series</flux:heading>
    <flux:subheading size="lg" class="mb-6">Group readings into themed collections.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:spacer/>
        <flux:button :href="route('series.create')" size="sm" variant="primary" icon="plus">Add Series</flux:button>
    </div>

    <flux:table :paginate="$this->series" class="mt-4">
        <flux:table.columns>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="font-semibold">Readings</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            <flux:table.column class="font-semibold"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->series as $seriesItem)
                <livewire:series.row :series="$seriesItem" :key="$seriesItem->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
