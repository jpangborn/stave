<div
    x-cloak
    x-data="dropzone({
        _this: @this,
        multiple: @js($multiple)
    })"
    @dragenter.prevent.document="onDragenter($event)"
    @dragleave.prevent="onDragleave($event)"
    @dragover.prevent="onDragover($event)"
    @drop.prevent="onDrop"
    class="w-full antialiased"
>
    <div class="flex flex-col items-start h-full w-full justify-center">
        @if(!is_null($error))
            <div class="relative w-full mb-2 overflow-hidden rounded-md border border-red-600 bg-white text-slate-700 dark:bg-slate-900 dark:text-slate-300" role="alert">
                <div class="flex w-full items-center gap-2 bg-red-600/10 p-4">
                    <div class="bg-red-600/15 text-red-600 rounded-full p-1" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-6" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-2">
                        <h3 class="text-sm font-semibold text-red-600">Problem uploading file.</h3>
                        <p class="text-xs font-medium sm:text-sm">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif
        <div class="flex justify-between w-full mb-1">
            <label for="upload" class="pl-0.5 text-sm text-zinc-800 dark:text-white">{{ __('Upload Files') }}</label>
            <div x-show="isLoading" role="status">
                <flux:icon.loading class="size-5"/>
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div @click="$refs.input.click()" class="group border border-dashed border-zinc-200 dark:border-white/10 rounded-xl text-zinc-500 bg-white dark:bg-white/10 dark:text-white/70 w-full cursor-pointer">
            <div>
                <div x-show="!isDragging" class="flex flex-col items-center justify-center gap-2 py-8 h-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-12 h-12 opacity-75">
                        <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" />
                        <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                    </svg>
                    <p>Drop here or <span class="font-medium group-focus-within:underline hover:underline">Browse files</span></p>
                </div>
                <div x-show="isDragging" class="flex flex-col items-center justify-center gap-2 py-8 h-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-12 h-12 opacity-75">
                        <path d="M10 2a.75.75 0 01.75.75v5.59l1.95-2.1a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0L6.2 7.26a.75.75 0 111.1-1.02l1.95 2.1V2.75A.75.75 0 0110 2z" />
                        <path d="M5.273 4.5a1.25 1.25 0 00-1.205.918l-1.523 5.52c-.006.02-.01.041-.015.062H6a1 1 0 01.894.553l.448.894a1 1 0 00.894.553h3.438a1 1 0 00.86-.49l.606-1.02A1 1 0 0114 11h3.47a1.318 1.318 0 00-.015-.062l-1.523-5.52a1.25 1.25 0 00-1.205-.918h-.977a.75.75 0 010-1.5h.977a2.75 2.75 0 012.651 2.019l1.523 5.52c.066.239.099.485.099.732V15a2 2 0 01-2 2H3a2 2 0 01-2-2v-3.73c0-.246.033-.492.099-.73l1.523-5.521A2.75 2.75 0 015.273 3h.977a.75.75 0 010 1.5h-.977z" />
                    </svg>
                    <p>Drop here to upload</p>
                </div>
            </div>
            <input
                x-ref="input"
                wire:model="upload"
                type="file"
                class="hidden"
                x-on:livewire-upload-start="isLoading = true"
                x-on:livewire-upload-finish="isLoading = false"
                x-on:livewire-upload-error="console.log('livewire-dropzone upload error', error)"
                @if(! is_null($this->accept)) accept="{{ $this->accept }}" @endif
                @if($multiple === true) multiple @endif
            >
        </div>

        <div class="flex items-center gap-2 text-zinc-500 dark:text-white/70 text-sm mt-2">
            @php
                $hasMaxFileSize = !is_null($this->maxFileSize);
                $hasMimes = !empty($this->mimes);
            @endphp

            @if($hasMaxFileSize)
                <p>{{ __('Up to :size', ['size' => Number::fileSize($this->maxFileSize * 1024)]) }}</p>
            @endif

            @if($hasMaxFileSize && $hasMimes)
                <span class="w-1 h-1 bg-zinc-500 dark:bg-white/70 rounded-full"></span>
            @endif

            @if($hasMimes)
                <p>{{ Str::upper($this->mimes) }}</p>
            @endif
        </div>

        @if(count($files) > 0)
            <div class="w-full mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($files as $file)
                    <flux:card class="w-full">
                        <div class="flex items-center gap-2">
                            @if($this->isImageMime($file['extension']))
                                <flux:icon.photo class="size-12" />
                            @else
                                <flux:icon.document class="size-12" />
                            @endif
                            <div>
                                <flux:heading>{{ $file['name'] }}</flux:heading>
                                <flux:subheading>{{ Number::fileSize($file['size']) }}</flux:subheading>
                            </div>
                            <flux:spacer />
                            <div>
                                <flux:button icon="x-mark" variant="ghost" @click="removeUpload('{{ $file['tmpFilename'] }}')" />
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('dropzone', ({ _this, multiple }) => {
            return ({
                isDragging: false,
                isDropped: false,
                isLoading: false,

                onDrop(e) {
                    this.isDropped = true
                    this.isDragging = false

                    const file = multiple ? e.dataTransfer.files : e.dataTransfer.files[0]

                    const args = ['upload', file, () => {
                        // Upload completed
                        this.isLoading = false
                    }, (error) => {
                        // An error occurred while uploading
                        console.log('livewire-dropzone upload error', error);
                    }, () => {
                        // Uploading is in progress
                        this.isLoading = true
                    }];

                    // Upload file(s)
                    multiple ? _this.uploadMultiple(...args) : _this.upload(...args)
                },
                onDragenter() {
                    this.isDragging = true
                },
                onDragleave() {
                    this.isDragging = false
                },
                onDragover() {
                    this.isDragging = true
                },
                removeUpload(tmpFilename) {
                    // Dispatch an event to remove the temporarily uploaded file
                    _this.dispatch('{{ $uuid }}:fileRemoved', { tmpFilename })
                },
            });
        })
    </script>
    @endscript
</div>
