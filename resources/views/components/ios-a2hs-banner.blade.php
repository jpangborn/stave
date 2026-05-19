{{--
    iOS Add-to-Home-Screen coachmark banner.
    Rendered hidden by default; the `ios-a2hs.js` module reveals it when the
    visitor is on iOS Safari, not already running standalone, and has not
    previously dismissed it. Buttons call `window.StaveA2HS.dismissCoachmark()`.
--}}
<div
    id="ios-a2hs-banner"
    role="region"
    aria-labelledby="ios-a2hs-banner-heading"
    aria-live="polite"
    hidden
    class="fixed inset-x-0 bottom-0 z-50 px-3 pb-[env(safe-area-inset-bottom)]"
>
    <div
        class="mx-auto mb-3 max-w-md rounded-2xl border border-zinc-200/80 bg-white/95 p-4 shadow-lg backdrop-blur dark:border-zinc-700/80 dark:bg-zinc-900/95"
    >
        <div class="flex items-start gap-3">
            <img
                src="/apple-touch-icon.png"
                alt=""
                aria-hidden="true"
                class="size-10 shrink-0 rounded-lg"
            />

            <div class="min-w-0 flex-1">
                <p id="ios-a2hs-banner-heading" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Install Stave for notifications') }}
                </p>
                <p id="ios-a2hs-banner-body" class="mt-1 flex flex-wrap items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('Tap the share button') }}</span>
                    <flux:icon.arrow-up-on-square class="inline size-4 text-zinc-500 dark:text-zinc-400" />
                    <span>{{ __('below, then') }}</span>
                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ __('Add to Home Screen') }}</span>
                    <span>.</span>
                </p>
            </div>
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <flux:button
                size="sm"
                variant="ghost"
                type="button"
                onclick="window.StaveA2HS && window.StaveA2HS.dismissCoachmark()"
            >
                {{ __('Not now') }}
            </flux:button>

            <flux:button
                size="sm"
                variant="primary"
                type="button"
                onclick="window.StaveA2HS && window.StaveA2HS.dismissCoachmark()"
            >
                {{ __('Got it') }}
            </flux:button>
        </div>
    </div>
</div>
