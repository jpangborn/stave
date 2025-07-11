<?php

use Flux\Flux;
use App\Models\Reading;
use App\Enums\Permission;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public $sortBy = "name";
    public $sortDirection = "asc";
    public $search = "";
    public $types = [];

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
    public function readings()
    {
        return Reading::query()
            ->when($this->search, function ($query): void {
                $query->whereLike("title", "%{$this->search}%");
            })
            ->when($this->types, function ($query): void {
                $query->whereIn("type", $this->types);
            })
            ->tap(
                fn($query) => $this->sortBy
                    ? $query->orderBy($this->sortBy, $this->sortDirection)
                    : $query
            )
            ->paginate(15);
    }

    public function delete($id): void
    {
        Reading::findOrFail($id)->delete();
        Flux::modal("delete-reading")->close();
        Flux::toast(variant: "danger", text: "Reading deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Readings and Prayers</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your corporate readings and prayers library.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:select variant="listbox" wire:model.live="types" size="sm" placeholder="Type..." class="max-w-64" multiple clearable>
            @foreach(\App\Enums\ReadingType::cases() as $readingType)
                <flux:select.option value="{{ $readingType->value }}">{{ $readingType->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:spacer/>
        <flux:button :href="route('readings.create')" size="sm" variant="primary" icon="plus">Add Reading</flux:button>
    </div>

    <flux:table :paginate="$this->readings" class="mt-4">
        <flux:table.columns>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">Title</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">Type</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Added</flux:table.column>
            <flux:table.column class="font-semibold"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->readings as $reading)
                <livewire:readings.row :$reading :key="$reading->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
