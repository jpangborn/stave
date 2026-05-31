@props([
    'service',
])

@php
    $sections = $service->sectionCount();
    $elements = $service->elementCount();
    $unassigned = $service->unassignedCount();
    $missing = $service->missingContentCount();
@endphp

<div class="mt-4 flex shrink-0 flex-wrap items-center gap-x-6 gap-y-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-900">
    <x-service.stat label="Sections" :value="$sections" />
    <div class="hidden h-6 w-px bg-zinc-200 sm:block dark:bg-zinc-700"></div>
    <x-service.stat label="Elements" :value="$elements" />
    <div class="hidden h-6 w-px bg-zinc-200 sm:block dark:bg-zinc-700"></div>
    <x-service.stat label="Unassigned" :value="$unassigned" :warn="$unassigned > 0" />
    <div class="hidden h-6 w-px bg-zinc-200 sm:block dark:bg-zinc-700"></div>
    <x-service.stat label="Missing content" :value="$missing" :warn="$missing > 0" />
</div>
