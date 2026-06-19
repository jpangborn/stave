@props(['label'])

<div class="flex gap-3 border-t border-zinc-100 py-2 dark:border-zinc-700/60">
    <dt class="w-24 shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
    <dd class="min-w-0 flex-1 text-[13px] font-medium break-words">{{ $slot }}</dd>
</div>
