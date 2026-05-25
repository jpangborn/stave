<div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 px-6 py-12 text-center">
    <div class="mx-auto mb-3 grid size-12 place-items-center rounded-lg bg-white dark:bg-zinc-800 text-zinc-500 ring-1 ring-zinc-200 dark:ring-zinc-700">
        <flux:icon name="magnifying-glass" class="size-5" />
    </div>
    <flux:heading>No people match those filters</flux:heading>
    <flux:subheading class="mt-1">
        Try clearing the search or switching to <button type="button" x-on:click="$wire.set('filter', 'all')" class="font-semibold underline">All</button>.
    </flux:subheading>
</div>
