import Mention from '@tiptap/extension-mention';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getLivewireComponent(editor) {
    const host = editor.options.element?.closest?.('[wire\\:id]');
    if (!host) return null;

    const wireId = host.getAttribute('wire:id');
    return window.Livewire?.find?.(wireId) ?? null;
}

function createSuggestionList(props) {
    const root = document.createElement('div');
    root.className = 'mention-suggestions';

    let items = [];
    let selectedIndex = 0;

    const render = () => {
        if (items.length === 0) {
            root.innerHTML = `<div class="mention-suggestions__empty">No matching members</div>`;
            return;
        }

        root.innerHTML = items.map((item, index) => `
            <button
                type="button"
                class="mention-suggestions__item${index === selectedIndex ? ' is-selected' : ''}"
                data-index="${index}"
            >
                <img class="mention-suggestions__avatar" src="${escapeHtml(item.gravatar ?? '')}" alt="" />
                <span class="mention-suggestions__name">${escapeHtml(item.name)}</span>
            </button>
        `).join('');

        root.querySelectorAll('button[data-index]').forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                const index = Number(button.dataset.index);
                selectItem(index);
            });
        });
    };

    const selectItem = (index) => {
        const item = items[index];
        if (!item) return;

        props.command({
            id: String(item.id),
            label: item.name,
        });
    };

    const moveUp = () => {
        if (items.length === 0) return;
        selectedIndex = (selectedIndex + items.length - 1) % items.length;
        render();
    };

    const moveDown = () => {
        if (items.length === 0) return;
        selectedIndex = (selectedIndex + 1) % items.length;
        render();
    };

    const enter = () => {
        selectItem(selectedIndex);
    };

    const update = (nextItems) => {
        items = nextItems ?? [];
        selectedIndex = 0;
        render();
    };

    update(props.items);

    return {
        element: root,
        update,
        moveUp,
        moveDown,
        enter,
    };
}

const mentionExtension = Mention.configure({
    HTMLAttributes: {
        class: 'mention',
    },
    deleteTriggerWithBackspace: true,

    renderHTML({ node }) {
        const id = node.attrs.id ?? '';
        const label = node.attrs.label ?? id;

        return [
            'span',
            {
                'data-mention': id,
                class: 'mention',
            },
            `@${label}`,
        ];
    },

    renderText({ node }) {
        return `@${node.attrs.label ?? node.attrs.id ?? ''}`;
    },

    suggestion: {
        char: '@',

        items: async ({ query, editor }) => {
            const component = getLivewireComponent(editor);
            if (!component) return [];

            try {
                const results = await component.call('mentionCandidates', query);
                return Array.isArray(results) ? results : [];
            } catch (error) {
                console.error('Mention lookup failed', error);
                return [];
            }
        },

        render: () => {
            let list = null;
            let popup = null;

            return {
                onStart: (props) => {
                    if (!props.clientRect) return;

                    list = createSuggestionList(props);

                    popup = tippy('body', {
                        getReferenceClientRect: props.clientRect,
                        appendTo: () => document.body,
                        content: list.element,
                        showOnCreate: true,
                        interactive: true,
                        trigger: 'manual',
                        placement: 'bottom-start',
                        theme: 'mentions',
                    })[0];
                },

                onUpdate: (props) => {
                    if (!list || !popup) return;
                    list.update(props.items);

                    if (props.clientRect) {
                        popup.setProps({
                            getReferenceClientRect: props.clientRect,
                        });
                    }
                },

                onKeyDown: (props) => {
                    if (!list) return false;

                    if (props.event.key === 'Escape') {
                        popup?.hide();
                        return true;
                    }

                    if (props.event.key === 'ArrowUp') {
                        list.moveUp();
                        return true;
                    }

                    if (props.event.key === 'ArrowDown') {
                        list.moveDown();
                        return true;
                    }

                    if (props.event.key === 'Enter') {
                        list.enter();
                        return true;
                    }

                    return false;
                },

                onExit: () => {
                    popup?.destroy();
                    popup = null;
                    list = null;
                },
            };
        },
    },
});

document.addEventListener('flux:editor', (event) => {
    event.detail.registerExtension(mentionExtension);
});
