<?php

use App\Models\Song;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Song $song;

    #[Validate(['file' => ['required', 'file', 'mimes:mp3,m4a,aac,pdf', 'max:10420']])]
    public ?TemporaryUploadedFile $file = null;

    #[Validate(['description' => ['required', 'string']])]
    public string $description = '';

    public function save(): void
    {
        $this->validate();

        $extension = $this->file->getClientOriginalExtension();
        $uploadType = $extension === 'pdf' ? 'sheets' : 'recordings';

        $path = $this->file->storePublicly(
            $uploadType,
            $uploadType
        );

        $this->song->{$uploadType}()->create([
            'description' => $this->description,
            'filename' => $path,
        ]);

        $this->reset(['file', 'description']);
        $this->dispatch('refreshParent');
    }

    public function removeFile(): void
    {
        $this->reset(['file', 'description']);
    }
};
?>

<form wire:submit="save">
    <flux:file-upload wire:model="file" accept=".mp3,.m4a,.aac,.pdf">
        @if ($file)
            <flux:file-item
                :heading="$file->getClientOriginalName()"
                :size="$file->getSize()"
            >
                <x-slot:actions>
                    <flux:file-item.remove wire:click="removeFile" />
                </x-slot:actions>
            </flux:file-item>

            <div class="mt-4 space-y-6">
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:input type="text" name="description" wire:model="description" />
                    <flux:error name="description" />
                </flux:field>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        @else
            <flux:file-upload.dropzone
                with-progress
                heading="Upload a file"
                text="Drop here or click to browse. Up to 10MB."
                subtext="PDF, MP3, M4A, or AAC"
            />
        @endif
    </flux:file-upload>

    <flux:error name="file" />
</form>
