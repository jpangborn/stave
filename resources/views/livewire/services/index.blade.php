<?php

use Flux\Flux;
use App\Models\Service;
use App\Enums\Permission;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public $sortBy = "name";
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
    public function services()
    {
        return Service::query()
            ->when($this->search, function ($query) {
                $query->whereLike("name", "%{$this->search}%");
            })
            ->tap(
                fn($query) => $this->sortBy
                    ? $query->orderBy($this->sortBy, $this->sortDirection)
                    : $query->orderBy("date", "desc")
            )
            ->with("template")
            ->paginate(15);
    }

    public function delete($id)
    {
        Service::findOrFail($id)->delete();
        Flux::modal("delete-service")->close();
        Flux::toast(variant: "danger", text: "Service deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Services</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your services.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:spacer/>
        <flux:button :href="route('services.create')" size="sm" variant="primary" icon="plus">Add Service</flux:button>
    </div>

    <flux:table :paginate="$this->services" class="mt-4">
        <flux:table.columns>
            <flux:table.column sortable class="font-semibold max-w-6" :sorted="$sortBy === 'date'" :direction="$sortDirection" wire:click="sort('deafult')">Date</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('name')">Title</flux:table.column>
            <flux:table.column class="font-semibold">Template</flux:table.column>
            <flux:table.column class="font-semibold"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->services as $service)
                <livewire:services.row :$service :key="$service->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
