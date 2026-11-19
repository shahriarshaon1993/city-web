<script setup lang="ts">
import {
    Bold,
    Heading2,
    Heading3,
    Heading4,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Undo2,
} from '@lucide/vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { StarterKit } from '@tiptap/starter-kit';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

// CMP-ADM-RTE (DEC-83 → brought forward): a small Tiptap editor for the text fields that are stored
// as HTML. It can only produce what the server keeps (App\Support\Html\BasicHtml, NFR-SEC-03):
// p, h2–h4, ul/ol/li, strong, em, a[href], blockquote — so what the editor shows is what is saved.
// No images, tables or embeds. The editor is created in onMounted (SSR-safe, SEO-REN-08).
const props = defineProps<{
    id: string;
    describedby?: string;
    invalid?: boolean;
    /** Words needed to publish or to be indexed; the counter turns green when reached. */
    minWords?: number;
    /** Visible height of the writing area, in lines of text. */
    rows?: number;
    /** Paragraphs, bold, links and lists only (FR-SOC-003 FAQ answers): no headings, italic or quote. */
    limited?: boolean;
}>();

const model = defineModel<string>({ default: '' });

function editorAttributes(
    describedby?: string,
    invalid?: boolean,
): Record<string, string> {
    return {
        id: props.id,
        role: 'textbox',
        'aria-multiline': 'true',
        'aria-labelledby': `${props.id}-label`,
        ...(describedby ? { 'aria-describedby': describedby } : {}),
        ...(invalid ? { 'aria-invalid': 'true' } : {}),
        spellcheck: 'true',
        class: 'rte-content',
    };
}

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: props.limited ? false : { levels: [2, 3, 4] },
            blockquote: props.limited ? false : undefined,
            italic: props.limited ? false : undefined,
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                protocols: ['mailto', 'tel'],
                HTMLAttributes: { rel: 'noopener', target: null },
            },
            code: false,
            codeBlock: false,
            horizontalRule: false,
            strike: false,
            underline: false,
            trailingNode: false,
        }),
    ],
    editorProps: {
        attributes: editorAttributes(props.describedby, props.invalid),
    },
    onUpdate: ({ editor: current }) => {
        // An empty document is "" and not "<p></p>", so "required" and the word count are right.
        const html = current.isEmpty ? '' : current.getHTML();
        lastEmitted = html;
        model.value = html;
    },
});

let lastEmitted = model.value;

// A value set from outside (a reload after a conflict, a discard) replaces the document.
watch(model, (value) => {
    if (value !== lastEmitted && editor.value) {
        lastEmitted = value;
        editor.value.commands.setContent(value, { emitUpdate: false });
    }
});

watch(
    () => [props.invalid, props.describedby] as const,
    ([invalid, describedby]) => {
        editor.value?.setOptions({
            editorProps: { attributes: editorAttributes(describedby, invalid) },
        });
    },
);

// --- Words --------------------------------------------------------------------------------------
const words = computed(() => {
    const text = model.value
        .replace(/<\/(p|li|h[2-4]|blockquote)>|<br\s*\/?>/gi, ' ')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .trim();

    return text === '' ? 0 : text.split(/\s+/u).length;
});

const reached = computed(
    () => props.minWords !== undefined && words.value >= props.minWords,
);

// --- Link bar -----------------------------------------------------------------------------------
const linkOpen = ref(false);
const linkUrl = ref('');
const linkInput = ref<InstanceType<typeof Input> | null>(null);

async function openLink(): Promise<void> {
    if (!editor.value) {
        return;
    }

    linkUrl.value = editor.value.getAttributes('link').href ?? '';
    linkOpen.value = true;
    await nextTick();
    (linkInput.value?.$el as HTMLInputElement | undefined)?.focus();
}

// The server only keeps http(s), mailto, tel, /path and #anchor links (BasicHtml::safeUrl).
function normaliseUrl(raw: string): string {
    const url = raw.trim();

    if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) {
        return url;
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url)
        ? `mailto:${url}`
        : `https://${url}`;
}

function applyLink(): void {
    const chain = editor.value?.chain().focus().extendMarkRange('link');

    if (!chain) {
        return;
    }

    if (linkUrl.value.trim() === '') {
        chain.unsetLink().run();
    } else {
        chain.setLink({ href: normaliseUrl(linkUrl.value) }).run();
    }

    linkOpen.value = false;
}

function removeLink(): void {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    linkOpen.value = false;
}

function closeLink(): void {
    linkOpen.value = false;
    editor.value?.commands.focus();
}

// --- Toolbar ------------------------------------------------------------------------------------
type Tool = {
    key: string;
    label: string;
    icon: typeof Bold;
    active: () => boolean;
    run: () => void;
    disabled?: () => boolean;
};

