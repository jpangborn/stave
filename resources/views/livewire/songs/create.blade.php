<?php

use Flux\Flux;
use Livewire\Volt\Component;
use App\Livewire\Forms\SongForm;

new class extends Component {
    public SongForm $form;

    public function save()
    {
        $this->form->store();
        Flux::toast(variant: "success", text: "Song added.");
        return $this->redirect("/songs", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Add a Song</flux:heading>
    <flux:subheading size="lg" class="mb-6">Fill in details about the song.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Song Details</flux:heading>
                <flux:subheading>Information about the song.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Name</flux:label>
                    <flux:input type="text" name="name" wire:model="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label>CCLI Number</flux:label>
                    <flux:input type="text" name="ccli_number" wire:model="form.ccli_number" />
                    <flux:error name="form.ccli_number" />
                </flux:field>

                <flux:field>
                    <flux:label>Copyright</flux:label>
                    <flux:input type="text" name="copyright" wire:model="form.copyright" />
                    <flux:error name="form.copyright" />
                </flux:field>

                <flux:editor label="Lyrics" wire:model="form.lyrics" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[400px]" />

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
