<?php

use App\Enums\LiturgyElementType;
use App\Models\Service;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.public')] class extends Component {
    public ?Service $service = null;

    public function mount(?Service $service = null): void
    {
        $this->service = $service ?? Service::current();
        $this->service?->load(['liturgyElements.content', 'liturgyElements.assignee']);
    }

    /**
     * The service shaped for the follow-along view: `panels` are the
     * navigable elements (sections excluded), `sections` the movements
     * that hold them. Each panel knows its section, and the first panel
     * of a movement carries that section's "Entering" intro. A section is
     * recorded the moment it appears, so content-less sections (trailing or
     * consecutive) still show in the stepper; their tab just jumps to the
     * nearest panel since they own none.
     *
     * @return array{
     *     panels: array<int, array<string, mixed>>,
     *     sections: array<int, array{name: string, description: ?string, firstIndex: int}>
     * }
     */
    #[Computed]
    public function bulletin(): array
    {
        $panels = [];
        $sections = [];
        $currentSection = null;
        $pendingEntering = null;

        if (! $this->service) {
            return ['panels' => $panels, 'sections' => $sections];
        }

        foreach ($this->service->liturgyElements as $element) {
            if ($element->type === LiturgyElementType::SECTION) {
                $sections[] = [
                    'name' => $element->name,
                    'description' => $element->description,
                    'firstIndex' => count($panels),
                ];
                $currentSection = count($sections) - 1;
                $pendingEntering = ['name' => $element->name, 'description' => $element->description];

                continue;
            }

            $body = $element->hasContent() ? $element->getContentText() : null;
            $preacher = $element->type === LiturgyElementType::SERMON ? $element->assignee?->name : null;

            $panels[] = [
                'kindLabel' => $element->isReading() && $element->reading_type ? $element->reading_type->label() : $element->type->label(),
                'title' => $element->getDisplayTitle(),
                'description' => $element->description,
                'body' => $body,
                'isEmpty' => ! $body && ! $element->description && ! $preacher,
                'preacher' => $preacher,
                'song' => $element->isSong() && $element->hasContent() ? $element->content : null,
                'sectionIndex' => $currentSection ?? -1,
                'entering' => $pendingEntering,
            ];

            $pendingEntering = null;
        }

        return ['panels' => $panels, 'sections' => $sections];
    }
};
?>

@php
    $churchName = 'Reforming Truth Church';
    $panels = $this->bulletin['panels'];
    $sections = $this->bulletin['sections'];
@endphp