const tools = computed<Tool[][]>(() => {
    const e = editor.value;

    if (!e) {
        return [];
    }

    const heading = (level: 2 | 3 | 4, icon: typeof Bold): Tool => ({
        key: `h${level}`,
        label: `Heading ${level}`,
        icon,
        active: () => e.isActive('heading', { level }),
        run: () => e.chain().focus().toggleHeading({ level }).run(),
    });

    return [
        ...(props.limited
            ? []
            : [
                  [
                      heading(2, Heading2),
                      heading(3, Heading3),
                      heading(4, Heading4),
                  ],
              ]),
        [
            {
                key: 'bold',
                label: 'Bold',
                icon: Bold,
                active: () => e.isActive('bold'),
                run: () => e.chain().focus().toggleBold().run(),
            },
            ...(props.limited
                ? []
                : [
                      {
                          key: 'italic',
                          label: 'Italic',
                          icon: Italic,
                          active: () => e.isActive('italic'),
                          run: () => e.chain().focus().toggleItalic().run(),
                      },
                  ]),
            {
                key: 'link',
                label: 'Link',
                icon: Link2,
                active: () => e.isActive('link'),
                run: () => void openLink(),
            },
        ],
        [
            {
                key: 'bullets',
                label: 'Bulleted list',
                icon: List,
                active: () => e.isActive('bulletList'),
                run: () => e.chain().focus().toggleBulletList().run(),
            },
            {
                key: 'numbers',
                label: 'Numbered list',
                icon: ListOrdered,
                active: () => e.isActive('orderedList'),
                run: () => e.chain().focus().toggleOrderedList().run(),
            },
            ...(props.limited
                ? []
                : [
                      {
                          key: 'quote',
                          label: 'Quote',
                          icon: Quote,
                          active: () => e.isActive('blockquote'),
                          run: () => e.chain().focus().toggleBlockquote().run(),
                      },
                  ]),
        ],
        [
            {
                key: 'undo',
                label: 'Undo',
                icon: Undo2,
                active: () => false,
                run: () => e.chain().focus().undo().run(),
                disabled: () => !e.can().undo(),
            },
            {
                key: 'redo',
                label: 'Redo',
                icon: Redo2,
                active: () => false,
                run: () => e.chain().focus().redo().run(),
                disabled: () => !e.can().redo(),
            },
        ],
    ];
});

const heightStyle = computed(() => ({
    '--rte-lines': String(props.rows ?? 10),
}));
</script>

<template>
    <div class="grid gap-2">
        <div
            :class="
                cn(
                    'overflow-hidden rounded-md border border-input bg-background shadow-xs transition-[border-color,box-shadow] focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/25',
                    invalid &&
                        'border-destructive ring-3 ring-destructive/20 focus-within:border-destructive',
                )
            "
            :style="heightStyle"
        >
            <div
                class="flex flex-wrap items-center gap-x-1 gap-y-1 border-b bg-muted/40 px-2 py-1.5"
                role="toolbar"
                aria-label="Text formatting"
                :aria-controls="id"
            >
                <template
                    v-for="(group, groupIndex) in tools"
                    :key="groupIndex"
                >
                    <span
                        v-if="groupIndex > 0"
                        class="mx-1 h-5 w-px bg-border"
                        aria-hidden="true"
                    />
                    <Button
                        v-for="tool in group"
                        :key="tool.key"
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :class="
                            cn(
                                'text-muted-foreground',
                                tool.active() &&
                                    'bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary',
                            )
                        "
                        :aria-label="tool.label"
                        :title="tool.label"
                        :aria-pressed="
                            tool.key === 'undo' || tool.key === 'redo'
                                ? undefined
                                : tool.active()
                        "
                        :disabled="tool.disabled?.()"
                        @click="tool.run()"
                    >
                        <component :is="tool.icon" aria-hidden="true" />
                    </Button>
                </template>
            </div>

            <form
                v-if="linkOpen"
                class="flex flex-wrap items-center gap-2 border-b bg-muted/20 px-3 py-2"
                @submit.prevent="applyLink"
                @keydown.esc.prevent.stop="closeLink"
            >
                <label :for="`${id}-link`" class="text-sm font-medium"
                    >Link to</label
                >
                <Input
                    :id="`${id}-link`"
                    ref="linkInput"
                    v-model="linkUrl"
                    class="h-9 min-w-48 flex-1"
                    placeholder="https://… or /projects/…"
                    inputmode="url"
                    autocomplete="off"
                />
                <Button type="submit" size="sm">Apply</Button>
                <Button
                    v-if="editor?.isActive('link')"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeLink"
                >
                    <Link2Off aria-hidden="true" />
                    Remove link
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="closeLink"
                >
                    Cancel
                </Button>
            </form>

            <EditorContent :editor="editor" class="rte-frame" />
        </div>

        <p
            :class="
                cn(
                    'text-xs tabular-nums',
                    minWords === undefined
                        ? 'text-muted-foreground'
                        : reached
                          ? 'text-success'
                          : 'text-warning',
                )
            "
            aria-live="off"
        >
            {{ words }} {{ words === 1 ? 'word' : 'words' }}
            <template v-if="minWords !== undefined">
                · {{ reached ? 'long enough' : `${minWords} needed` }}
            </template>
        </p>
    </div>
</template>
