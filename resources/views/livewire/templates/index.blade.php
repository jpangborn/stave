<?php

use Flux\Flux;
use App\Models\Template;
use App\Enums\Permission;
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

    #[Computed]
    public function templates()
    {
        return Template::query()
            ->when($this->search, function ($query): void {
                $query->whereLike("name", "%{$this->search}%");
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
        Template::findOrFail($id)->delete();
        Flux::modal("delete-template")->close();
        Flux::toast(variant: "danger", text: "Template deleted.");
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Liturgy Templates</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your templates for service liturgies.</flux:subheading>

    <div class="flex space-x-4 items-center">
        <flux:input wire:model.live="search" size="sm" placeholder="Search..." icon="magnifying-glass" class="max-w-96" clearable/>
        <flux:spacer/>
        <flux:button :href="route('templates.create')" size="sm" variant="primary" icon="plus">Add Template</flux:button>
    </div>

    <flux:table :paginate="$this->templates" class="mt-4">
        <flux:table.columns>
            <flux:table.column sortable class="font-semibold max-w-6" :sorted="$sortBy === 'default'" :direction="$sortDirection" wire:click="sort('deafult')">Default</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable class="font-semibold" :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Added</flux:table.column>
            <flux:table.column class="font-semibold" ></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->templates as $template)
                <livewire:templates.row :$template :key="$template->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
