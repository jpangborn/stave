<?php

use App\Models\Church;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /** @return Collection<int, Church> */
    #[Computed]
    public function churches(): Collection
    {
        return Auth::user()->churches()->orderBy('name')->get();
    }

    #[Computed]
    public function current(): ?Church
    {
        return Auth::user()->currentChurch;
    }

    public function switch(int $churchId): void
    {
        $church = Auth::user()->churches()->findOrFail($churchId);

        Auth::user()->switchChurch($church);

        // Full page load (no wire:navigate): every mounted component holds
        // state scoped to the previous church.
        $this->redirect(route('dashboard'), navigate: false);
    }
}; ?>

<div class="px-2 pb-2">
    @if ($this->churches->count() > 1)
        <flux:dropdown position="bottom" align="start">
            <flux:profile
                :name="$this->current?->name"
                :avatar="$this->current?->logo_url"
                :initials="$this->current?->name[0] ?? '?'"
                icon-trailing="chevrons-up-down"
            />

            <flux:menu class="w-[240px]">
                <flux:menu.radio.group>
                    @foreach ($this->churches as $church)
                        <flux:menu.radio
                            wire:key="church-{{ $church->id }}"
                            :checked="$church->id === $this->current?->id"
                            wire:click="switch({{ $church->id }})"
                        >{{ $church->name }}</flux:menu.radio>
                    @endforeach
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    @elseif ($this->current)
        <div class="flex items-center gap-2 px-3 py-1.5">
            <flux:avatar size="xs" :name="$this->current->name" :src="$this->current->logo_url" />
            <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $this->current->name }}</span>
        </div>
    @endif
</div>
