<script setup lang="ts">
import { CrepeBuilder } from '@milkdown/crepe/builder';
import { cursor } from '@milkdown/crepe/feature/cursor';
import { linkTooltip } from '@milkdown/crepe/feature/link-tooltip';
import { listItem } from '@milkdown/crepe/feature/list-item';
import { placeholder } from '@milkdown/crepe/feature/placeholder';
import { topBar } from '@milkdown/crepe/feature/top-bar';
import { editorViewCtx } from '@milkdown/kit/core';
import { redoCommand, undoCommand } from '@milkdown/kit/plugin/history';
import { linkSchema } from '@milkdown/kit/preset/commonmark';
import type { MarkType } from '@milkdown/kit/prose/model';
import type { EditorState, Transaction } from '@milkdown/kit/prose/state';
import { Plugin } from '@milkdown/kit/prose/state';
import { $prose, callCommand, getMarkdown } from '@milkdown/kit/utils';
import { Milkdown, MilkdownProvider, useEditor } from '@milkdown/vue';
import { defineComponent, h, ref } from 'vue';

type EditorApi = {
    focus: () => void;
    getMarkdown: () => string;
    redo: () => void;
    undo: () => void;
};

const formattingActionLabels = [
    'Bold',
    'Italic',
    'Strikethrough',
    'Inline code',
    'Bullet list',
    'Numbered list',
    'Checklist',
    'Link',
    'Code block',
    'Quote',
    'Horizontal rule',
] as const;

function hasSafeLinkProtocol(href: string): boolean {
    const normalizedHref = href.replace(/[\u0000-\u0020\u007f-\u009f]/g, '');
    const protocol = normalizedHref.match(/^([a-z][a-z0-9+.-]*):/i)?.[1];

    if (!protocol) {
        return true;
    }

    return ['http', 'https', 'mailto', 'tel'].includes(protocol.toLowerCase());
}

function removeUnsafeLinkMarks(
    state: EditorState,
    linkType: MarkType,
): Transaction | null {
    let transaction: Transaction | null = null;

    state.doc.descendants((node, position) => {
        node.marks.forEach((mark) => {
            if (
                mark.type !== linkType ||
                hasSafeLinkProtocol(String(mark.attrs.href ?? ''))
            ) {
                return;
            }

            transaction ??= state.tr;
            transaction.removeMark(position, position + node.nodeSize, mark);
        });
    });

    return transaction;
}

const safeLinkProtocolPlugin = $prose((ctx) => {
    const linkType = linkSchema.type(ctx);

    return new Plugin({
        appendTransaction: (transactions, _oldState, newState) => {
            if (!transactions.some((transaction) => transaction.docChanged)) {
                return null;
            }

            return removeUnsafeLinkMarks(newState, linkType);
        },
    });
});

const props = withDefaults(
    defineProps<{
        autofocus?: boolean;
        modelValue: string;
    }>(),
    {
        autofocus: false,
    },
);

const emit = defineEmits<{
    ready: [];
    'update:modelValue': [value: string];
}>();

const editorContentRef = ref<EditorApi | null>(null);

