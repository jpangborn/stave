<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The sidebar's Livewire components wrap Flux sidebar items in a root <div>. Flux
 * renders those items as inline-flex custom elements (ui-dropdown / ui-tooltip),
 * which shrink-wrap to their content unless they are flex items of the sidebar's
 * flex column — leaving an undersized hover and click target.
 */
/** @group browser */
it('gives Livewire sidebar items the same hit target width as plain sidebar items', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $widths = visit(route('dashboard'))->script(<<<'JS'
        (() => {
            const sidebar = document.querySelector('[data-flux-sidebar]');
            const width = (el) => el ? Math.round(el.getBoundingClientRect().width) : null;
            const items = () => [...sidebar.querySelectorAll('[data-flux-sidebar-item]')];
            const byLabel = (label) => items().find((el) => el.textContent.trim() === label);

            return {
                dashboard: width(byLabel('Dashboard')),
                notifications: width(byLabel('Notifications')),
                churchSwitcher: width(sidebar.querySelector('[data-flux-sidebar-profile]')),
                people: width(byLabel('People')),
                messages: width(byLabel('Messages')),
            };
        })()
    JS);

    expect($widths['dashboard'])->toBeGreaterThan(0)
        ->and($widths['notifications'])->toBe($widths['dashboard'])
        ->and($widths['churchSwitcher'])->toBe($widths['dashboard'])
        ->and($widths['people'])->toBeGreaterThan(0)
        ->and($widths['messages'])->toBe($widths['people']);
});
