<?php

use App\Models\Song;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Livewire\Forms\SongForm;

new class extends Component {
    public SongForm $form;

    #[Url]
    public $tab = "details";

    protected $listeners = [
        "refreshParent" => '$refresh',
    ];

    public function mount(Song $song): void
    {
        $this->form->setSong($song);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ $form->name }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        @if($form->ccli_number)<flux:badge>{{ $form->ccli_number }}</flux:badge>@endif {{ $form->copyright }}
    </flux:subheading>

    <flux:tab.group class="mt-8">
        <flux:tabs wire:model.deep="tab" scrollable>
            <flux:tab name="details" icon="musical-note">Details</flux:tab>
            <flux:tab name="lyrics" icon="megaphone">Lyrics</flux:tab>
            <flux:tab name="files" icon="document">Files</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="details">
            <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
                <div class="w-80">
                    <flux:heading size="lg">Song Details</flux:heading>
                    <flux:subheading>Information about the song.</flux:subheading>
                </div>

                <div class="flex-1 max-w-md space-y-6">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input type="text" name="name" wire:model.deep="form.name" variant="filled" readonly copyable/>
                        <flux:error name="form.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Authors</flux:label>
                        <flux:input type="text" name="authors" wire:model.deep="form.authors" variant="filled" readonly copyable/>
                        <flux:error name="form.authors" />
                    </flux:field>

                    <flux:field>
                        <flux:label>CCLI Number</flux:label>
                        <flux:input type="text" name="ccli_number" wire:model.deep="form.ccli_number" variant="filled" readonly copyable />
                        <flux:error name="form.ccli_number" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Copyright</flux:label>
                        <flux:input type="text" name="copyright" wire:model.deep="form.copyright" variant="filled" readonly copyable/>
                        <flux:error name="form.copyright" />
                    </flux:field>
                </div>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="lyrics">
            <div class="[&>p]:mb-8">
                {!! $form->lyrics !!}
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="files" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <flux:heading size="lg" level="2" class="mb-3">Recordings</flux:heading>
                    @if (!$form->song->recordings->isEmpty())
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($form->song->recordings as $recording)
                                <livewire:recordings.card :$recording :key="$recording->id"/>
                            @endforeach
                        </div>
                    @else
                        <flux:card>
                            <flux:heading size="lg" level="3">No Recordings Yet</flux:heading>
                            <flux:subheading>Add recordings by uploading files below.</flux:subheading>
                        </flux:card>
                    @endif
                </div>

                <div>
                    <flux:heading size="lg" level="2" class="mb-3">Sheets</flux:heading>
                    @if (!$form->song->sheets->isEmpty())
                        <div class="grid grid-cols-1 gap-4">
                            @foreach($form->song->sheets as $sheet)
                                <livewire:sheets.card :$sheet :key="$sheet->id"/>
                            @endforeach
                        </div>
                    @else
                        <flux:card>
                            <flux:heading size="lg" level="3">No Sheets Yet</flux:heading>
                            <flux:subheading>Add sheets by uploading files below.</flux:subheading>
                        </flux:card>
                    @endif
                </div>
            </div>

            <flux:separator variant="subtle" />

            <livewire:songs.upload-files :song="$form->song"/>
        </flux:tab.panel>
    </flux:tab.group>
</section>
