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
    public function songs()
    {
        return Song::query()
            ->tap(
                fn($query) => $this->sortBy
                    ? $query->orderBy($this->sortBy, $this->sortDirection)
                    : $query
            )
            ->paginate(15);
    }

    public function delete($id)
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

    <flux:table :paginate="$this->songs" class="mt-2">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'ccli_number'" :direction="$sortDirection" wire:click="sort('ccli_number')">CCLI Number</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Added</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->songs as $song)
                <livewire:songs.row :$song :key="$song->id"/>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
