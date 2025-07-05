<?php

use App\Models\Service;
use Livewire\Volt\Component;

new class extends Component {
    public Service $service;

    public function delete()
    {
        $this->modal("delete-service")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        <flux:link variant="ghost" href="{{ route('services.show', ['service' => $service]) }}" >{{ $service->date->toFormattedDayDateString() }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        {{ $service->title }}
    </flux:table.cell>
    <flux:table.cell>
        @if($service->template)
            <flux:link variant="ghost" href="{{ route('templates.show', ['template' => $service->template]) }}" >{{ $service->template->name }}</flux:link>
       @endif
    </flux:table.cell>
    <flux:table.cell class="max-w-6">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('services.edit', ['service' => $service]) }}" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:drowdown>

        <flux:modal name="delete-service" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $service->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete service?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the service.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete service</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
