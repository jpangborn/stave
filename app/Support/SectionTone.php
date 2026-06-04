<?php

namespace App\Support;

/**
 * Tonal color palette for liturgy section headers.
 *
 * Mirrors Flux UI's avatar color selection algorithm (crc32 of a seed mapped
 * to a color in an ordered palette) so the visual language stays consistent.
 *
 * @see vendor/livewire/flux/stubs/resources/views/flux/avatar/index.blade.php
 */
class SectionTone
{
    /**
     * Ordered palette. Do not reorder — the index is used by the hash and
     * reordering would shift every existing section's color.
     *
     * @var list<string>
     */
    public const PALETTE = [
        'red', 'orange', 'yellow', 'green',
        'cyan', 'blue', 'purple', 'pink',
    ];

    public static function pick(string $seed): string
    {
        return self::PALETTE[crc32($seed) % count(self::PALETTE)];
    }

    /**
     * Tailwind class names for a given tone. Returns neutral zinc classes
     * when the color is null or not in the palette.
     *
     * @return array{stripe:string, dot:string, swatch:string, soft:string}
     */
    public static function classesFor(?string $color): array
    {
        if ($color === null || ! in_array($color, self::PALETTE, true)) {
            return [
                'stripe' => 'bg-zinc-300 dark:bg-zinc-600',
                'dot' => 'text-zinc-600 dark:text-zinc-300',
                'swatch' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                'soft' => 'bg-zinc-50 dark:bg-zinc-900',
            ];
        }

        return [
            'stripe' => "bg-{$color}-500 dark:bg-{$color}-400",
            'dot' => "text-{$color}-700 dark:text-{$color}-300",
            'swatch' => "bg-{$color}-100 text-{$color}-700 dark:bg-{$color}-900 dark:text-{$color}-300",
            'soft' => "bg-{$color}-50 dark:bg-{$color}-900",
        ];
    }
}
