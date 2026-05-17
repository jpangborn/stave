<?php

use App\Models\LiturgyElement;
use App\Models\Reading;
use App\Support\SectionTone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    public LiturgyElement $element;

    #[Locked]
    public Collection $users;

    /** @var array<int,int> */
    #[Locked]
    public array $recentAssigneeIds = [];

    public ?string $sectionColor = null;

    public ?int $sectionIndex = null;

    public ?int $sectionElementCount = null;

    public bool $isFirstInSection = false;

    public bool $isLastInSection = false;

    public string $name;

    public bool $assigneeOpen = false;

    public bool $contentOpen = false;

    public string $assigneeSearch = '';

    public string $contentSearch = '';

    public function mount(?Collection $users = null): void
    {
        $this->users = $users ?? collect();
        $this->name = $this->element->name;
    }

    public function updatedName(string $value): void
    {
        $this->element->update(['name' => $value]);
        $this->dispatch('service-element-changed');
    }

    public function setAssignee(?int $userId): void
    {
        $this->element->update(['assignee_id' => $userId]);
        $this->assigneeOpen = false;
        $this->assigneeSearch = '';
        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Assignee saved.');
    }

    public function setContent(?int $readingId): void
    {
        if ($readingId === null) {
            $this->element->content()->dissociate();
        } else {
            $reading = Reading::findOrFail($readingId);
            $this->element->content()->associate($reading);
        }
        $this->element->save();
        $this->contentOpen = false;
        $this->contentSearch = '';
        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Reading selection saved.');
    }

    public function delete(): void
    {
        $this->modal('delete-element')->show();
    }

    public function duplicate(): void
    {
        DB::transaction(function () {
            $copy = $this->element->replicate();
            $copy->order = $this->element->order + 1;
            $copy->save();

            LiturgyElement::query()
                ->where('liturgy_type', $this->element->liturgy_type)
                ->where('liturgy_id', $this->element->liturgy_id)
                ->where('order', '>=', $copy->order)
                ->where('id', '!=', $copy->id)
                ->increment('order');
        });

        $this->dispatch('service-element-changed');
        Flux::toast(variant: 'success', text: 'Reading duplicated.');
    }

    #[Computed]
    public function readings(): Collection
    {
        if ($this->contentSearch === '') {
            return collect();
        }

        $query = Reading::query()
            ->where('title', 'like', '%'.$this->contentSearch.'%')
            ->orderBy('title');

        if ($this->element->reading_type) {
            $query->where('type', $this->element->reading_type);
        }

        return $query->limit(50)->get();
    }
};
?>

@php
    $tone = SectionTone::classesFor($sectionColor);
    $showStripe = $sectionColor !== null;
@endphp

<div :x-sort:item="$element->id" wire:key="reading-{{ $element->id }}"
     class="group relative grid items-center gap-3 border-b border-zinc-100 px-2 py-2 transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900
            {{ $showStripe ? 'grid-cols-[4px_18px_36px_minmax(140px,1fr)_minmax(140px,200px)_minmax(160px,240px)_32px]' : 'grid-cols-[18px_36px_minmax(140px,1fr)_minmax(140px,200px)_minmax(160px,240px)_32px]' }}">

    @if ($showStripe)
        <div class="-my-2 self-stretch rounded-sm opacity-60 {{ $tone['stripe'] }}"></div>
    @endif

    <div x-sort-handle class="flex h-6 cursor-grab items-center justify-center text-zinc-300 opacity-0 transition-opacity group-hover:opacity-100 dark:text-zinc-600" title="Drag to reorder">
        <flux:icon name="bars-2" class="size-3.5" />
    </div>

    <div class="flex size-9 items-center justify-center rounded-lg {{ $tone['swatch'] }}">
        <flux:icon name="{{ $element->type->icon() }}" class="size-4" />
    </div>

    <div class="min-w-0">
        <div class="text-[13.5px] font-semibold text-zinc-900 dark:text-zinc-100">
            <x-service.inline-text
                wire-model="name"
                :value="$name"
                :placeholder="$element->type->label()"
                class="text-[13.5px] font-semibold"
            />
        </div>
        <div class="text-[10.5px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            {{ $element->type->label() }}
        </div>
    </div>

    @include('livewire.elements._partials.assignee-chip', [
        'element' => $element,
        'users' => $users,
        'recentIds' => $recentAssigneeIds,
        'open' => $assigneeOpen,
        'search' => $assigneeSearch,
    ])

    @include('livewire.elements._partials.content-chip', [
        'element' => $element,
        'items' => $this->readings,
        'variant' => 'reading',
        'open' => $contentOpen,
        'search' => $contentSearch,
    ])

    <flux:dropdown align="end" offset="-15">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />
        <flux:menu class="min-w-36">
            <flux:menu.item href="{{ route('readings.create') }}" icon="plus">New reading</flux:menu.item>
            <flux:menu.item wire:click="duplicate" icon="document-duplicate">Duplicate</flux:menu.item>
            <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
        </flux:menu>
    </flux:dropdown>

    <flux:modal name="delete-element" class="min-w-[22rem]">
        <form wire:submit="$parent.delete({{ $element->id }})" class="space-y-6">
            <div>
                <flux:heading size="lg">Delete reading?</flux:heading>
                <flux:subheading>This will permanently delete the liturgy element. It cannot be undone.</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">Delete Element</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
