@props([
    'kind',
    'size' => 'sm',
])

<flux:badge
    :color="$kind->color()"
    :icon="$kind->icon()"
    :size="$size"
    :title="$kind->label() . ' — ' . $kind->description()"
    {{ $attributes }}
>
    {{ $kind->label() }}
</flux:badge>
