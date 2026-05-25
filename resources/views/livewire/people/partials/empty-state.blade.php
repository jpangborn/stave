<div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-6 py-14 text-center">
    <div class="mx-auto mb-3 grid size-14 place-items-center rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-100 dark:ring-emerald-900">
        <flux:icon name="users" class="size-6" />
    </div>
    <flux:heading size="lg">No people yet</flux:heading>
    <flux:subheading class="mx-auto mt-1 max-w-sm">
        Add the members of your congregation to start assigning them to services, groups, and the prayer schedule.
    </flux:subheading>
    <div class="mt-4 inline-flex gap-2">
        <flux:modal.trigger name="add-person">
            <flux:button variant="primary" icon="user-plus">Add your first person</flux:button>
        </flux:modal.trigger>
    </div>
</div>
