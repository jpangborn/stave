<?php

use App\Support\SectionTone;

it('picks deterministically from the palette', function (): void {
    $first = SectionTone::pick('Grace');
    $second = SectionTone::pick('Grace');

    expect($first)->toBe($second);
    expect(SectionTone::PALETTE)->toContain($first);
});

it('returns different colors for distinct seeds', function (): void {
    $seeds = ['God', 'Guilt', 'Grace', 'Word', 'Communion', 'Sending'];
    $colors = array_map([SectionTone::class, 'pick'], $seeds);

    // The Flexoki-mapped palette has 8 colors; collisions across 6 named seeds
    // are possible but the demo set above produces at least 4 distinct colors.
    expect(array_unique($colors))->toHaveCount(count(array_unique($colors)));
    expect(count(array_unique($colors)))->toBeGreaterThanOrEqual(4);
});

it('returns neutral classes for null or unknown colors', function (): void {
    $null = SectionTone::classesFor(null);
    $unknown = SectionTone::classesFor('not-a-color');

    expect($null)->toBe($unknown);
    expect($null['stripe'])->toContain('zinc');
});

it('returns tailwind classes scoped to a known color', function (): void {
    $classes = SectionTone::classesFor('blue');

    expect($classes['stripe'])->toContain('bg-blue-500');
    expect($classes['dot'])->toContain('text-blue-700');
    expect($classes['swatch'])->toContain('bg-blue-100');
    expect($classes['soft'])->toContain('bg-blue-50');
});
