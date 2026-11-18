<script setup lang="ts">
import {
    Bold,
    Heading2,
    Heading3,
    Heading4,
    ImagePlus,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Table2,
    TriangleAlert,
    Undo2,
    Video,
} from '@lucide/vue';
import {
    Table,
    TableCell,
    TableHeader,
    TableRow,
} from '@tiptap/extension-table';
import { Youtube, isValidYoutubeUrl } from '@tiptap/extension-youtube';
import { StarterKit } from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, nextTick, ref, watch } from 'vue';
import { useAssetMap } from '@/components/admin/catalog/context';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { MediaAsset } from '@/types';
import {
    HeadingOrder,
    LibraryImage,
    documentIssues,
    headingLevelBefore,
} from './rte';

// CMP-ADM-RTE (FR-NEWS-001): the article editor. Toolbar: H2/H3/H4, bold, italic, link, lists, quote,
// rule, library image, table, YouTube, undo/redo. It can only produce what the server keeps
// (App\Support\News\PostBody, NFR-SEC-03). Headings start at H2 and cannot skip a level (FR-NEWS-005);
// an image needs alt text or must be marked decorative (AC-NEWS-02). The editor is created in
// onMounted by Tiptap (SSR-safe, SEO-REN-08).
const props = defineProps<{
    id: string;
    describedby?: string;
    invalid?: boolean;
}>();

const model = defineModel<string>({ default: '' });
const { remember } = useAssetMap();

const WORDS_PER_MINUTE = 200;

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
            heading: { levels: [2, 3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                protocols: ['mailto', 'tel'],
                HTMLAttributes: { rel: 'noopener', target: null },
            },
            code: false,
            codeBlock: false,
            strike: false,
            underline: false,
            trailingNode: false,
        }),
        LibraryImage,
        Table.configure({ resizable: false }),
        TableRow,
        TableHeader,
        TableCell,
        Youtube.configure({
            nocookie: true,
            controls: true,
            width: 640,
            height: 360,
            addPasteHandler: false,
        }),
        HeadingOrder,
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

// --- Words, read time, problems ------------------------------------------------------------------
const words = computed(() => {
    const text = model.value
        .replace(/<\/(p|li|h[2-4]|blockquote|tr|figcaption)>|<br\s*\/?>/gi, ' ')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .trim();

    return text === '' ? 0 : text.split(/\s+/u).length;
});

// FR-NEWS-004: ceil(words / 200), at least one minute.
const readMinutes = computed(() =>
    Math.max(1, Math.ceil(words.value / WORDS_PER_MINUTE)),
);

const issues = computed(() => {
    // Reading the model makes the list follow every edit.
    void model.value;

    return editor.value ? documentIssues(editor.value.state.doc) : [];
});

// --- Link bar ------------------------------------------------------------------------------------
type Bar = 'link' | 'youtube' | null;
const bar = ref<Bar>(null);
const barUrl = ref('');
const barError = ref('');
const barInput = ref<InstanceType<typeof Input> | null>(null);

async function openBar(kind: Exclude<Bar, null>): Promise<void> {
    if (!editor.value) {
        return;
    }

    barUrl.value =
        kind === 'link' ? (editor.value.getAttributes('link').href ?? '') : '';
    barError.value = '';
    bar.value = kind;
    await nextTick();
    (barInput.value?.$el as HTMLInputElement | undefined)?.focus();
}

function closeBar(): void {
    bar.value = null;
    editor.value?.commands.focus();
}

// The server only keeps http(s), mailto, tel, /path and #anchor links (PostBody::clean).
function normaliseUrl(raw: string): string {
    const url = raw.trim();

    if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) {
        return url;
    }

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url)
        ? `mailto:${url}`
        : `https://${url}`;
}

function applyBar(): void {
    const e = editor.value;

    if (!e) {
        return;
    }

    if (bar.value === 'youtube') {
        const url = barUrl.value.trim();

        if (!isValidYoutubeUrl(url)) {
            barError.value = 'Paste a YouTube address, e.g. https://youtu.be/…';

            return;
        }

        e.chain().focus().setYoutubeVideo({ src: url }).run();
        bar.value = null;

        return;
    }

    const chain = e.chain().focus().extendMarkRange('link');

    if (barUrl.value.trim() === '') {
        chain.unsetLink().run();
    } else {
        chain.setLink({ href: normaliseUrl(barUrl.value) }).run();
    }

    bar.value = null;
}

function removeLink(): void {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    bar.value = null;
}

// --- Library images (FR-NEWS-006) -----------------------------------------------------------------
const pickerOpen = ref(false);

