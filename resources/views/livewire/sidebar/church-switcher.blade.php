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

{{-- contents: ui-dropdown is inline-flex and only fills the sidebar when it is a
     direct flex item of the sidebar's flex column. --}}
<div class="contents">
    @if ($this->current)
        <flux:dropdown position="bottom" align="start">
            <flux:sidebar.profile
                :name="$this->current->name"
                :avatar="$this->current->logo_url"
                :initials="mb_substr($this->current->name, 0, 1)"
                icon-trailing="chevrons-up-down"
            />

            <flux:menu class="w-[240px]">
                <flux:menu.radio.group>
                    @foreach ($this->churches as $church)
                        <flux:menu.radio
                            wire:key="church-{{ $church->id }}"
                            :checked="$church->id === $this->current->id"
                            wire:click="switch({{ $church->id }})"
                        >{{ $church->name }}</flux:menu.radio>
                    @endforeach
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
