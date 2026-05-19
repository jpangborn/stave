/**
 * iOS Add-to-Home-Screen coachmark.
 *
 * Shows a small banner to iOS Safari users (not already running as a PWA)
 * suggesting they install Stave to their home screen so they can receive
 * push notifications. Dismissals are stored in localStorage; users will not
 * see the banner again on this device unless they reset the flag.
 *
 * The "Show install instructions" link in settings dispatches a
 * `show-a2hs-coachmark` CustomEvent which force-shows the banner regardless
 * of platform — so a desktop user can preview what their phone-using
 * teammate sees.
 */

export const DISMISSED_KEY = 'stave_ios_a2hs_dismissed';
export const BANNER_ID = 'ios-a2hs-banner';

export function detectIOS() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

export function detectStandalone() {
    return (
        window.navigator.standalone === true ||
        (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
    );
}

function showBanner() {
    const banner = document.getElementById(BANNER_ID);
    if (banner) {
        banner.removeAttribute('hidden');
    }
}

function hideBanner() {
    const banner = document.getElementById(BANNER_ID);
    if (banner) {
        banner.setAttribute('hidden', '');
    }
}

/**
 * Show the coachmark only when running on iOS Safari, not standalone, and
 * not previously dismissed.
 *
 * @param {{ isIOS?: boolean, isStandalone?: boolean }} [overrides] Optional
 *   detection overrides — primarily for tests. Production callers should
 *   omit this argument and let the module sniff the real environment.
 */
export function maybeShowCoachmark(overrides = {}) {
    const isIOS = overrides.isIOS ?? detectIOS();
    const isStandalone = overrides.isStandalone ?? detectStandalone();

    if (!isIOS || isStandalone) {
        return;
    }
    if (localStorage.getItem(DISMISSED_KEY)) {
        return;
    }
    showBanner();
}

export function dismissCoachmark() {
    try {
        localStorage.setItem(DISMISSED_KEY, String(Date.now()));
    } catch (error) {
        // localStorage unavailable (private mode, quota); fall back to hiding only.
    }
    hideBanner();
}

export function resetCoachmarkDismissal() {
    try {
        localStorage.removeItem(DISMISSED_KEY);
    } catch (error) {
        // ignore
    }
}

export function forceShowCoachmark() {
    resetCoachmarkDismissal();
    showBanner();
}

function init() {
    maybeShowCoachmark();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
    init();
}

window.addEventListener('show-a2hs-coachmark', () => {
    forceShowCoachmark();
});

// Expose the API for the banner buttons (called via inline onclick) and tests.
window.StaveA2HS = {
    DISMISSED_KEY,
    BANNER_ID,
    detectIOS,
    detectStandalone,
    maybeShowCoachmark,
    dismissCoachmark,
    resetCoachmarkDismissal,
    forceShowCoachmark,
};
