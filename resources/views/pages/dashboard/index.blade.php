<?php

use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    //
}; ?>

@php
    $user = auth()->user();
    $liturgy = $user->canAccessLiturgy();
    $liturgyAdmin = $user->canManageLiturgy();
    $pastoral = $user->canAccessPastoralCare();
    $firstName = $user->person?->first_name ?? Str::before($user->name, ' ');
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<section class="mx-auto w-full">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $greeting }}, {{ $firstName }}</flux:heading>
        <flux:subheading>{{ now()->format('l, F j, Y') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3 items-start">
        {{-- Quick Actions --}}
        <flux:card class="md:col-span-3 !py-3.5 !px-4.5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.sparkles variant="micro" class="text-zinc-400" />
                    <span class="text-[13px] font-bold text-zinc-500 dark:text-zinc-400">Quick actions</span>
                </span>

                @if ($liturgy)
                    <flux:button :href="route('services.create')" wire:navigate size="sm" variant="outline">
                        <flux:icon.calendar variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        New Service
                    </flux:button>
                @endif

                @if ($liturgyAdmin)
                    <flux:button :href="route('songs.create')" wire:navigate size="sm" variant="outline">
                        <flux:icon.musical-note variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Song
                    </flux:button>
                @endif

                @if ($pastoral)
                    <flux:button :href="route('pastoral-care.index')" wire:navigate size="sm" variant="outline">
                        <flux:icon.hand-platter variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Prayer Request
                    </flux:button>

                    <flux:button :href="route('pastoral-care.index')" wire:navigate size="sm" variant="outline">
                        <flux:icon.pencil-square variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                        Add Pastoral Note
                    </flux:button>
                @endif

                <flux:button :href="route('messages.index', ['compose' => 1])" wire:navigate size="sm" variant="outline">
                    <flux:icon.chat-bubble-left-right variant="micro" class="text-emerald-600 dark:text-emerald-400" />
                    New Conversation
                </flux:button>
            </div>
        </flux:card>

        {{-- islands land here in later tasks --}}
    </div>
</section>
