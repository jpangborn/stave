<?php

use App\Models\Template;
use Livewire\Volt\Component;

new class extends Component {
    public Template $template;

    public function delete()
    {
        $this->modal("delete-template")->show();
    }
};
?>

<flux:table.row>
    <flux:table.cell>
        @if($template->default)<flux:icon.circle-check-big class="text-green-500 dark:text-green-600" />@endif
    </flux:table.cell>
    <flux:table.cell>
        <flux:link variant="ghost" href="{{ route('templates.show', ['template' => $template]) }}" >{{ $template->name }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        {{ $template->created_at->toFormattedDayDateString() }}
    </flux:table.cell>
    <flux:table.cell class="max-w-6">
        <flux:dropdown align="end" offset="-15">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

            <flux:menu class="min-w-32">
                <flux:menu.item href="{{ route('templates.edit', ['template' => $template]) }}" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
            </flux:menu>
        </flux:drowdown>

        <flux:modal name="delete-template" class="min-w-[22rem]">
            <form wire:submit="$parent.delete({{ $template->id }})" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete template?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the template.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete template</flux:button>
                </div>
            </form>
        </flux:modal>
    </flux:table.cell>
</flux:table.row>
