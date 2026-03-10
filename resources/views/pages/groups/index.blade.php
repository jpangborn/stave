<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Groups</flux:heading>
    <flux:subheading size="lg" class="mb-6">Manage your church groups.</flux:subheading>

    <div class="flex items-center">
        <flux:spacer/>
        <flux:button :href="route('groups.create')" size="sm" variant="primary" icon="plus">Add Group</flux:button>
    </div>
</section>
