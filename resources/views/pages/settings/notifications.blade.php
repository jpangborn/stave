<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Notifications')"
        :subheading="__('Manage how Stave reaches you on this device.')"
    >
        <div class="space-y-10">
            <livewire:settings.notification-preferences />

            <flux:separator />

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/40">
                <p class="text-zinc-700 dark:text-zinc-300">
                    {{ __('On iPhone or iPad? Push notifications require installing Stave to your home screen first.') }}
                </p>
                <div class="mt-3">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        type="button"
                        icon="arrow-up-on-square"
                        onclick="window.dispatchEvent(new CustomEvent('show-a2hs-coachmark'))"
                    >
                        {{ __('Show install instructions') }}
                    </flux:button>
                </div>
            </div>

            <livewire:settings.push-subscription-manager />
        </div>
    </x-settings.layout>
</section>