<div>
    @if (! $service)
        <div class="flex min-h-dvh flex-col items-center justify-center gap-5 bg-white p-8 text-center text-zinc-900">
            <span class="text-accent-content *:h-14 *:w-auto"><x-app-logo-icon /></span>
            <div>
                <h1 class="text-lg font-bold">{{ $churchName }}</h1>
                <p class="mt-2 text-sm text-zinc-500">No upcoming service is scheduled. Check back soon.</p>
            </div>
        </div>
    @else
        <div
            id="follow-along"
            x-data="{
                idx: 0,
                total: @js(count($panels)),
                sectionStarts: @js(array_column($sections, 'firstIndex')),
                panelSection: @js(array_column($panels, 'sectionIndex')),
                scale: 1,
                dim: false,
                copied: false,
                startX: null,

                init() {
                    try {
                        const stored = localStorage.getItem('bulletin.dim');
                        this.dim = stored !== null ? stored === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    } catch { }
                    this.$watch('idx', () => {
                        this.centerStep();
                        this.$refs.content?.scrollTo(0, 0);
                    });
                    this.$nextTick(() => this.centerStep());
                },
                go(n) {
                    if (this.total) this.idx = Math.max(0, Math.min(this.total - 1, n));
                },
                next() { this.go(this.idx + 1) },
                prev() { this.go(this.idx - 1) },
                jump(s) { this.go(this.sectionStarts[s]) },
                bump(d) { this.scale = Math.min(1.5, Math.max(0.82, Math.round((this.scale + d) * 100) / 100)) },
                toggleDim() {
                    this.dim = ! this.dim;
                    try { localStorage.setItem('bulletin.dim', this.dim); } catch { }
                },
                copyLink() {
                    navigator.clipboard?.writeText(window.location.href).then(() => {
                        this.copied = true;
                        clearTimeout(this._toast);
                        this._toast = setTimeout(() => this.copied = false, 1600);
                    });
                },
                pointerDown(e) { this.startX = e.clientX },
                pointerUp(e) {
                    if (this.startX === null) return;
                    const dx = e.clientX - this.startX;
                    this.startX = null;
                    if (dx < -45) this.next();
                    else if (dx > 45) this.prev();
                },
                centerStep() {
                    const stepper = this.$refs.stepper;
                    const s = this.panelSection[this.idx];
                    if (! stepper || s === undefined || s < 0) return;
                    const step = stepper.children[s];
                    if (! step) return;
                    const target = step.offsetLeft - (stepper.clientWidth - step.offsetWidth) / 2;
                    stepper.scrollTo({
                        left: Math.max(0, Math.min(target, stepper.scrollWidth - stepper.clientWidth)),
                        behavior: 'instant',
                    });
                },
            }"
            :class="dim && 'dark'"
            :style="'--reader-scale: ' + scale"
            @keydown.arrow-right.window.prevent="next()"
            @keydown.arrow-left.window.prevent="prev()"
            class="flex h-dvh flex-col bg-white text-zinc-900 transition-colors duration-[350ms] dark:bg-zinc-950 dark:text-zinc-100"
        >
            {{-- Header --}}
            <header class="flex-none border-b border-zinc-200 bg-zinc-50 transition-colors duration-[350ms] dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 px-4.5 pt-4 pb-2.5 md:px-10">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex-none text-accent-content *:h-7 *:w-auto md:*:h-8"><x-app-logo-icon /></span>
                        <div class="min-w-0 leading-tight">
                            <div class="truncate text-[11.5px] font-bold md:text-[13px]">{{ $churchName }}</div>
                            <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">{{ $service->date->format('D, M j, Y') }}</div>
                        </div>
                    </div>
                    <div class="relative flex flex-none items-center gap-1.5">
                        <button
                            type="button"
                            @click="bump(-0.12)"
                            :disabled="scale <= 0.82"
                            aria-label="Smaller text"
                            class="size-[30px] rounded-lg border border-zinc-200 text-[11px] font-bold text-zinc-500 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-400"
                        >A−</button>
                        <button
                            type="button"
                            @click="bump(0.12)"
                            :disabled="scale >= 1.5"
                            aria-label="Larger text"
                            class="size-[30px] rounded-lg border border-zinc-200 text-[14px] font-bold text-zinc-500 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-400"
                        >A+</button>
                        <button
                            type="button"
                            @click="toggleDim()"
                            aria-label="Toggle dim mode"
                            class="flex size-[30px] items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
                        >
                            <flux:icon.sun x-show="dim" style="display: none" class="size-4" />
                            <flux:icon.moon x-show="! dim" class="size-4" />
                        </button>
                        <button
                            type="button"
                            @click="copyLink()"
                            aria-label="Copy link"
                            class="flex size-[30px] items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
                        >
                            <flux:icon.link class="size-4" />
                        </button>
                        <div
                            x-show="copied"
                            x-transition.opacity
                            style="display: none"
                            class="absolute top-[38px] right-0 z-20 rounded-md bg-zinc-900 px-2.5 py-1.5 text-[11px] font-semibold whitespace-nowrap text-white dark:bg-zinc-100 dark:text-zinc-900"
                        >Link copied</div>
                    </div>
                </div>

                @if (count($sections))
                    <div class="relative">
                        <div x-ref="stepper" class="flex overflow-x-auto px-3 [scrollbar-width:none] md:px-7 [&::-webkit-scrollbar]:hidden">
                            @foreach ($sections as $s => $section)
                                <button
                                    type="button"
                                    @click="jump({{ $s }})"
                                    wire:key="section-step-{{ $s }}"
                                    class="min-w-[84px] flex-1 cursor-pointer px-1.5 pt-1 md:min-w-[120px]"
                                >
                                    <div
                                        class="truncate pb-1.5 text-center text-[11px] font-bold tracking-[.02em]"
                                        :class="panelSection[idx] === {{ $s }} ? 'text-accent-content' : ({{ $s }} < panelSection[idx] ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 dark:text-zinc-400')"
                                    >{{ $section['name'] }}</div>
                                    <div
                                        class="h-[3px] rounded-full"
                                        :class="panelSection[idx] === {{ $s }} ? 'bg-accent' : ({{ $s }} < panelSection[idx] ? 'bg-emerald-300 dark:bg-emerald-500/50' : 'bg-zinc-200 dark:bg-zinc-700')"
                                    ></div>
                                </button>
                            @endforeach
                        </div>
                        <div class="pointer-events-none absolute inset-y-0 left-0 w-5 bg-gradient-to-r from-zinc-50 to-transparent dark:from-zinc-900"></div>
                        <div class="pointer-events-none absolute inset-y-0 right-0 w-5 bg-gradient-to-l from-zinc-50 to-transparent dark:from-zinc-900"></div>
                    </div>
                @endif
            </header>

            {{-- Content --}}
            <main class="relative min-h-0 flex-1 touch-pan-y" @pointerdown="pointerDown" @pointerup="pointerUp">
                @if (count($panels))
                    <button
                        type="button"
                        @click="prev()"
                        aria-label="Previous element"
                        :class="idx === 0 && 'pointer-events-none'"
                        class="group absolute inset-y-0 left-0 z-10 flex w-14 items-center justify-start pl-3 md:w-[18%]"
                    >
                        <flux:icon.chevron-left class="hidden size-8 text-zinc-400 opacity-0 transition-opacity duration-150 group-hover:opacity-50 md:block" />
                    </button>
                    <button
                        type="button"
                        @click="next()"
                        aria-label="Next element"
                        :class="idx >= total - 1 && 'pointer-events-none'"
                        class="group absolute inset-y-0 right-0 z-10 flex w-14 items-center justify-end pr-3 md:w-[18%]"
                    >
                        <flux:icon.chevron-right class="hidden size-8 text-zinc-400 opacity-0 transition-opacity duration-150 group-hover:opacity-50 md:block" />
                    </button>
                @endif

                <div x-ref="content" class="absolute inset-0 overflow-y-auto px-7 py-6 md:px-14 md:py-11">
                    <div class="mx-auto max-w-full md:max-w-[660px]">
                        @forelse ($panels as $i => $panel)
                            <article
                                @class(['hidden' => $i !== 0])
                                :class="{ hidden: idx !== {{ $i }} }"
                                wire:key="panel-{{ $i }}"
                            >
                                @if ($panel['entering'] && $panel['entering']['description'])
                                    <div class="mb-6.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                                        <div class="mb-1.5 text-[10.5px] font-bold tracking-[.12em] text-accent-content uppercase">Entering · {{ $panel['entering']['name'] }}</div>
                                        <p class="text-sm leading-relaxed text-emerald-950 dark:text-zinc-300">{{ $panel['entering']['description'] }}</p>
                                    </div>
                                @endif

                                <div class="mb-2 text-[11px] font-bold tracking-[.1em] text-zinc-500 uppercase dark:text-zinc-400">{{ $panel['kindLabel'] }}</div>
                                <h2 class="text-[27px] leading-[1.12] font-extrabold tracking-tight md:text-[38px]">{{ $panel['title'] }}</h2>

                                @if ($panel['body'])
                                    <div class="bulletin-body mt-5">{!! $panel['body'] !!}</div>
                                @elseif ($panel['description'])
                                    <p class="bulletin-body mt-5">{{ $panel['description'] }}</p>
                                @elseif ($panel['isEmpty'])
                                    <div class="mt-6 rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        No content was attached to this element yet.
                                    </div>
                                @endif

                                @if ($panel['preacher'])
                                    <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                                        <span class="text-[13px] text-zinc-500 dark:text-zinc-400">Preaching</span>
                                        <span class="text-[13.5px] font-bold">{{ $panel['preacher'] }}</span>
                                    </div>
                                @endif

                                @if ($panel['song'] && ($panel['song']->authors || $panel['song']->ccli_number || $panel['song']->copyright))
                                    <div class="mt-6 border-t border-zinc-200 pt-4 text-xs leading-relaxed text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                        @if ($panel['song']->authors)
                                            <div>{{ $panel['song']->authors }}</div>
                                        @endif
                                        @if ($panel['song']->copyright || $panel['song']->ccli_number)
                                            <div>{{ collect([$panel['song']->copyright, $panel['song']->ccli_number ? 'CCLI Song #'.$panel['song']->ccli_number : null])->filter()->implode(' · ') }}</div>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                This service doesn't have any elements yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            @if (count($panels))
                <footer class="flex flex-none items-center gap-3.5 border-t border-zinc-200 px-4.5 pt-3 pb-4 transition-colors duration-[350ms] md:px-10 dark:border-zinc-800">
                    <button
                        type="button"
                        @click="prev()"
                        :disabled="idx === 0"
                        aria-label="Previous element"
                        class="flex size-[46px] flex-none items-center justify-center rounded-full border border-zinc-200 disabled:text-zinc-300 dark:border-zinc-700 dark:disabled:text-zinc-600"
                    >
                        <flux:icon.chevron-left class="size-5" />
                    </button>
                    <div class="min-w-0 flex-1 text-center">
                        <div class="text-[12.5px] font-bold"><span x-text="idx + 1">1</span> of {{ count($panels) }}</div>
                        <div class="text-[10.5px] text-zinc-500 dark:text-zinc-400">
                            <span class="md:hidden">swipe or tap sides</span>
                            <span class="hidden md:inline">← → arrow keys, or click the sides</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="next()"
                        :disabled="idx >= total - 1"
                        aria-label="Next element"
                        class="flex size-[46px] flex-none items-center justify-center rounded-full bg-accent text-white disabled:bg-zinc-200 disabled:text-zinc-400 dark:disabled:bg-zinc-800 dark:disabled:text-zinc-500"
                    >
                        <flux:icon.chevron-right class="size-5" />
                    </button>
                </footer>
            @endif
        </div>
    @endif
</div>
