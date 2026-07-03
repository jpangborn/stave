<?php

test('widget renders title, slot content, and view-all link when href is given', function (): void {
    $view = $this->blade(
        '<x-dashboard.widget title="Upcoming" icon="calendar" href="/upcoming" link-label="See all">Body content</x-dashboard.widget>'
    );

    $view->assertSee('Upcoming')
        ->assertSee('Body content')
        ->assertSee('See all')
        ->assertSeeHtml('href="/upcoming"')
        ->assertSeeHtml('wire:navigate');
});

test('widget hides the view-all link when href is null', function (): void {
    $view = $this->blade(
        '<x-dashboard.widget title="Upcoming" icon="calendar">Body content</x-dashboard.widget>'
    );

    $view->assertDontSeeHtml('wire:navigate')
        ->assertDontSee('View all');
});

test('widget with isEmpty renders emptyMessage and not the slot content', function (): void {
    $view = $this->blade(
        '<x-dashboard.widget title="Upcoming" icon="calendar" :is-empty="true" empty-message="Nothing scheduled">Body content</x-dashboard.widget>'
    );

    $view->assertSee('Nothing scheduled')
        ->assertDontSee('Body content');
});

test('skeleton renders with animate-pulse and a bar per row', function (): void {
    $view = $this->blade(
        '<x-dashboard.widget-skeleton :rows="5" />'
    );

    $view->assertSeeHtml('animate-pulse');

    $rowBarCount = substr_count((string) $view, 'h-3 rounded bg-zinc-100 dark:bg-zinc-800');

    expect($rowBarCount)->toBe(5);
});
