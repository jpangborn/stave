<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $limit = 15;

    #[Computed]
    public function notifications(): Collection
    {
        /** @var Collection<int, DatabaseNotification> */
        return Auth::user()
            ->notifications()
            ->latest()
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function markRead(string $id): void
    {
        Auth::user()
            ->unreadNotifications()
            ->whereKey($id)
            ->update(['read_at' => now()]);

        unset($this->notifications, $this->unreadCount);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        unset($this->notifications, $this->unreadCount);
    }

    public function refresh(): void
    {
        unset($this->notifications, $this->unreadCount);
    }
}; ?>

<div>
    <flux:dropdown position="bottom" align="end">
        <flux:sidebar.item icon="bell" :badge="$this->unreadCount">Notifications</flux:sidebar.item>

        <flux:menu class="max-h-[480px] w-[380px] overflow-y-auto">
            <div class="flex items-center justify-between px-3 py-2">
                <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>

                @if ($this->unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllRead"
                        class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
                    >
                        {{ __('Mark all read') }}
                    </button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($this->notifications as $notification)
                @php
                    $data = $notification->data;
                    $isMention = ($data['type'] ?? null) === 'comment.mention';
                    $url = $data['url'] ?? '/dashboard';
                    $title = $data['title'] ?? __('Notification');
                    $body = $data['body'] ?? '';
                    $isUnread = $notification->read_at === null;
                @endphp

                <a
                    href="{{ $url }}"
                    wire:click="markRead('{{ $notification->id }}')"
                    wire:navigate
                    class="flex gap-3 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $isUnread ? 'bg-blue-50/40 dark:bg-blue-950/20' : '' }}"
                >
                    <div class="mt-0.5 shrink-0">
                        @if ($isMention)
                            <flux:icon.at-symbol class="size-5 text-blue-500" />
                        @else
                            <flux:icon.bell class="size-5 text-zinc-400" />
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate {{ $isUnread ? 'font-semibold text-zinc-900 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $title }}
                            </span>
                            <span class="shrink-0 text-xs text-zinc-400">
                                {{ $notification->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>

                        @if ($body !== '')
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $body }}
                            </p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="px-3 py-8 text-center">
                    <flux:subheading>{{ __('No notifications yet') }}</flux:subheading>
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>

    @script
    <script>
        window.addEventListener('stave:notification', () => {
            $wire.refresh();
        });
    </script>
    @endscript
</div>
