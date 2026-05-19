<?php

use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    /** @var array<int, array{endpoint: string, created_at: string|null}> */
    public array $subscriptions = [];

    public function mount(): void
    {
        $this->refreshSubscriptions();
    }

    /**
     * Persist a Web Push subscription supplied by the browser.
     *
     * @param  array{endpoint?: string, keys?: array{p256dh?: string, auth?: string}, contentEncoding?: string}  $subscription
     */
    public function storeSubscription(array $subscription): void
    {
        $endpoint = $subscription['endpoint'] ?? null;
        if (! $endpoint) {
            return;
        }

        Auth::user()->updatePushSubscription(
            $endpoint,
            $subscription['keys']['p256dh'] ?? null,
            $subscription['keys']['auth'] ?? null,
            $subscription['contentEncoding'] ?? null,
        );

        $this->refreshSubscriptions();
    }

    public function removeSubscription(string $endpoint): void
    {
        Auth::user()->deletePushSubscription($endpoint);

        $this->refreshSubscriptions();
    }

    public function sendTest(): void
    {
        Auth::user()->notify(new TestNotification);
    }

    public function truncateEndpoint(string $endpoint): string
    {
        return Str::limit($endpoint, 60);
    }

    private function refreshSubscriptions(): void
    {
        $this->subscriptions = Auth::user()
            ->pushSubscriptions()
            ->orderByDesc('created_at')
            ->get(['endpoint', 'created_at'])
            ->map(fn ($subscription) => [
                'endpoint' => (string) $subscription->endpoint,
                // @phpstan-ignore-next-line property.notFound (timestamps exist on push_subscriptions table)
                'created_at' => $subscription->created_at?->toDateTimeString(),
            ])
            ->all();
    }
}; ?>

<section
    class="w-full"
    x-data="{
        permission: 'default',
        async init() {
            this.permission = (window.StavePush && await window.StavePush.getPermissionState()) || 'default';
        },
        async enable() {
            try {
                const subscription = await window.StavePush.subscribeToPush(
                    @js(config('webpush.vapid.public_key'))
                );
                this.permission = 'granted';
                $wire.storeSubscription(subscription);
            } catch (e) {
                console.error(e);
            }
        },
        async disable() {
            const endpoint = await window.StavePush.unsubscribeFromPush();
            if (endpoint) {
                $wire.removeSubscription(endpoint);
            }
        },
    }"
>
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Notifications')"
        :subheading="__('Manage how Stave reaches you on this device.')"
    >
        <div class="space-y-8">
            <div>
                <flux:heading>{{ __('Push notifications') }}</flux:heading>
                <flux:subheading>{{ __('Receive a banner when teammates message you, even when Stave is closed.') }}</flux:subheading>

                <template x-if="permission === 'unsupported'">
                    <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                        <flux:callout.text>
                            {{ __('Push notifications are not supported in this browser.') }}
                        </flux:callout.text>
                    </flux:callout>
                </template>

                <template x-if="permission !== 'unsupported'">
                    <div>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <template x-if="permission !== 'granted'">
                                <flux:button variant="primary" icon="bell" x-on:click="enable()">{{ __('Enable on this device') }}</flux:button>
                            </template>

                            <template x-if="permission === 'granted'">
                                <flux:button variant="filled" icon="bell-slash" x-on:click="disable()">{{ __('Disable on this device') }}</flux:button>
                            </template>

                            <flux:button variant="ghost" icon="paper-airplane" wire:click="sendTest">{{ __('Send test push') }}</flux:button>
                        </div>

                        <template x-if="permission === 'denied'">
                            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                                <flux:callout.text>
                                    {{ __('Notifications are blocked in your browser. Update the site permissions to enable push.') }}
                                </flux:callout.text>
                            </flux:callout>
                        </template>
                    </div>
                </template>
            </div>

            @if ($subscriptions)
                <div>
                    <flux:heading size="sm">{{ __('Registered devices') }}</flux:heading>
                    <ul class="mt-3 space-y-2">
                        @foreach ($subscriptions as $subscription)
                            <li wire:key="sub-{{ md5($subscription['endpoint']) }}" class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="min-w-0">
                                    <flux:text class="truncate font-mono text-xs">{{ $this->truncateEndpoint($subscription['endpoint']) }}</flux:text>
                                    @if ($subscription['created_at'])
                                        <flux:subheading size="sm">{{ __('Added :date', ['date' => $subscription['created_at']]) }}</flux:subheading>
                                    @endif
                                </div>
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeSubscription({{ json_encode($subscription['endpoint']) }})">
                                    {{ __('Remove') }}
                                </flux:button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
