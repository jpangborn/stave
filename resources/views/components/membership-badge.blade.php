@props([
    'status',
    'reason' => null,
    'size' => 'sm',
])

@php
    $title = $status->label() . ($reason ? ' — ' . $reason->label() : '');
@endphp

<flux:badge
    :color="$status->color()"
    :icon="$status->icon()"
    :size="$size"
    :title="$title"
    {{ $attributes }}
>
    {{ $status->label() }}
</flux:badge>
