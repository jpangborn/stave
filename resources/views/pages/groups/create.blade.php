<?php

use App\Enums\GroupMessaging;
use App\Enums\GroupVisibility;
use App\Livewire\Forms\GroupForm;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public GroupForm $form;

    public $image = null;

    public function removeImage(): void
    {
        if ($this->image) {
            $this->image->delete();
            $this->image = null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'image' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ]);

        $this->form->validate();

        $imagePath = null;

        if ($this->image) {
            $imagePath = $this->image->storePublicly('groups', 'digital-ocean');
        }

        $this->form->store($imagePath);
        Flux::toast(variant: "success", text: "Group created.");

        $this->redirect(route('groups.index'), navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1">Create a Group</flux:heading>
    <flux:subheading size="lg" class="mb-6">Add a new group to your church.</flux:subheading>

    <form wire:submit="save">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-8">
            <div class="w-80">
                <flux:heading size="lg">Group Details</flux:heading>
                <flux:subheading>Basic information about the group.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:field>
                    <flux:label badge="Required">Name</flux:label>
                    <flux:input type="text" name="name" wire:model="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:editor label="Description" wire:model="form.description" toolbar="heading | bold italic underline ~ undo redo" class="**:data-[slot=content]:min-h-[200px]" />

                <flux:file-upload wire:model="image" label="Group Image" accept="image/jpeg,image/png,image/webp">
                    <flux:file-upload.dropzone heading="Drop image here or click to browse" text="JPG, PNG, WebP up to 5MB" />
                </flux:file-upload>

                @if ($image && $image->isPreviewable())
                    <div class="flex flex-col gap-2">
                        <flux:file-item
                            :heading="$image->getClientOriginalName()"
                            :image="$image->temporaryUrl()"
                            :size="$image->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeImage" aria-label="{{ 'Remove file: ' . $image->getClientOriginalName() }}" />
                            </x-slot>
                        </flux:file-item>
                    </div>
                @endif

                <flux:error name="image" />
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mt-12">
            <div class="w-80">
                <flux:heading size="lg">Settings</flux:heading>
                <flux:subheading>Configure visibility and messaging.</flux:subheading>
            </div>

            <div class="flex-1 max-w-md space-y-6">
                <flux:select wire:model="form.visibility" label="Visibility" variant="listbox">
                    @foreach (GroupVisibility::cases() as $visibility)
                        <flux:select.option :value="$visibility->value">{{ $visibility->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.messaging" label="Messaging" variant="listbox">
                    @foreach (GroupMessaging::cases() as $messaging)
                        <flux:select.option :value="$messaging->value">{{ $messaging->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </div>
    </form>
</section>
