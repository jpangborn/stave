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

            <livewire:settings.push-subscription-manager />
        </div>
    </x-settings.layout>
</section>
