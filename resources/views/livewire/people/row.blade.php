<?php

use App\Models\Person;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    public string $density = 'spacious';

    public function open(): void
    {
        $this->dispatch('open-person-drawer', personId: $this->person->id)->to('people.drawer');
    }
}; ?>

<flux:table.row class="cursor-pointer" wire:click="open">
    <flux:table.cell>
        <div class="flex items-center gap-3">
            <x-person-avatar :person="$person" :size="$density === 'compact' ? 'xs' : 'sm'" />
            <div class="min-w-0">
                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $person->full_name }}</div>
                @if ($person->email)
                    <div class="text-xs text-zinc-500 truncate">{{ $person->email }}</div>
                @endif
            </div>
        </div>
    </flux:table.cell>

    <flux:table.cell>
        <x-membership-badge :status="$person->membership_status" :reason="$person->termination_reason" />
    </flux:table.cell>

    <flux:table.cell>
        @if ($person->offices->isEmpty())
            <span class="text-zinc-400 text-xs">—</span>
        @else
            <div class="inline-flex gap-1">
                @foreach ($person->offices as $office)
                    <flux:badge
                        size="sm"
                        :color="$office->kind->color()"
                        :icon="$office->kind->icon()"
                        :title="$office->kind->label() . ' · since ' . $office->started_on->format('M Y')"
                    />
                @endforeach
            </div>
        @endif
    </flux:table.cell>

    <flux:table.cell class="text-zinc-500 text-sm whitespace-nowrap">
        {{ $person->created_at->toFormattedDayDateString() }}
    </flux:table.cell>

    <flux:table.cell align="end">
        <flux:icon name="chevron-right" class="size-4 text-zinc-400" />
    </flux:table.cell>
</flux:table.row>
