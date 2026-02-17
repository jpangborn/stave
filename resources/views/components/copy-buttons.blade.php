@props([
    'title' => '',
    'content' => '',
])

<div
    x-data="{
        title: @js($title),
        htmlContent: @js($content),

        htmlToPlainText(html) {
            if (!html) return '';

            const temp = document.createElement('div');
            temp.innerHTML = html;

            temp.querySelectorAll('br').forEach(br => {
                br.replaceWith('\n');
            });

            let text = temp.innerHTML;

            text = text.replace(/<\/p>\s*<p[^>]*>/gi, '\n\n');

            text = text.replace(/<p[^>]*>/gi, '');
            text = text.replace(/<\/p>/gi, '');

            const temp2 = document.createElement('div');
            temp2.innerHTML = text;
            text = temp2.textContent || temp2.innerText || '';

            return text.trim();
        },

        async copyRichText() {
            try {
                const html = this.htmlContent || '';
                const plainText = this.htmlToPlainText(this.htmlContent);

                const htmlBlob = new Blob([html], { type: 'text/html' });
                const textBlob = new Blob([plainText], { type: 'text/plain' });

                const item = new ClipboardItem({
                    'text/html': htmlBlob,
                    'text/plain': textBlob,
                });

                await navigator.clipboard.write([item]);
                $flux.toast({ text: 'Copied rich text to clipboard', variant: 'success' });
            } catch (err) {
                try {
                    const plainText = this.htmlToPlainText(this.htmlContent);
                    await navigator.clipboard.writeText(plainText);
                    $flux.toast({ text: 'Copied as plain text (rich text not supported)', variant: 'success' });
                } catch (fallbackErr) {
                    $flux.toast({ text: 'Failed to copy', variant: 'danger' });
                }
            }
        },

        async copyPlainText() {
            const plainText = this.htmlToPlainText(this.htmlContent);

            try {
                await navigator.clipboard.writeText(plainText);
                $flux.toast({ text: 'Copied to clipboard', variant: 'success' });
            } catch (err) {
                $flux.toast({ text: 'Failed to copy', variant: 'danger' });
            }
        },

        async copyTitle() {
            if (!this.title) return;

            try {
                await navigator.clipboard.writeText(this.title);
                $flux.toast({ text: 'Copied title to clipboard', variant: 'success' });
            } catch (err) {
                $flux.toast({ text: 'Failed to copy title', variant: 'danger' });
            }
        }
    }"
    {{ $attributes->merge(['class' => 'inline-flex gap-1']) }}
>
    <flux:tooltip content="Copy Rich Text">
        <flux:button
            variant="ghost"
            size="xs"
            icon="clipboard-document"
            @click="copyRichText()"
            class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
        />
    </flux:tooltip>

    <flux:tooltip content="Copy Plain Text">
        <flux:button
            variant="ghost"
            size="xs"
            icon="clipboard-document-list"
            @click="copyPlainText()"
            class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
        />
    </flux:tooltip>

    <flux:tooltip content="Copy Title">
        <flux:button
            variant="ghost"
            size="xs"
            icon="tag"
            @click="copyTitle()"
            class="text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
        />
    </flux:tooltip>
</div>
