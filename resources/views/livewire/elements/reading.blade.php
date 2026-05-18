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

    public ?int $hoverContentId = null;

    public function mount(?Collection $users = null): void
    {
        $this->users = $users ?? collect();
        $this->name = $this->element->name;
    }

    public function openContent(): void
    {
        $this->contentOpen = true;
        $this->contentSearch = '';
        $this->hoverContentId = $this->element->content_id
            ?? $this->readings->first()?->id;
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
        $query = Reading::query()->withLastUsedDate();

        if ($this->contentSearch !== '') {
            $query->where('title', 'like', '%'.$this->contentSearch.'%');
        }

        if ($this->element->reading_type) {
            $query->where('type', $this->element->reading_type);
        }

        return $query
            ->orderByDesc('last_used_date')
            ->orderBy('title')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function previewReading(): ?Reading
    {
        if ($this->hoverContentId === null) {
            return null;
        }

        return Reading::query()
            ->withLastUsedDate()
            ->find($this->hoverContentId);
    }
};
?>

@php
    $tone = SectionTone::classesFor($sectionColor);
@endphp

<div>
    <x-service.element-row
        :element="$element"
        :tone="$tone"
        :section-color="$sectionColor"
        :is-first-in-section="$isFirstInSection"
        :is-last-in-section="$isLastInSection"
        :name="$name"
        wire-key-prefix="reading"
    >
        <x-slot:assignee>
            @include('livewire.elements._partials.assignee-chip', [
                'element' => $element,
                'users' => $users,
                'recentIds' => $recentAssigneeIds,
                'open' => $assigneeOpen,
                'search' => $assigneeSearch,
            ])
        </x-slot:assignee>

        <x-slot:content>
            @include('livewire.elements._partials.content-chip', [
                'element' => $element,
                'items' => $this->readings,
                'variant' => 'reading',
                'open' => $contentOpen,
                'search' => $contentSearch,
                'hoverId' => $hoverContentId,
                'previewItem' => $this->previewReading,
            ])
        </x-slot:content>

        <x-slot:actions>
            <flux:dropdown align="end" offset="-15">
                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />
                <flux:menu class="min-w-36">
                    <flux:menu.item href="{{ route('readings.create') }}" icon="plus">New reading</flux:menu.item>
                    <flux:menu.item wire:click="duplicate" icon="document-duplicate">Duplicate</flux:menu.item>
                    <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </x-slot:actions>
    </x-service.element-row>

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
