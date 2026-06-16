<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadDirectCount();
    }

    #[On('messages-unread-updated')]
    public function refresh(): void
    {
        unset($this->unreadCount);
    }
}; ?>

<div>
    <flux:sidebar.item
        icon="chat-bubble-left-right"
        :href="route('messages.index')"
        :current="request()->routeIs('messages.*')"
        :badge="$this->unreadCount > 0 ? $this->unreadCount : null"
        wire:navigate
    >{{ __('Messages') }}</flux:sidebar.item>

    @script
    <script>
        window.addEventListener('stave:notification', () => $wire.refresh());
    </script>
    @endscript
</div>
