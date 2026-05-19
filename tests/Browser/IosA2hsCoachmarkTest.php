<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Contract for the iOS Add-to-Home-Screen coachmark banner.
 *
 * The banner is shown only when:
 *   - the visitor is on iOS Safari (UA contains iPhone/iPad/iPod);
 *   - the app is not running in standalone (installed) mode; and
 *   - the user has not already dismissed it on this device.
 *
 * Detection is JS-only (no UA sniffing server-side). The module exposes
 * `window.StaveA2HS.maybeShowCoachmark({isIOS, isStandalone})` so we can
 * lock in the contract here without fighting Playwright's userAgent
 * spoofing semantics.
 */

/** @group browser */
it('shows the banner on iOS Safari when not standalone and not dismissed', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->script(<<<'JS'
        try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}
        window.StaveA2HS.maybeShowCoachmark({ isIOS: true, isStandalone: false });
    JS);

    $page
        ->assertVisible('#ios-a2hs-banner')
        ->assertSee('Install Stave for notifications');
});

/** @group browser */
it('persists dismissal via localStorage so the banner stays hidden after reload', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $flagAfterDismiss = $page->script(<<<'JS'
        (() => {
            try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}
            window.StaveA2HS.maybeShowCoachmark({ isIOS: true, isStandalone: false });
            window.StaveA2HS.dismissCoachmark();
            return JSON.stringify(localStorage.getItem('stave_ios_a2hs_dismissed'));
        })()
    JS);
    expect($flagAfterDismiss)->not->toBe('null');

    // Reload the same page/context so localStorage persists, then assert the
    // banner stays hidden because the dismissal flag survived.
    $page->refresh();
    $hiddenAttr = $page->script(<<<'JS'
        (() => {
            window.StaveA2HS.maybeShowCoachmark({ isIOS: true, isStandalone: false });
            return JSON.stringify(document.getElementById('ios-a2hs-banner').getAttribute('hidden'));
        })()
    JS);
    expect($hiddenAttr)->not->toBe('null');
});

/** @group browser */
it('keeps the banner hidden on non-iOS user agents', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $hiddenAttr = $page->script(<<<'JS'
        (() => {
            try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}
            window.StaveA2HS.maybeShowCoachmark({ isIOS: false, isStandalone: false });
            return JSON.stringify(document.getElementById('ios-a2hs-banner').getAttribute('hidden'));
        })()
    JS);
    expect($hiddenAttr)->not->toBe('null');
});

/** @group browser */
it('keeps the banner hidden when running standalone on iOS', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $hiddenAttr = $page->script(<<<'JS'
        (() => {
            try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}
            window.StaveA2HS.maybeShowCoachmark({ isIOS: true, isStandalone: true });
            return JSON.stringify(document.getElementById('ios-a2hs-banner').getAttribute('hidden'));
        })()
    JS);
    expect($hiddenAttr)->not->toBe('null');
});

/** @group browser */
it('force-shows the banner via the show-a2hs-coachmark event regardless of platform', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->script(<<<'JS'
        try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}
        localStorage.setItem('stave_ios_a2hs_dismissed', String(Date.now()));
        window.dispatchEvent(new CustomEvent('show-a2hs-coachmark'));
    JS);

    $page->assertVisible('#ios-a2hs-banner');

    $flagAfterForceShow = $page->script(
        '(() => JSON.stringify(localStorage.getItem("stave_ios_a2hs_dismissed")))()'
    );
    expect($flagAfterForceShow)->toBe('null');

    // Defensive cleanup: ensure the key is cleared regardless of whether the
    // force-show handler ran, so this test cannot leak state to others.
    $page->script("try { localStorage.removeItem('stave_ios_a2hs_dismissed'); } catch (e) {}");
});
