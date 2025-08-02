<?php

use App\Models\Song;
use Illuminate\Http\File;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public Song $song;
    public array $files = [];

    #[Validate("required|string")]
    public $description;

    public function save()
    {
        $this->validate();

        $uploadType =
            $this->files[0]["extension"] === "pdf" ? "sheets" : "recordings";
        $path = Storage::disk($uploadType)->putFile(
            $uploadType,
            new File($this->files[0]["path"]),
            "public",
        );

        $this->song->{$uploadType}()->create([
            "description" => $this->description,
            "filename" => $path,
        ]);

        $this->reset(["files", "description"]);
        $this->dispatch("refreshParent");
    }
};
?>

<form wire:submit="save">
    <livewire:dropzone wire:model="files" :rules="['mimes:mp3,m4a,aac,pdf','max:10420']" :multiple="false"/>

    <div x-show="$wire.files.length > 0" class="md:max-w-[50%] mt-4 space-y-6">
        <flux:field>
            <flux:label>Description</flux:label>
            <flux:input type="text" name="description" wire:model="description"/>
            <flux:error name="form.copyright" />
        </flux:field>

        <flux:button type="submit" variant="primary">Save</flux:button>
    </div>
</form>
