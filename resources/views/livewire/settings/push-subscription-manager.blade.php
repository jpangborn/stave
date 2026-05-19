<?php

use App\Models\User;
use App\Notifications\TestNotification;
use App\Support\DeviceLabel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use NotificationChannels\WebPush\PushSubscription;

new class extends Component {
    /**
     * @var array<int, array{
     *     endpoint: string,
     *     label: string,
     *     created_at: string|null,
     *     last_used_at: string|null,
     *     has_user_agent: bool
     * }>
     */
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
    public function storeSubscription(array $subscription, ?string $userAgent = null): void
    {
        $endpoint = $subscription['endpoint'] ?? null;
        if (! $endpoint) {
            return;
        }

        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $user->updatePushSubscription(
            $endpoint,
            $subscription['keys']['p256dh'] ?? null,
            $subscription['keys']['auth'] ?? null,
            $subscription['contentEncoding'] ?? null,
        );

        PushSubscription::query()
            ->where('endpoint', $endpoint)
            ->update([
                'user_agent' => $userAgent !== null ? Str::limit($userAgent, 512, '') : null,
                'last_used_at' => now(),
            ]);

        $this->refreshSubscriptions();
    }

    public function removeSubscription(string $endpoint): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $user->deletePushSubscription($endpoint);

        $this->refreshSubscriptions();
    }

    public function sendTest(): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $user->notify(new TestNotification);
    }

    private function refreshSubscriptions(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->subscriptions = $user
            ->pushSubscriptions()
            ->orderByDesc('created_at')
            ->get(['endpoint', 'user_agent', 'created_at', 'last_used_at'])
            ->map(function (PushSubscription $subscription): array {
                $userAgent = $subscription->getAttribute('user_agent');
                $createdAt = $subscription->getAttribute('created_at');
                $lastUsedAt = $subscription->getAttribute('last_used_at');

                $label = $userAgent
                    ? DeviceLabel::fromUserAgent($userAgent)
                    : Str::limit($subscription->endpoint, 60);

                return [
                    'endpoint' => (string) $subscription->endpoint,
                    'label' => $label,
                    'created_at' => $createdAt instanceof Carbon ? $createdAt->isoFormat('MMM D, YYYY') : null,
                    'last_used_at' => $lastUsedAt instanceof Carbon ? $lastUsedAt->diffForHumans() : null,
                    'has_user_agent' => (bool) $userAgent,
                ];
            })
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
                $wire.storeSubscription(subscription, navigator.userAgent);
            } catch (e) {
                console.error(e);
            }
        },
        async disable() {
            const endpoint = await window.StavePush.unsubscribeFromPush();
            if (endpoint) {
                await $wire.removeSubscription(endpoint);
            }
            this.permission = 'default';
        },
    }"
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
                        <li
                            wire:key="sub-{{ md5($subscription['endpoint']) }}"
                            class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-700"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <flux:icon.device-phone-mobile class="size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                <div class="min-w-0">
                                    <flux:text @class([
                                        'truncate font-medium',
                                        'font-mono text-xs' => ! $subscription['has_user_agent'],
                                    ])>
                                        {{ $subscription['label'] }}
                                    </flux:text>
                                    @if ($subscription['created_at'] || $subscription['last_used_at'])
                                        <flux:subheading size="sm">
                                            @if ($subscription['created_at'])
                                                {{ __('Added :date', ['date' => $subscription['created_at']]) }}
                                            @endif
                                            @if ($subscription['created_at'] && $subscription['last_used_at'])
                                                <span class="mx-1">&middot;</span>
                                            @endif
                                            @if ($subscription['last_used_at'])
                                                {{ __('Last active :time', ['time' => $subscription['last_used_at']]) }}
                                            @endif
                                        </flux:subheading>
                                    @endif
                                </div>
                            </div>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="removeSubscription({{ json_encode($subscription['endpoint']) }})"
                            >
                                {{ __('Remove') }}
                            </flux:button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
