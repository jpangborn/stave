<?php

use App\Models\Song;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Song $song;

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    #[Validate(['file' => ['required', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/m4a,audio/aac,audio/x-aac,audio/x-hx-aac-adts,application/pdf', 'max:10420']])]
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
            'digital-ocean'
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
    <flux:file-upload wire:model="file" accept=".mp3,.m4a,.aac,.pdf,audio/mpeg,audio/mp4,audio/x-m4a,audio/m4a,audio/aac,audio/x-aac,audio/x-hx-aac-adts,application/pdf">
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

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
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