const EditorContent = defineComponent({
    name: 'MarkdownNoteEditorContent',
    props: {
        autofocus: {
            type: Boolean,
            default: false,
        },
        initialValue: {
            type: String,
            required: true,
        },
    },
    emits: ['ready', 'update:modelValue'],
    setup(innerProps, { emit: innerEmit, expose }) {
        const { get } = useEditor((root) => {
            const crepe = new CrepeBuilder({
                root,
                defaultValue: innerProps.initialValue,
            })
                .addFeature(cursor)
                .addFeature(listItem)
                .addFeature(linkTooltip)
                .addFeature(placeholder, {
                    text: 'Start writing your note...',
                    mode: 'doc',
                })
                .addFeature(topBar, {
                    headingOptions: [
                        { label: 'Paragraph', level: null },
                        { label: 'Heading 2', level: 2 },
                        { label: 'Heading 3', level: 3 },
                    ],
                });

            crepe.editor.use(safeLinkProtocolPlugin);

            crepe.on((listener) => {
                listener
                    .markdownUpdated((_ctx, markdown) => {
                        innerEmit('update:modelValue', markdown);
                    })
                    .mounted((ctx) => {
                        const view = ctx.get(editorViewCtx);

                        view.dom.setAttribute('aria-label', 'Note body');
                        view.dom.setAttribute('aria-multiline', 'true');

                        const initialSanitizingTransaction =
                            removeUnsafeLinkMarks(
                                view.state,
                                linkSchema.type(ctx),
                            );

                        if (initialSanitizingTransaction) {
                            view.dispatch(
                                initialSanitizingTransaction.setMeta(
                                    'addToHistory',
                                    false,
                                ),
                            );
                        }

                        queueMicrotask(() => {
                            const editorRoot = view.dom.parentElement;

                            editorRoot
                                ?.querySelector<HTMLButtonElement>(
                                    '.top-bar-heading-button',
                                )
                                ?.setAttribute('aria-label', 'Text style');

                            editorRoot
                                ?.querySelectorAll<HTMLButtonElement>(
                                    '.top-bar-item',
                                )
                                .forEach((button, index) => {
                                    const label = formattingActionLabels[index];

                                    if (!label) {
                                        return;
                                    }

                                    button.setAttribute('aria-label', label);
                                    button.title = label;
                                });
                        });

                        innerEmit('ready');

                        if (innerProps.autofocus) {
                            queueMicrotask(() => {
                                view.focus();
                            });
                        }
                    });
            });

            return crepe;
        });

        function focus(): void {
            get()?.action((ctx) => {
                ctx.get(editorViewCtx).focus();
            });
        }

        function currentMarkdown(): string {
            return get()?.action(getMarkdown()) ?? innerProps.initialValue;
        }

        function undo(): void {
            get()?.action(callCommand(undoCommand.key));
        }

        function redo(): void {
            get()?.action(callCommand(redoCommand.key));
        }

        expose({ focus, getMarkdown: currentMarkdown, redo, undo });

        return () => h(Milkdown);
    },
});

function preventLinkNavigation(event: MouseEvent): void {
    const target = event.target;

    if (target instanceof Element && target.closest('a')) {
        event.preventDefault();
    }
}

function handleReady(): void {
    emit('ready');
}

function updateModelValue(value: string): void {
    emit('update:modelValue', value);
}

defineExpose({
    focus: () => editorContentRef.value?.focus(),
    getMarkdown: () =>
        editorContentRef.value?.getMarkdown() ?? props.modelValue,
    redo: () => editorContentRef.value?.redo(),
    undo: () => editorContentRef.value?.undo(),
});
</script>

<template>
    <div
        class="student-markdown-editor min-w-0 bg-transparent"
        data-test="note-body-editor"
        @click.capture="preventLinkNavigation"
    >
        <MilkdownProvider>
            <EditorContent
                ref="editorContentRef"
                :autofocus="props.autofocus"
                :initial-value="props.modelValue"
                @ready="handleReady"
                @update:model-value="updateModelValue"
            />
        </MilkdownProvider>
    </div>
</template>

<style>
@import '@milkdown/crepe/theme/common/prosemirror.css';
@import '@milkdown/crepe/theme/common/reset.css';
@import '@milkdown/crepe/theme/common/cursor.css';
@import '@milkdown/crepe/theme/common/link-tooltip.css';
@import '@milkdown/crepe/theme/common/list-item.css';
@import '@milkdown/crepe/theme/common/placeholder.css';
@import '@milkdown/crepe/theme/common/top-bar.css';

.student-markdown-editor .milkdown {
    --crepe-color-background: #ffffff;
    --crepe-color-on-background: #17212b;
    --crepe-color-surface: #ffffff;
    --crepe-color-surface-low: #f5f8f9;
    --crepe-color-on-surface: #17212b;
    --crepe-color-on-surface-variant: #667584;
    --crepe-color-outline: #536271;
    --crepe-color-primary: #078c96;
    --crepe-color-secondary: #e2f1f3;
    --crepe-color-on-secondary: #08747c;
    --crepe-color-inverse: #17212b;
    --crepe-color-on-inverse: #ffffff;
    --crepe-color-inline-code: #b84d3c;
    --crepe-color-error: #dc2626;
    --crepe-color-hover: #eef6f7;
    --crepe-color-selected: #dceef0;
    --crepe-color-inline-area: #f1f5f6;
    --crepe-font-title: var(--font-sans);
    --crepe-font-default: var(--font-sans);
    --crepe-font-code:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        'Liberation Mono', monospace;
    --crepe-shadow-1: 0 1px 2px rgb(0 0 0 / 8%);
    --crepe-shadow-2: 0 8px 24px rgb(0 0 0 / 14%);

    min-width: 0;
    background: transparent;
    color: #17212b;
}

