@props([
    'wireModel',
    'value' => null,
    'placeholder' => 'Untitled',
    'tag' => 'span',
    'inputType' => 'text',
])

@php
    $display = $value ?? '';
    $base = $attributes->get('class', '');
    $hoverClasses = 'cursor-text rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors';
    $padClasses = 'px-1.5 -mx-1.5';
@endphp

<div x-data="{ editing: false }" class="inline-block w-full max-w-full" wire:key="inline-{{ $wireModel }}">
    <{{ $tag }}
        x-show="!editing"
        x-cloak
        @click="editing = true; $nextTick(() => { $refs.inlineInput.focus(); $refs.inlineInput.select(); })"
        @keydown.enter.prevent="editing = true; $nextTick(() => { $refs.inlineInput.focus(); $refs.inlineInput.select(); })"
        tabindex="0"
        title="Click to rename"
        class="{{ $base }} {{ $padClasses }} {{ $hoverClasses }} block max-w-full truncate"
    >
        @if ($display !== '')
            {{ $display }}
        @else
            <span class="italic text-zinc-400 dark:text-zinc-500">{{ $placeholder }}</span>
        @endif
    </{{ $tag }}>

    <input
        type="{{ $inputType }}"
        x-show="editing"
        x-ref="inlineInput"
        x-cloak
        wire:model.live.blur="{{ $wireModel }}"
        placeholder="{{ $placeholder }}"
        @blur="editing = false"
        @keydown.enter.prevent="$refs.inlineInput.blur()"
        @keydown.escape.prevent="$refs.inlineInput.blur()"
        class="{{ $base }} {{ $padClasses }} block w-full max-w-full bg-transparent border-b border-accent outline-none"
    />
</div>