function insertImages(assets: MediaAsset[]): void {
    const e = editor.value;

    if (!e) {
        return;
    }

    for (const asset of assets) {
        remember(asset);
        e.chain()
            .focus()
            .insertContent({
                type: 'image',
                attrs: {
                    src: asset.url ?? asset.preview_url,
                    // The image inherits the library's alt text; the editor can override it here.
                    alt: asset.is_decorative ? '' : (asset.alt ?? ''),
                    decorative: asset.is_decorative,
                    caption: asset.caption ?? '',
                    assetId: asset.id,
                    width: asset.width,
                    height: asset.height,
                },
            })
            .run();
    }
}

const image = computed(() => {
    void model.value;

    return editor.value?.isActive('image')
        ? (editor.value.getAttributes('image') as {
              alt: string;
              decorative: boolean;
              caption: string;
          })
        : null;
});

function setImage(attributes: Record<string, unknown>): void {
    editor.value?.chain().updateAttributes('image', attributes).run();
}

const inTable = computed(() => {
    void model.value;

    return editor.value?.isActive('table') ?? false;
});

// --- Toolbar -------------------------------------------------------------------------------------
type Tool = {
    key: string;
    label: string;
    icon: typeof Bold;
    active: () => boolean;
    run: () => void;
    disabled?: () => boolean;
    hint?: () => string | undefined;
};

