<?php

use Flux\Flux;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public $recording;

    #[Computed]
    public function fileUrl()
    {
        return Storage::disk("recordings")->url($this->recording->filename);
    }

    public function confirmDelete()
    {
        $this->modal("delete-recording")->show();
    }

    public function delete()
    {
        $this->recording->delete();

        $this->dispatch("refreshParent");
        Flux::toast(variant: "danger", text: "Recording deleted.");
    }
};
?>

<flux:card>
    <div class="flex items-center gap-2">
        <flux:icon.document class="text-zinc-800 dark:text-white" />

        <flux:heading>{{ $recording->description }}</flux:heading>

        <flux:spacer />

        <flux:button icon="eye" variant="ghost" href="{{ $this->fileUrl() }}" inset="top bottom" target="_blank">View Sheet</flux:button>
        <flux:button icon="trash" variant="ghost" inset="top bottom" wire:click="confirmDelete" />

        <flux:modal name="delete-recording" class="min-w-[22rem]">
            <form wire:submit="delete" class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete recording?</flux:heading>

                    <flux:subheading>
                        <p>This will permanently delete the recording.</p>
                        <p>It cannot be undone.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Delete recording</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</flux:card>
