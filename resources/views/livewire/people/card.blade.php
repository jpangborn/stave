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

<button
    type="button"
    wire:click="open"
    @class([
        'group flex flex-col gap-2.5 text-left bg-white dark:bg-zinc-900 rounded-lg ring-1 ring-zinc-200 dark:ring-zinc-700 hover:ring-zinc-300 dark:hover:ring-zinc-600 hover:shadow-sm transition cursor-pointer',
        'p-4' => $density === 'spacious',
        'p-3' => $density === 'compact',
    ])
>
    <div class="flex items-center gap-3">
        <x-person-avatar :person="$person" :size="$density === 'compact' ? 'sm' : 'md'" />
        <div class="flex-1 min-w-0">
            <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $person->full_name }}</div>
            @if ($person->email)
                <div class="text-xs text-zinc-500 truncate">{{ $person->email }}</div>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-1.5 min-h-[1.5rem]">
        <x-membership-badge :status="$person->membership_status" :reason="$person->termination_reason" />
        @foreach ($person->offices as $office)
            <flux:badge
                size="sm"
                :color="$office->kind->color()"
                :icon="$office->kind->icon()"
                :title="$office->kind->label()"
            />
        @endforeach
    </div>

    @if ($person->phone || $person->address_city)
        <div class="mt-auto pt-2 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-xs text-zinc-500">
            @if ($person->phone)
                <flux:icon name="phone" variant="micro" class="size-3.5" />
                <div class="text-zinc-700 dark:text-zinc-300 tabular-nums">{{ $person->phone }}</div>
            @endif
            @if ($person->address_city)
                <flux:icon name="map-pin" variant="micro" class="size-3.5" />
                <div>{{ $person->address_city }}@if ($person->address_state), {{ $person->address_state }}@endif</div>
            @endif
        </div>
    @endif
</button>
