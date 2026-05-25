@props([
    'role',
    'short' => false,
    'size' => 'sm',
])

<flux:badge
    :color="$role->color()"
    :icon="$role->icon()"
    :size="$size"
    :title="$role->description()"
    {{ $attributes }}
>
    {{ $short ? $role->shortLabel() : $role->label() }}
</flux:badge>
