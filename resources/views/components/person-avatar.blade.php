@props([
    'person',
    'size' => 'sm',
    'circle' => false,
])

<flux:avatar
    :name="$person->full_name"
    :src="$person->gravatar"
    :size="$size"
    :circle="$circle"
    color="auto"
    :color:seed="$person->id"
    {{ $attributes }}
/>
