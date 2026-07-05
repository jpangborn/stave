{{--
    Stave private-beta landing page. Static, stateless marketing placeholder served at `/`.
    Colours use the app's tokens: `emerald-*` is aliased to the brand "pine" green in app.css,
    and `taupe-*` is the warm-neutral palette. All "Sign in" CTAs point at the local login route.
--}}
<x-layouts.public title="Stave — worship service planning">
    <div class="flex min-h-screen flex-col">

        {{-- Header --}}
        <header class="sticky top-0 z-10 border-b border-taupe-200 bg-white">
            <div class="mx-auto flex h-[66px] max-w-[1120px] items-center justify-between px-10">
                <div class="flex items-center gap-[11px]">
                    <span class="flex size-9 items-center justify-center rounded-[9px] border border-emerald-100 bg-emerald-50 text-emerald-700">
                        <x-app-logo-icon class="h-6 w-5" />
                    </span>
                    <span class="text-[20px] font-extrabold tracking-[-0.02em] text-taupe-950">Stave</span>
                    <span class="rounded-full border border-taupe-200 bg-taupe-100 px-2 py-[3px] text-[10.5px] font-semibold uppercase tracking-[0.04em] text-taupe-600">Private Beta</span>
                </div>
                <div class="flex items-center gap-[22px]">
                    <a href="#features" class="text-[13.5px] font-medium text-taupe-700 transition-colors hover:text-emerald-700">Features</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-[7px] rounded-lg border border-emerald-700 bg-emerald-600 px-4 py-[9px] text-[13.5px] font-semibold text-white shadow-[0_1px_2px_rgba(19,35,22,0.3)] transition-colors hover:bg-emerald-700">
                        Sign in
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                </div>
            </div>
        </header>

        {{-- Hero --}}
        <section class="relative overflow-hidden border-b border-taupe-200 bg-gradient-to-b from-white to-taupe-50">
            <x-app-logo-icon class="pointer-events-none absolute left-1/2 top-[34px] w-[560px] -translate-x-1/2 text-taupe-700 opacity-5" stroke-width="3.2" />
            <div class="relative mx-auto max-w-[1120px] px-10 pb-[76px] pt-[82px] text-center">
                <div class="mb-[26px] inline-flex items-center gap-[7px] rounded-full border border-emerald-100 bg-emerald-50 px-[13px] py-1.5 text-[12px] font-semibold text-emerald-700">
                    <span class="size-[7px] rounded-full bg-emerald-600"></span>
                    Now inviting churches to the beta
                </div>
                <h1 class="mx-auto max-w-[760px] text-[40px] font-extrabold leading-[1.05] tracking-[-0.033em] text-taupe-950 min-[820px]:text-[53px]">One calm place to hold the whole service together.</h1>
                <p class="mx-auto mt-6 max-w-[580px] text-[17.5px] leading-[1.55] text-taupe-700">Stave keeps your liturgy, songs, scriptures, prayer, and people in one warm, unhurried workspace — so Sunday takes care of itself.</p>
                <div class="mt-[34px] flex flex-wrap items-center justify-center gap-[14px]">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-[9px] border border-emerald-700 bg-emerald-600 px-6 py-[13px] text-[15px] font-semibold text-white shadow-[0_1px_2px_rgba(19,35,22,0.3)] transition-colors hover:bg-emerald-700">
                        Sign in to Stave
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                    <a href="#features" class="px-1 py-[13px] text-[15px] font-semibold text-taupe-700 transition-colors hover:text-emerald-700">See what's inside</a>
                </div>
            </div>
        </section>

        {{-- Heritage strip --}}
        <section class="border-b border-taupe-200 bg-taupe-50">
            <div class="mx-auto flex max-w-[1120px] flex-wrap items-center justify-center gap-[18px] px-10 py-[26px] text-center">
                <x-app-logo-icon class="h-[31px] w-[26px] shrink-0 text-taupe-500" stroke-width="7" />
                <p class="max-w-[720px] text-[14px] leading-[1.55] text-taupe-700">A <strong class="font-bold text-taupe-900">stave</strong> is the five lines that hold a hymn together — and a <strong class="font-bold text-taupe-900">stave church</strong> is the old Norwegian timber church, built tier upon tier from many parts into one standing whole. The name holds both.</p>
            </div>
        </section>

        <div id="features" class="scroll-mt-20"></div>

        {{-- Feature 1: Liturgy Management --}}
        <section class="border-b border-taupe-200">
            <div class="mx-auto grid max-w-[1120px] grid-cols-1 items-center gap-9 px-10 py-[66px] min-[820px]:grid-cols-2 min-[820px]:gap-14">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 text-[12px] font-bold uppercase tracking-[0.06em] text-emerald-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]" aria-hidden="true"><path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" /></svg>
                        Liturgy Management
                    </div>
                    <h2 class="mb-3.5 text-[31px] font-bold leading-[1.15] tracking-[-0.02em] text-taupe-950">Build the order of service, element by element.</h2>
                    <p class="mb-[22px] text-[15.5px] leading-[1.6] text-taupe-700">Start from a reusable template, drop in songs and readings, assign the people leading each part, and print a bulletin — all from one running order.</p>
                    <div class="flex flex-col gap-[13px]">
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Songs.</strong> A searchable library with lyrics, CCLI numbers, sheet PDFs and recordings.</span>
                        </div>
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Readings.</strong> Scripture and readings, classified and ready to slot into any service.</span>
                        </div>
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Services.</strong> Every Sunday built from a template, with assignees and section notes.</span>
                        </div>
                    </div>
                </div>
                <div class="order-last overflow-hidden rounded-xl border border-taupe-200 bg-white shadow-[0_10px_30px_-18px_rgba(33,30,26,0.3)] min-[820px]:order-none">
                    <div class="border-b border-taupe-200 bg-taupe-50 px-[18px] py-[14px]">
                        <div class="text-[14px] font-bold text-taupe-950">Sunday Morning Service</div>
                        <div class="mt-0.5 text-[11.5px] text-taupe-500">Sun, May 3, 2026 · Service Order</div>
                    </div>
                    <div class="px-1.5 py-2">
                        <div class="px-3 py-[9px]">
                            <div class="text-[13px] font-bold text-taupe-900">God</div>
                            <div class="mt-0.5 text-[11px] leading-snug text-taupe-500">We begin with a proclamation of God — his character and his actions.</div>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-taupe-500" aria-hidden="true"><path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" /></svg>
                            <a href="#features" class="text-[13px] font-semibold text-emerald-600 underline underline-offset-2">In Christ Alone</a>
                            <span class="ml-auto text-[10.5px] text-taupe-400">J. Pangborn</span>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2">
                            <flux:icon.book-open-text class="size-4 shrink-0 text-taupe-500" />
                            <a href="#features" class="text-[13px] font-semibold text-emerald-600 underline underline-offset-2">Psalm 100</a>
                            <span class="ml-auto text-[10.5px] text-taupe-400">Reading</span>
                        </div>
                        <div class="px-3 pb-1.5 pt-[9px]">
                            <div class="text-[13px] font-bold text-taupe-900">Grace</div>
                        </div>
                        <div class="flex items-center gap-2.5 px-3 py-2">
                            <flux:icon.lectern class="size-4 shrink-0 text-taupe-500" />
                            <span class="text-[13px] font-semibold text-taupe-900">Sermon — The Grace of God</span>
                            <span class="ml-auto text-[10.5px] text-taupe-400">Pastor</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Feature 2: Pastoral Care (mockup left / text right on desktop) --}}
        <section class="border-b border-taupe-200 bg-taupe-50">
            <div class="mx-auto grid max-w-[1120px] grid-cols-1 items-center gap-9 px-10 py-[66px] min-[820px]:grid-cols-2 min-[820px]:gap-14">
                <div class="order-last overflow-hidden rounded-xl border border-taupe-200 bg-white shadow-[0_10px_30px_-18px_rgba(33,30,26,0.3)] min-[820px]:order-none">
                    <div class="flex items-center justify-between border-b border-taupe-200 px-[18px] py-[14px]">
                        <div class="text-[14px] font-bold text-taupe-950">Prayer Requests</div>
                        <span class="rounded-full border border-emerald-100 bg-emerald-50 px-[9px] py-[3px] text-[10.5px] font-semibold text-emerald-700">This week</span>
                    </div>
                    <div>
                        <div class="flex gap-[11px] border-b border-taupe-100 px-[18px] py-[13px]">
                            <span class="flex size-[30px] shrink-0 items-center justify-center rounded-lg bg-taupe-200 text-[12px] font-bold text-taupe-700">MR</span>
                            <div class="min-w-0">
                                <div class="text-[12.5px] font-semibold text-taupe-900">Margaret R.</div>
                                <div class="text-[11.5px] leading-snug text-taupe-500">Recovery after surgery — grateful for the meals train.</div>
                            </div>
                        </div>
                        <div class="flex gap-[11px] border-b border-taupe-100 px-[18px] py-[13px]">
                            <span class="flex size-[30px] shrink-0 items-center justify-center rounded-lg bg-taupe-200 text-[12px] font-bold text-taupe-700">DT</span>
                            <div class="min-w-0">
                                <div class="text-[12.5px] font-semibold text-taupe-900">The Tran family</div>
                                <div class="text-[11.5px] leading-snug text-taupe-500">Safe travels and a new season of work.</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 bg-taupe-50 px-[18px] py-3">
                            <flux:icon.calendar-days class="size-4 shrink-0 text-taupe-500" />
                            <span class="text-[11.5px] text-taupe-700">Prayer schedule — <strong class="font-semibold text-taupe-900">Elder Grey</strong> is covering Wed &amp; Thu</span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 text-[12px] font-bold uppercase tracking-[0.06em] text-emerald-700">
                        <flux:icon.heart class="size-[17px]" />
                        Pastoral Care
                    </div>
                    <h2 class="mb-3.5 text-[31px] font-bold leading-[1.15] tracking-[-0.02em] text-taupe-950">Keep watch over the people, not just the plan.</h2>
                    <p class="mb-[22px] text-[15.5px] leading-[1.6] text-taupe-700">Hold your congregation's details, prayer needs and quiet follow-ups in one place — so nobody slips through the week unseen.</p>
                    <div class="flex flex-col gap-[13px]">
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Congregation.</strong> Every person, their roles, and how to reach them.</span>
                        </div>
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Prayer schedule &amp; requests.</strong> Rotate who prays, and log what for.</span>
                        </div>
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Notes.</strong> Private, gentle records of care and conversations.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Feature 3: Congregation Messaging --}}
        <section class="border-b border-taupe-200">
            <div class="mx-auto grid max-w-[1120px] grid-cols-1 items-center gap-9 px-10 py-[66px] min-[820px]:grid-cols-2 min-[820px]:gap-14">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 text-[12px] font-bold uppercase tracking-[0.06em] text-emerald-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z" /></svg>
                        Congregation Messaging
                    </div>
                    <h2 class="mb-3.5 text-[31px] font-bold leading-[1.15] tracking-[-0.02em] text-taupe-950">Reach the whole group, or just one person.</h2>
                    <p class="mb-[22px] text-[15.5px] leading-[1.6] text-taupe-700">Organise people into groups — worship team, elders, small groups — and keep conversations flowing without another chat app to manage.</p>
                    <div class="flex flex-col gap-[13px]">
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Groups.</strong> Public or private, with member scope and threaded discussion.</span>
                        </div>
                        <div class="flex gap-3">
                            <flux:icon.circle-check-big class="mt-px size-[18px] shrink-0 text-emerald-700" />
                            <span class="text-[14.5px] leading-normal text-taupe-800"><strong class="font-bold text-taupe-900">Direct messages.</strong> Quiet one-to-one notes when a group isn't right.</span>
                        </div>
                    </div>
                </div>
                <div class="order-last overflow-hidden rounded-xl border border-taupe-200 bg-white shadow-[0_10px_30px_-18px_rgba(33,30,26,0.3)] min-[820px]:order-none">
                    <div class="border-b border-taupe-200 bg-taupe-50 px-[18px] py-[14px]">
                        <div class="text-[14px] font-bold text-taupe-950">Worship Team</div>
                        <div class="mt-0.5 text-[11.5px] text-taupe-500">8 members · All Members</div>
                    </div>
                    <div class="flex flex-col gap-3 bg-white px-[18px] py-4">
                        <div class="flex gap-[9px]">
                            <span class="flex size-[26px] shrink-0 items-center justify-center rounded-[7px] bg-taupe-200 text-[11px] font-bold text-taupe-700">AL</span>
                            <div class="flex flex-col items-start">
                                <div class="mb-[3px] text-[11px] text-taupe-500">Anna L.</div>
                                <div class="max-w-[230px] rounded-[10px] rounded-tl-[3px] bg-taupe-100 px-3 py-[9px] text-[12.5px] leading-snug text-taupe-900">Set list for Sunday is locked — "In Christ Alone" opens.</div>
                            </div>
                        </div>
                        <div class="flex flex-row-reverse gap-[9px]">
                            <span class="flex size-[26px] shrink-0 items-center justify-center rounded-[7px] bg-emerald-700 text-[11px] font-bold text-white">JP</span>
                            <div class="flex flex-col items-end">
                                <div class="mb-[3px] text-[11px] text-taupe-500">You</div>
                                <div class="max-w-[230px] rounded-[10px] rounded-tr-[3px] bg-emerald-600 px-3 py-[9px] text-left text-[12.5px] leading-snug text-white">Perfect. I'll add the key change to the notes.</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-[9px] border-t border-taupe-200 bg-taupe-50 px-[14px] py-[11px]">
                        <span class="flex-1 rounded-lg border border-taupe-200 bg-white px-[11px] py-2 text-[12px] text-taupe-400">Message the group…</span>
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600">
                            <flux:icon.paper-airplane class="size-[15px] text-white" />
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Positioning + footer (dark) --}}
        <section class="relative overflow-hidden bg-taupe-950">
            <x-app-logo-icon class="pointer-events-none absolute right-[6%] top-1/2 w-[280px] -translate-y-1/2 text-emerald-300 opacity-[0.09]" stroke-width="3.4" />
            <div class="relative mx-auto max-w-[1120px] px-10 py-[76px] text-center">
                <p class="mx-auto max-w-[660px] text-[27px] font-semibold leading-[1.4] tracking-[-0.015em] text-taupe-50">Tier upon tier, part upon part — a stave church stands as one. <span class="text-emerald-300">Stave holds your service together</span> the same way: liturgy, prayer and people, on one page.</p>
                <a href="{{ route('login') }}" class="mt-8 inline-flex items-center gap-2 rounded-[9px] bg-white px-6 py-[13px] text-[14.5px] font-semibold text-taupe-950 transition-colors hover:bg-taupe-100">
                    Sign in to Stave
                    <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
            <div class="border-t border-[#29251f]">
                <div class="mx-auto flex max-w-[1120px] flex-wrap items-center justify-between gap-2.5 px-10 py-5">
                    <span class="text-[12px] text-taupe-500">© 2026 Stave · Private beta</span>
                    <a href="{{ route('login') }}" class="text-[12px] text-taupe-500 transition-colors hover:text-taupe-300">Sign in</a>
                </div>
            </div>
        </section>

    </div>
</x-layouts.public>