const tools = computed<Tool[][]>(() => {
    const e = editor.value;

    if (!e) {
        return [];
    }

    // FR-NEWS-005: a level is offered only when the heading before the cursor is at most one level above.
    const heading = (level: 2 | 3 | 4, icon: typeof Bold): Tool => {
        const before = headingLevelBefore(e.state.doc, e.state.selection.from);
        const blocked = level > before + 1 && !e.isActive('heading', { level });

        return {
            key: `h${level}`,
            label: `Heading ${level}`,
            icon,
            active: () => e.isActive('heading', { level }),
            run: () => e.chain().focus().toggleHeading({ level }).run(),
            disabled: () => blocked,
            hint: () =>
                blocked
                    ? `Heading ${level} needs a Heading ${level - 1} before it.`
                    : undefined,
        };
    };

    return [
        [heading(2, Heading2), heading(3, Heading3), heading(4, Heading4)],
        [
            {
                key: 'bold',
                label: 'Bold',
                icon: Bold,
                active: () => e.isActive('bold'),
                run: () => e.chain().focus().toggleBold().run(),
            },
            {
                key: 'italic',
                label: 'Italic',
                icon: Italic,
                active: () => e.isActive('italic'),
                run: () => e.chain().focus().toggleItalic().run(),
            },
            {
                key: 'link',
                label: 'Link',
                icon: Link2,
                active: () => e.isActive('link'),
                run: () => void openBar('link'),
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
            {
                key: 'quote',
                label: 'Quote',
                icon: Quote,
                active: () => e.isActive('blockquote'),
                run: () => e.chain().focus().toggleBlockquote().run(),
            },
            {
                key: 'rule',
                label: 'Horizontal rule',
                icon: Minus,
                active: () => false,
                run: () => e.chain().focus().setHorizontalRule().run(),
            },
        ],
        [
            {
                key: 'image',
                label: 'Image from the library',
                icon: ImagePlus,
                active: () => e.isActive('image'),
                run: () => {
                    pickerOpen.value = true;
                },
            },
            {
                key: 'table',
                label: 'Table',
                icon: Table2,
                active: () => e.isActive('table'),
                run: () =>
                    e
                        .chain()
                        .focus()
                        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                        .run(),
                disabled: () => e.isActive('table'),
            },
            {
                key: 'youtube',
                label: 'YouTube video',
                icon: Video,
                active: () => false,
                run: () => void openBar('youtube'),
            },
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

const tableTools: Array<{
    label: string;
    run: (e: NonNullable<typeof editor.value>) => void;
}> = [
    { label: 'Add row', run: (e) => e.chain().focus().addRowAfter().run() },
    {
        label: 'Add column',
        run: (e) => e.chain().focus().addColumnAfter().run(),
    },
    { label: 'Delete row', run: (e) => e.chain().focus().deleteRow().run() },
    {
        label: 'Delete column',
        run: (e) => e.chain().focus().deleteColumn().run(),
    },
    {
        label: 'Delete table',
        run: (e) => e.chain().focus().deleteTable().run(),
    },
];
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
            style="--rte-lines: 18"
        >
            <div
                class="flex flex-wrap items-center gap-x-1 gap-y-1 border-b bg-muted/40 px-2 py-1.5"
                role="toolbar"
                aria-label="Article formatting"
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
                        :title="tool.hint?.() ?? tool.label"
                        :aria-pressed="
                            [
                                'undo',
                                'redo',
                                'rule',
                                'image',
                                'youtube',
                            ].includes(tool.key)
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
                v-if="bar"
                class="flex flex-wrap items-center gap-2 border-b bg-muted/20 px-3 py-2"
                @submit.prevent="applyBar"
                @keydown.esc.prevent.stop="closeBar"
            >
                <label :for="`${id}-bar`" class="text-sm font-medium">{{
                    bar === 'youtube' ? 'YouTube address' : 'Link to'
                }}</label>
                <Input
                    :id="`${id}-bar`"
                    ref="barInput"
                    v-model="barUrl"
                    class="h-9 min-w-48 flex-1"
                    :placeholder="
                        bar === 'youtube'
                            ? 'https://www.youtube.com/watch?v=…'
                            : 'https://… or /projects/…'
                    "
                    inputmode="url"
                    autocomplete="off"
                    :aria-invalid="barError ? true : undefined"
                />
                <Button type="submit" size="sm">{{
                    bar === 'youtube' ? 'Insert video' : 'Apply'
                }}</Button>
                <Button
                    v-if="bar === 'link' && editor?.isActive('link')"
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
                    @click="closeBar"
                >
                    Cancel
                </Button>
                <p
                    v-if="barError"
                    class="w-full text-sm text-destructive"
                    role="alert"
                >
                    {{ barError }}
                </p>
                <p
                    v-else-if="bar === 'youtube'"
                    class="w-full text-xs text-muted-foreground"
                >
                    The video loads from YouTube only when a visitor presses
                    play.
                </p>
            </form>

            <div
                v-if="inTable"
                class="flex flex-wrap items-center gap-1 border-b bg-muted/20 px-3 py-1.5"
                role="toolbar"
                aria-label="Table"
            >
                <span class="mr-1 text-xs font-medium text-muted-foreground"
                    >Table</span
                >
                <Button
                    v-for="tool in tableTools"
                    :key="tool.label"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="editor && tool.run(editor)"
                >
                    {{ tool.label }}
                </Button>
            </div>

            <div
                v-if="image"
                class="grid gap-3 border-b bg-muted/20 px-3 py-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
            >
                <div class="grid gap-1">
                    <label :for="`${id}-alt`" class="text-sm font-medium"
                        >Alt text</label
                    >
                    <Input
                        :id="`${id}-alt`"
                        class="h-9"
                        :model-value="image.alt"
                        :disabled="image.decorative"
                        placeholder="What the picture shows"
                        maxlength="125"
                        @update:model-value="setImage({ alt: String($event) })"
                    />
                </div>
                <div class="grid gap-1">
                    <label :for="`${id}-caption`" class="text-sm font-medium"
                        >Caption</label
                    >
                    <Input
                        :id="`${id}-caption`"
                        class="h-9"
                        :model-value="image.caption"
                        placeholder="Optional"
                        maxlength="255"
                        @update:model-value="
                            setImage({ caption: String($event) })
                        "
                    />
                </div>
                <label class="flex min-h-9 items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        class="size-4 accent-primary"
                        :checked="image.decorative"
                        @change="
                            setImage({
                                decorative: ($event.target as HTMLInputElement)
                                    .checked,
                            })
                        "
                    />
                    Decorative image
                </label>
                <p class="text-xs text-muted-foreground sm:col-span-3">
                    The alt text comes from the library. Change it here for this
                    article only. A decorative image has no alt text and is
                    skipped by screen readers.
                </p>
            </div>

            <EditorContent :editor="editor" class="rte-frame" />
        </div>

        <div
            v-if="issues.length"
            class="flex gap-2 rounded-md border border-warning/40 bg-warning-50 p-3 text-sm text-warning-700"
            role="status"
        >
            <TriangleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <ul class="grid gap-0.5">
                <li v-for="issue in issues" :key="issue.message">
                    {{ issue.message }}
                </li>
            </ul>
        </div>

        <p class="text-xs text-muted-foreground tabular-nums" aria-live="off">
            {{ words }} {{ words === 1 ? 'word' : 'words' }} ·
            {{ readMinutes }} min read
        </p>

        <MediaPicker
            v-model:open="pickerOpen"
            title="Insert an image"
            :kinds="['image']"
            insert-label="Insert image"
            multiple
            @insert="insertImages"
        />
    </div>
</template>
