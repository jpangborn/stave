<?php

use App\Models\Song;
use App\Enums\Permission;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use App\Livewire\Forms\SongForm;

new class extends Component {
    public SongForm $form;

    #[Url]
    public $tab = "details";

    public function mount(Song $song): void
    {
        $this->form->setSong($song);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast("Song updated.");
        return $this->redirect("/songs", navigate: true);
    }

    public function delete()
    {
        $this->form->song->delete();

        Flux::toast("Song deleted.");
        return $this->redirect("/songs", navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit Song: {{ $form->name }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        @if($form->ccli_number)<flux:badge>{{ $form->ccli_number }}</flux:badge>@endif {{ $form->copyright }}
    </flux:subheading>

    <flux:tab.group variant="flush" class="mt-8">
        <flux:tabs wire:model="tab">
            <flux:tab name="details" icon="musical-note">Details</flux:tab>
            <flux:tab name="lyrics" icon="megaphone">Lyrics</flux:tab>
            <flux:tab name="files" icon="document">Files</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <form wire:submit="save" class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Song Details</flux:heading>
                    <flux:subheading>Information about the song.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input type="text" name="name" wire:model="form.name" />
                        <flux:error name="form.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Authors</flux:label>
                        <flux:input type="text" name="authors" wire:model="form.authors" placeholder="e.g., John Smith, Jane Doe" />
                        <flux:description>Separate multiple authors with commas</flux:description>
                        <flux:error name="form.authors" />
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

                    <div class="flex space-x-2">
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </div>
            </form>
        </flux:tab.panel>
        <flux:tab.panel name="lyrics">
            <form wire:submit="save" class="space-y-6">
                <flux:editor label="Lyrics" wire:model="form.lyrics" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[400px]" />
                <div class="flex">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </flux:tab.panel>
        <flux:tab.panel name="files" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <flux:heading size="lg" level="2" class="mb-3">Recordings</flux:heading>
                    @if (!$form->song->recordings->isEmpty())
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($form->song->recordings as $recording)
                                <livewire:recording-card :$recording :key="$recording->id"/>
                            @endforeach
                        </div>
                    @else
                        <flux:card>
                            <flux:heading size="lg" level="3">No Recordings Yet</flux:heading>
                            @can(Permission::EDIT_SONGS)
                                <flux:subheading>Add some by uploading files below.</flux:subheading>
                            @endcan
                        </flux:card>
                    @endif
                </div>

                <div>
                    <flux:heading size="lg" level="2" class="mb-3">Sheets</flux:heading>
                    @if (!$form->song->sheets->isEmpty())
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($form->song->sheets as $sheet)
                                <livewire:sheet-card :$sheet :key="$sheet->id"/>
                            @endforeach
                        </div>
                    @else
                        <flux:card>
                            <flux:heading size="lg" level="3">No Sheets Yet</flux:heading>
                            @can(Permission::EDIT_SONGS)
                                <flux:subheading>Add some by uploading files below.</flux:subheading>
                            @endcan
                        </flux:card>
                    @endif
                </div>
            </div>

            <flux:separator variant="subtle" />

            @can(Permission::EDIT_SONGS)
                <livewire:upload-song-files :song="$form->song"/>
            @endcan
        </flux:tab.panel>
    </flux:tab.group>
</section>
