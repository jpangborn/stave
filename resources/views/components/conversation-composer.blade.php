@props([
    'editorModel',
    'prayerModel',
    'prayerActive' => false,
    'newImageModel',
    'newAttachmentModel',
    'pendingImages',
    'pendingAttachments',
    'removeImageAction' => 'removePendingImage',
    'removeAttachmentAction' => 'removePendingAttachment',
    'submitAction',
    'submitLabel' => 'Send',
    'cancelAction' => null,
    'editing' => false,
    'existingAttachments' => null,
    'removeExistingAttachmentAction' => null,
    'testPrefix' => 'composer',
])

<form
    wire:submit="{{ $submitAction }}"
    x-on:keydown.enter="if ($event.metaKey || $event.ctrlKey) { $event.preventDefault(); $el.requestSubmit() }"
>
    <input type="file" x-ref="imageInput" wire:model="{{ $newImageModel }}" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" data-test="{{ $testPrefix }}-image-input" />
    <input type="file" x-ref="attachInput" wire:model="{{ $newAttachmentModel }}" accept=".pdf,.md,.txt,audio/*" class="hidden" data-test="{{ $testPrefix }}-attach-input" />

    <flux:composer wire:model="{{ $editorModel }}" label="{{ $editing ? 'Edit message' : 'Reply' }}" label:sr-only placeholder="Write a reply…  (use @ to mention a member)">
        <x-slot name="input">
            <flux:editor
                variant="borderless"
                toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                class="**:data-[slot=content]:min-h-[100px]!"
            />

            @if ($pendingImages->isNotEmpty() || $pendingAttachments->isNotEmpty() || ($existingAttachments && $existingAttachments->isNotEmpty()))
                <div class="mt-2 space-y-2" data-test="{{ $testPrefix }}-pending">
                    @if ($pendingImages->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($pendingImages as $image)
                                <div
                                    class="group/pending relative size-20 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700"
                                    wire:key="{{ $testPrefix }}-pending-image-{{ $image->id }}"
                                    data-test="pending-image"
                                >
                                    <img src="{{ $image->url }}" alt="{{ $image->original_name }}" class="size-full object-cover" />
                                    <button
                                        type="button"
                                        wire:click="{{ $removeImageAction }}({{ $image->id }})"
                                        class="absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-zinc-900/70 text-white opacity-0 transition-opacity hover:bg-zinc-900 focus-visible:opacity-100 group-hover/pending:opacity-100"
                                        aria-label="Remove image"
                                        data-test="pending-image-remove"
                                    >
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($pendingAttachments->isNotEmpty() || ($existingAttachments && $existingAttachments->isNotEmpty()))
                        <div class="flex flex-wrap gap-1.5">
                            @if ($existingAttachments)
                                @foreach ($existingAttachments as $attachment)
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 py-1 pl-2.5 pr-1 text-xs dark:border-zinc-700 dark:bg-zinc-800"
                                        wire:key="{{ $testPrefix }}-existing-attachment-{{ $attachment->id }}"
                                        data-test="existing-attachment"
                                    >
                                        <flux:icon.paper-clip variant="micro" class="text-zinc-500" />
                                        <span class="max-w-[16ch] truncate font-medium text-zinc-700 dark:text-zinc-200">{{ $attachment->original_name }}</span>
                                        <button
                                            type="button"
                                            wire:click="{{ $removeExistingAttachmentAction }}({{ $attachment->id }})"
                                            class="grid size-4 place-items-center rounded-full text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700"
                                            aria-label="Remove attachment"
                                            data-test="existing-attachment-remove"
                                        >
                                            <flux:icon.x-mark variant="micro" class="size-3" />
                                        </button>
                                    </span>
                                @endforeach
                            @endif

                            @foreach ($pendingAttachments as $attachment)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 py-1 pl-2.5 pr-1 text-xs dark:border-zinc-700 dark:bg-zinc-800"
                                    wire:key="{{ $testPrefix }}-pending-attachment-{{ $attachment->id }}"
                                    data-test="pending-attachment"
                                >
                                    <flux:icon.paper-clip variant="micro" class="text-zinc-500" />
                                    <span class="max-w-[16ch] truncate font-medium text-zinc-700 dark:text-zinc-200">{{ $attachment->original_name }}</span>
                                    <button
                                        type="button"
                                        wire:click="{{ $removeAttachmentAction }}({{ $attachment->id }})"
                                        class="grid size-4 place-items-center rounded-full text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700"
                                        aria-label="Remove attachment"
                                        data-test="pending-attachment-remove"
                                    >
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <flux:error name="{{ $newImageModel }}" />
            <flux:error name="{{ $newAttachmentModel }}" />
        </x-slot>

        <x-slot name="footer">
            <flux:tooltip content="Attach image">
                <button
                    type="button"
                    x-on:click="$refs.imageInput.click()"
                    class="inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-semibold text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                    aria-label="Attach image"
                    data-test="{{ $testPrefix }}-image-button"
                >
                    <flux:icon.photo variant="micro" wire:loading.remove wire:target="{{ $newImageModel }}" />
                    <flux:icon.arrow-path variant="micro" class="animate-spin" wire:loading wire:target="{{ $newImageModel }}" />
                </button>
            </flux:tooltip>

            <flux:tooltip content="Attach file">
                <button
                    type="button"
                    x-on:click="$refs.attachInput.click()"
                    class="inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-semibold text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                    aria-label="Attach file"
                    data-test="{{ $testPrefix }}-attach-button"
                >
                    <flux:icon.paper-clip variant="micro" wire:loading.remove wire:target="{{ $newAttachmentModel }}" />
                    <flux:icon.arrow-path variant="micro" class="animate-spin" wire:loading wire:target="{{ $newAttachmentModel }}" />
                </button>
            </flux:tooltip>

            <button
                type="button"
                wire:click="$toggle('{{ $prayerModel }}')"
                aria-pressed="{{ $prayerActive ? 'true' : 'false' }}"
                @class([
                    'inline-flex h-7 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold transition-colors',
                    'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-200' => $prayerActive,
                    'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => ! $prayerActive,
                ])
                data-test="{{ $testPrefix }}-prayer-toggle"
                @if ($prayerActive) data-test-active="true" @endif
            >
                <flux:icon.hand-raised variant="micro" />
                <span class="lg:hidden">Prayer</span>
                <span class="hidden lg:inline">{{ $prayerActive ? 'Sending as prayer' : 'Mark as prayer' }}</span>
            </button>

            <flux:tooltip content="Mention a member">
                <button
                    type="button"
                    x-on:click="$root.querySelector('ui-editor')?.editor?.chain().focus().insertContent('@').run()"
                    class="inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-semibold text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                    data-test="{{ $testPrefix }}-mention"
                >
                    <flux:icon.at-symbol variant="micro" />
                    <span class="lg:hidden">@</span>
                    <span class="hidden lg:inline">Mention</span>
                </button>
            </flux:tooltip>

            <div class="ms-auto flex items-center gap-2">
                <span class="hidden items-center gap-1 text-xs text-zinc-400 lg:inline-flex" data-test="{{ $testPrefix }}-shortcut-hint">
                    <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">⌘</kbd>
                    <kbd class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">↵</kbd>
                    to send
                </span>
                @if ($cancelAction)
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        wire:click="{{ $cancelAction }}"
                        data-test="{{ $testPrefix }}-cancel"
                    >Cancel</flux:button>
                @else
                    <button
                        type="button"
                        x-on:click="collapse()"
                        class="grid size-7 place-items-center rounded-md text-zinc-500 hover:bg-zinc-100 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800"
                        aria-label="Collapse composer"
                        data-test="{{ $testPrefix }}-collapse"
                    >
                        <flux:icon.chevron-down variant="micro" />
                    </button>
                @endif
                <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane" wire:loading.attr="disabled">{{ $submitLabel }}</flux:button>
            </div>
        </x-slot>
    </flux:composer>
    <flux:error name="{{ $editorModel }}" />
</form>
