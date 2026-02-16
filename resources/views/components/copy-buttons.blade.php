@props([
    'content' => '',
])

<div
    x-data="{
        htmlContent: @js($content),

        htmlToPlainText(html, preserveSpacing = true) {
            if (!html) return '';

            // Create a temporary element to parse HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;

            // Replace <br> tags with newlines
            temp.querySelectorAll('br').forEach(br => {
                br.replaceWith('\n');
            });

            // Get the modified HTML
            let text = temp.innerHTML;

            // Replace </p><p> with double newlines (paragraph breaks) or single newlines
            text = text.replace(/<\/p>\s*<p[^>]*>/gi, preserveSpacing ? '\n\n' : '\n');

            // Remove opening <p> tags
            text = text.replace(/<p[^>]*>/gi, '');

            // Remove closing </p> tags
            text = text.replace(/<\/p>/gi, '');

            // Strip remaining HTML tags
            const temp2 = document.createElement('div');
            temp2.innerHTML = text;
            text = temp2.textContent || temp2.innerText || '';

            // Clean up multiple consecutive newlines if compact mode
            if (!preserveSpacing) {
                text = text.replace(/\n{2,}/g, '\n');
            }

            return text.trim();
        },

        async copyToClipboard(preserveSpacing = true) {
            const plainText = this.htmlToPlainText(this.htmlContent, preserveSpacing);

            try {
                await navigator.clipboard.writeText(plainText);
                $flux.toast({
                    text: 'Copied to clipboard',
                    variant: 'success'
                });
            } catch (err) {
                $flux.toast({
                    text: 'Failed to copy',
                    variant: 'danger'
                });
            }
        }
    }"
    {{ $attributes->merge(['class' => 'inline-flex gap-1']) }}
>
    <flux:tooltip content="Copy with paragraph spacing">
        <flux:button
            variant="ghost"
            size="xs"
            icon="clipboard-document"
            @click="copyToClipboard(true)"
            class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
        />
    </flux:tooltip>

    <flux:tooltip content="Copy compact (single line breaks)">
        <flux:button
            variant="ghost"
            size="xs"
            icon="clipboard-document-list"
            @click="copyToClipboard(false)"
            class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
        />
    </flux:tooltip>
</div>
