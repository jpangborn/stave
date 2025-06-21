    <?php
    use App\Models\LiturgyElement;
    use Livewire\Volt\Component;

    new class extends Component {
        public LiturgyElement $element;
    };
    ?>

<flux:table.row>
    <flux:table.cell>
        <div class="flex items-center gap-x-2">
            <div>
                <flux:icon.lectern />
            </div>
            <div>
                <flux:heading>{{ $element->name }}</flux:heading>
                @if($element->description)
                    <flux:subheading>{{ $element->description }}</flux:subheading>
                @endif
            </div>
            <flux:spacer />
            <div class="pr-2">
                <flux:dropdown align="end" offset="-15">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

                    <flux:menu class="min-w-32">
                        <flux:menu.item wire:click="editElement({{ $element->id }})" icon="pencil-square"  class="cursor-default">Edit</flux:menu.item>
                        <flux:menu.item wire:click="delete" icon="trash" variant="danger">Delete</flux:menu.item>
                    </flux:menu>
                </flux:drowdown>
            </div>
        </div>
    </flux:table.cell>
</flux:table.row>