.dark .student-markdown-editor .milkdown {
    --crepe-color-background: #0e1415;
    --crepe-color-on-background: #edf4f4;
    --crepe-color-surface: #151e20;
    --crepe-color-surface-low: #1a2628;
    --crepe-color-on-surface: #edf4f4;
    --crepe-color-on-surface-variant: #a9b8ba;
    --crepe-color-outline: #bac8ca;
    --crepe-color-primary: #67cbd1;
    --crepe-color-secondary: #19383b;
    --crepe-color-on-secondary: #8fdce0;
    --crepe-color-inverse: #edf4f4;
    --crepe-color-on-inverse: #0e1415;
    --crepe-color-inline-code: #ff8b79;
    --crepe-color-hover: #1a292b;
    --crepe-color-selected: #214044;
    --crepe-color-inline-area: #172123;

    color: #edf4f4;
}

.student-markdown-editor .milkdown .milkdown-top-bar {
    top: 0;
    min-height: 48px;
    padding-inline: 4px;
    border: 1px solid #d6e1e5;
    border-radius: 6px;
    background: #ffffff;
}

.dark .student-markdown-editor .milkdown .milkdown-top-bar {
    border-color: #304244;
    background: #151e20;
}

.student-markdown-editor .milkdown .milkdown-top-bar .top-bar-divider {
    margin-inline: 4px;
    background: #dfe7eb;
}

.dark .student-markdown-editor .milkdown .milkdown-top-bar .top-bar-divider {
    background: #304244;
}

.student-markdown-editor .milkdown .milkdown-top-bar .top-bar-item {
    margin-inline: 3px;
}

.student-markdown-editor
    .milkdown
    .milkdown-top-bar
    .top-bar-item[aria-label='Horizontal rule'] {
    display: none;
}

.student-markdown-editor .milkdown .milkdown-top-bar .top-bar-heading-selector {
    padding-inline: 4px;
}

.student-markdown-editor .milkdown .ProseMirror {
    min-height: 24rem;
    max-width: 72ch;
    margin-inline: 0;
    padding: 1.25rem 0 5rem;
    font-family: var(--font-sans);
    font-size: 1rem;
    line-height: 1.75;
    color: #263440;
    overflow-wrap: anywhere;
}

.dark .student-markdown-editor .milkdown .ProseMirror {
    color: #dce7e8;
}

.student-markdown-editor .milkdown .ProseMirror :is(h1, h2, h3, h4) {
    color: #17212b;
    font-weight: 650;
    letter-spacing: 0;
}

.dark .student-markdown-editor .milkdown .ProseMirror :is(h1, h2, h3, h4) {
    color: #f0f6f6;
}

.student-markdown-editor .milkdown .ProseMirror pre {
    border: 1px solid #dbe4e8;
    border-radius: 6px;
    background: #f8fafb;
}

.dark .student-markdown-editor .milkdown .ProseMirror pre {
    border-color: #304244;
    background: #121a1b;
}

.student-markdown-editor .milkdown .ProseMirror:focus {
    outline: none;
}

.student-markdown-editor .milkdown .ProseMirror a {
    color: #078c96;
    text-decoration: underline;
    text-underline-offset: 3px;
}

@media (max-width: 640px) {
    .student-markdown-editor .milkdown .milkdown-top-bar {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-inline: 0.25rem;
    }

    .student-markdown-editor .milkdown .milkdown-top-bar .top-bar-inner {
        width: max-content;
        flex-wrap: nowrap;
    }

    .student-markdown-editor .milkdown .ProseMirror {
        min-height: 22rem;
        padding: 1.5rem 0 4rem;
    }
}
</style>
