<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { Rocket } from '@lucide/vue';
import {
    computed,
    nextTick,
    onMounted,
    provide,
    reactive,
    ref,
    watch,
} from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import ErrorBadge from '@/components/admin/ErrorBadge.vue';
import SlugField from '@/components/admin/SlugField.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import AddBlockDialog from '@/components/admin/pages/AddBlockDialog.vue';
import BlockDrawer from '@/components/admin/pages/BlockDrawer.vue';
import BlockList from '@/components/admin/pages/BlockList.vue';
import BlockPanel from '@/components/admin/pages/BlockPanel.vue';
import BlockPreview from '@/components/admin/pages/BlockPreview.vue';
import PageRail from '@/components/admin/pages/PageRail.vue';
import { BLOCK_BY_TYPE } from '@/components/admin/pages/blocks';
import {
    blockFieldId,
    pageEditorKey,
    totalErrors,
    withKeys,
} from '@/components/admin/pages/context';
import { useBlockOperations } from '@/components/admin/pages/useBlockOperations';
import { errorView } from '@/components/admin/projects/context';
import ConflictDialog from '@/components/admin/publishing/ConflictDialog.vue';
import RevisionsPanel from '@/components/admin/publishing/RevisionsPanel.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useConfirm } from '@/composables/useConfirm';
import { usePublishingEditor } from '@/composables/usePublishingEditor';
import { cn } from '@/lib/utils';
import {
    autosave,
    destroy,
    duplicate,
    index as pagesIndex,
    store,
    update,
} from '@/routes/admin/pages';
import type {
    AssetMap,
    BlockErrorMap,
    ChecklistItem,
    PageFormData,
    PageOptions,
    PageSummary,
    PreviewResult,
} from '@/types';

// ADM-SCR-06 page builder (CMP-ADM-BUILDER, design/admin.md §12.5): block list on the left, live preview
// in the middle, the block form in a drawer (a column beside the preview from 1700 px). Tabs: Builder ·
// Page (title, address) · SEO · Revisions. A legal page is one text and a "last updated" date instead of
// blocks. Save, autosave, the conflict dialog and the publishing workflow are the shared ones (14.1).
const props = defineProps<{
    page: PageSummary | null;
    form: PageFormData;
    assets: AssetMap;
    options: PageOptions;
    checklist: ChecklistItem[];
    lastSaved: string | null;
    can: {
        publish: boolean;
        delete: boolean;
        duplicate: boolean;
        update: boolean;
    };
    previewFrame: string;
}>();

const form = useForm<PageFormData>(withKeys(props.form));
const confirm = useConfirm();
const isLegal = computed(() => form.template === 'legal');

const editor = usePublishingEditor({
    form,
    workflow: () => props.page,
    lastSaved: () => props.lastSaved,
    autosaveUrl: () => (props.page ? autosave.url(props.page.id) : null),
    canEdit: () => props.can.update,
    reseed: () => {
        form.defaults(withKeys(props.form));
        form.reset();
    },
});

// --- selection ----------------------------------------------------------------------------------------
const selectedId = ref<number | null>(null);
const hoverId = ref<number | null>(null);
const drawerOpen = ref(false);
const wide = useMediaQuery('(min-width: 1700px)');
const adding = ref(false);
const publishing = ref(false);

function select(id: number | null): void {
    selectedId.value = id;
    // Below 1700 px the block form is a sheet; from there it is the column beside the preview.
    drawerOpen.value = id !== null && !wide.value;
}

watch(wide, (isWide) => {
    if (isWide) {
        drawerOpen.value = false;
    } else {
        drawerOpen.value = selectedId.value !== null;
    }
});

watch(drawerOpen, (open) => {
    if (!open && !wide.value) {
        selectedId.value = null;
    }
});

const ops = useBlockOperations(form, props.options.block_limit, select);
const selectedIndex = computed(() =>
    form.blocks.findIndex((block) => block.id === selectedId.value),
);
const selectedBlock = computed(() =>
    selectedIndex.value >= 0 ? form.blocks[selectedIndex.value] : null,
);
const hasOpener = computed(() => {
    const first = form.blocks.find((block) => block.is_visible);

    return (
        first !== undefined &&
        BLOCK_BY_TYPE[first.type].fields.length > 0 &&
        ['hero', 'page_header'].includes(first.type)
    );
});

// --- live checks (CMS-PB-003) ---------------------------------------------------------------------------
const blockErrors = reactive<Record<string, BlockErrorMap>>({});
const checklist = ref<ChecklistItem[]>(props.checklist);

function onPreview(result: PreviewResult): void {
    for (const key of Object.keys(blockErrors)) {
        delete blockErrors[key];
    }

    Object.assign(blockErrors, result.errors);
    checklist.value = result.checklist;
}

const invalidBlocks = computed(
    () =>
        form.blocks.filter(
            (block) => totalErrors(blockErrors[String(block.id)]) > 0,
        ).length,
);

provide(assetMapKey, createAssetMap(props.assets));
provide(
    pageEditorKey,
    reactive({
        form,
        options: props.options,
        errors: errorView(form),
        blockErrors,
        canEdit: props.can.update,
    }),
);

// A saved page starts from the server's values again (real block ids, trimmed text). Only a save changes
// `lastSaved`, so a failed save keeps everything that was typed (VAL-06). The open block stays open.
watch(
    () => props.lastSaved,
    () => {
        const position = selectedIndex.value;
        form.defaults(withKeys(props.form));
        form.reset();
        checklist.value = props.checklist;

        if (position >= 0) {
            selectedId.value = form.blocks[position]?.id ?? null;
        }
    },
);

onMounted(() => {
    // A legal page is one text block; make sure it exists so the editor has something to write in.
    if (
        isLegal.value &&
        !form.blocks.some((block) => block.type === 'rich_text')
    ) {
        ops.add('rich_text');
        selectedId.value = null;
        form.defaults(form.data());
    }
});

// --- tabs -----------------------------------------------------------------------------------------------
type TabKey = 'builder' | 'page' | 'seo' | 'revisions';
const tab = ref<TabKey>('builder');

const tabs = computed(
    () =>
        [
            { key: 'builder', label: isLegal.value ? 'Text' : 'Builder' },
            { key: 'page', label: 'Page' },
            { key: 'seo', label: 'SEO' },
            { key: 'revisions', label: 'Revisions' },
        ].filter((item) => item.key !== 'revisions' || props.page !== null) as {
            key: TabKey;
            label: string;
        }[],
);

function tabOfError(key: string): TabKey {
    if (key.startsWith('seo')) {
        return 'seo';
    }

    return key.startsWith('blocks') || key === 'last_reviewed_at'
        ? 'builder'
        : 'page';
}

const tabErrors = computed(() => {
    const counts: Record<string, number> = { builder: invalidBlocks.value };

    for (const key of Object.keys(form.errors)) {
        const target = tabOfError(key);
        counts[target] = (counts[target] ?? 0) + 1;
    }

    return counts;
});

function onTabKey(event: KeyboardEvent, index: number): void {
    const last = tabs.value.length - 1;
    const next =
        { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: last }[
            event.key
        ] ?? null;

    if (next === null) {
        return;
    }

    event.preventDefault();
    const target = tabs.value[(next + last + 1) % (last + 1)];
    tab.value = target.key;
    void nextTick(() =>
        document.getElementById(`page-tab-${target.key}`)?.focus(),
    );
}

// --- error links (VAL-07) ---------------------------------------------------------------------------------
function fieldIdOfError(key: string): string {
    const block = /^blocks\.(\d+)\.(.+)$/.exec(key);

    if (block) {
        const target = form.blocks[Number(block[1])];

        return target ? blockFieldId(target.id, block[2]) : 'field-blocks';
    }

    if (key.startsWith('seo.')) {
        return `seo-${key.slice(4).replace('og_image_asset_id', 'og-image').replace(/_/g, '-')}`;
    }

    return `field-${key.replace(/\./g, '-')}`;
}

async function focusField(id: string): Promise<void> {
    await nextTick();
    const element = document.getElementById(id);
    const control = element?.matches('input, select, textarea, button')
        ? element
        : element?.querySelector<HTMLElement>(
              'input:not([type="hidden"]), select, textarea, button, [tabindex="0"]',
          );

    (control ?? element)?.scrollIntoView({ block: 'center' });
    (control ?? element)?.focus({ preventScroll: true });
}

/** An error summary link: show the tab (and the block) that holds the field, then focus it. */
function reveal(id: string): void {
    const key = Object.keys(form.errors).find(
        (item) => fieldIdOfError(item) === id,
    );

    if (key) {
        tab.value = tabOfError(key);
        const block = /^blocks\.(\d+)\./.exec(key);

        if (block && form.blocks[Number(block[1])]) {
            select(form.blocks[Number(block[1])].id);
        }
    }

    // The block form renders after the selection changes.
    setTimeout(() => void focusField(id), 120);
}

/** A checklist "Fix" link (CMP-ADM-CHECKLIST): the builder, on the first block that needs attention. */
function fix(): void {
    publishing.value = false;
    tab.value = 'builder';

    const broken = form.blocks.find(
        (block) => totalErrors(blockErrors[String(block.id)]) > 0,
    );

    if (broken) {
        select(broken.id);
    } else if (form.blocks.length === 0) {
        adding.value = true;
    }
}

// --- save and verbs -----------------------------------------------------------------------------------------
const isDraft = computed(
    () => !props.page || props.page.status !== 'published',
);

function save(overwrite = false): void {
    editor.prepare(overwrite);

    if (props.page) {
        form.patch(update.url(props.page.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

function duplicateNow(): void {
    if (props.page) {
        router.post(duplicate.url(props.page.id));
    }
}

async function deleteNow(): Promise<void> {
    if (!props.page) {
        return;
    }

    const live = props.page.status === 'published';
    const answer = await confirm({
        title: 'Delete this page?',
        subject: props.page.title,
        description: live
            ? 'It leaves the website: visitors will see “page not found” unless you add a redirect under SEO › Redirects. The page moves to the Recycle bin and can be restored as a draft.'
            : 'The page moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete page',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.page.id));
    }
}

async function removeBlock(id: number): Promise<void> {
    ops.remove(id);
}

const title = computed(
    () => form.title.trim() || props.page?.title || 'New page',
);
const path = computed(() => props.page?.path ?? `/${form.slug || 'page-name'}`);
const legalBlock = computed(
    () => form.blocks.find((block) => block.type === 'rich_text') ?? null,
);

const blockCount = computed(() => form.blocks.length);
</script>

<template>
    <Head :title="title" />

    <AdminEditor
        :title="title"
        :description="
            isLegal
                ? 'The text of this legal page. Drafts stay hidden until you publish them.'
                : page
                  ? 'Build the page from blocks, see it as visitors will, and publish when it is ready.'
                  : 'Name the page and add its first blocks: it stays a draft until you publish it.'
        "
        :form="form"
        :last-saved="editor.savedAt.value"
        :note="editor.autosaveNote.value"
        :save-label="isDraft ? 'Save draft' : 'Save changes'"
        :save-reason="
            can.update ? null : 'You can read this page but not change it.'
        "
        :field-id="fieldIdOfError"
        :rail="false"
        @save="save()"
        @reveal="reveal"
    >
        <template #actions>
            <Link
                :href="pagesIndex.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All pages</Link
            >
        </template>

        <template #tabs>
            <div
                class="-mb-px flex gap-5 overflow-x-auto"
                role="tablist"
                aria-label="Page sections"
            >
                <button
                    v-for="(item, index) in tabs"
                    :id="`page-tab-${item.key}`"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.key"
                    :aria-controls="`page-panel-${item.key}`"
                    :tabindex="tab === item.key ? 0 : -1"
                    :class="
                        cn(
                            'inline-flex h-11 shrink-0 items-center gap-1.5 border-b-2 px-1 text-sm font-medium whitespace-nowrap transition-colors',
                            tab === item.key
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="tab = item.key"
                    @keydown="onTabKey($event, index)"
                >
                    {{ item.label }}
                    <span
                        v-if="item.key === 'builder' && !isLegal"
                        class="text-xs font-normal text-muted-foreground"
                        >{{ blockCount }}</span
                    >
                    <ErrorBadge :count="tabErrors[item.key] ?? 0" />
                </button>
            </div>
        </template>

        <template #bar>
            <Button type="button" variant="outline" @click="publishing = true">
                <Rocket aria-hidden="true" />
                Publishing
                <span
                    v-if="checklist.some((item) => !item.passed)"
                    class="ml-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-warning px-1.5 text-xs font-medium text-warning-foreground"
                >
                    {{ checklist.filter((item) => !item.passed).length }}
                </span>
            </Button>
        </template>

        <!-- Builder -->
        <section
            id="page-panel-builder"
            v-show="tab === 'builder'"
            role="tabpanel"
            aria-labelledby="page-tab-builder"
        >
            <!-- Legal page: one text and a date -->
            <div v-if="isLegal" class="grid max-w-[1100px] gap-6">
                <AdminSection
                    title="Text"
                    description="Headings (H2 to H4), paragraphs, lists and links. Visitors see a table of contents built from the headings."
                >
                    <FormField
                        v-if="legalBlock"
                        id="field-legal-text"
                        label="Page text"
                        required
                        :error="form.errors['blocks.0.data.body']"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-legal-text"
                                v-model="legalBlock.data.body"
                                :rows="18"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </AdminSection>
                <AdminSection
                    title="Last updated"
                    description="Shown at the top of the page. Change it when the text changes in a way visitors should know about."
                >
                    <div class="max-w-xs">
                        <FormField
                            id="field-last_reviewed_at"
                            label="Last updated"
                            :error="form.errors.last_reviewed_at"
                        >
                            <template #default="{ describedby, invalid }">
                                <Input
                                    id="field-last_reviewed_at"
                                    :model-value="form.last_reviewed_at ?? ''"
                                    type="date"
                                    :aria-invalid="invalid || undefined"
                                    :aria-describedby="describedby"
                                    @update:model-value="
                                        form.last_reviewed_at =
                                            String($event) || null
                                    "
                                />
                            </template>
                        </FormField>
                    </div>
                </AdminSection>
            </div>

            <!-- Builder page: list | preview | block form -->
            <div
                v-else
                :class="
                    cn(
                        'grid items-start gap-4 lg:grid-cols-[20rem_minmax(0,1fr)]',
                        wide && '2xl:grid-cols-[20rem_minmax(0,1fr)_30rem]',
                    )
                "
            >
                <div class="lg:sticky lg:top-20">
                    <BlockList
                        :blocks="form.blocks"
                        :selected-id="selectedId"
                        :hover-id="hoverId"
                        :at-limit="ops.atLimit.value"
                        :limit="options.block_limit"
                        :disabled="!can.update"
                        @select="select"
                        @add="adding = true"
                        @duplicate="ops.duplicate"
                        @remove="removeBlock"
                        @toggle="ops.toggleVisible"
                        @move="ops.move"
                        @reorder="form.blocks = $event"
                        @hover="hoverId = $event"
                    />
                </div>

                <BlockPreview
                    :frame-url="previewFrame"
                    :page-id="page?.id ?? null"
                    :title="form.title"
                    :blocks="form.blocks"
                    :selected-id="selectedId"
                    :hover-id="hoverId"
                    @select="select"
                    @hover="hoverId = $event"
                    @result="onPreview"
                />

                <aside
                    v-if="wide"
                    class="sticky top-20 hidden max-h-[calc(100dvh-6rem)] min-h-64 flex-col overflow-hidden rounded-xl border bg-card 2xl:flex"
                    aria-label="Block settings"
                >
                    <BlockPanel
                        v-if="selectedBlock"
                        :block="selectedBlock"
                        :index="selectedIndex"
                        :at-limit="ops.atLimit.value"
                        @duplicate="ops.duplicate"
                        @remove="removeBlock"
                    />
                    <div
                        v-else
                        class="grid flex-1 place-content-center gap-1 p-8 text-center text-sm text-muted-foreground"
                    >
                        <p class="font-medium text-foreground">
                            Select a block
                        </p>
                        <p>
                            Click a block in the list or in the preview to edit
                            it here.
                        </p>
                    </div>
                </aside>
            </div>
        </section>

        <!-- Page -->
        <section
            id="page-panel-page"
            v-show="tab === 'page'"
            role="tabpanel"
            aria-labelledby="page-tab-page"
            class="grid max-w-[1100px] gap-6"
        >
            <AdminSection
                title="Identity"
                description="The page name and where it lives on the website."
            >
                <div class="grid gap-5">
                    <TextField
                        id="field-title"
                        v-model="form.title"
                        label="Page title"
                        required
                        :maxlength="120"
                        helper="Used in the browser tab, in search results and in menus. The heading on the page comes from its first block."
                        :error="form.errors.title"
                    />
                    <SlugField
                        v-if="!page?.is_system"
                        id="field-slug"
                        v-model="form.slug"
                        :from="form.title"
                        source="page"
                        :ignore-id="page?.id ?? null"
                        :published="page?.status === 'published'"
                        prefix="/"
                        label="URL"
                        required
                        :error="form.errors.slug"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        The address
                        <code
                            class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs"
                            >{{ page?.path }}</code
                        >
                        belongs to a system page and cannot be changed.
                    </p>
                </div>
            </AdminSection>
        </section>

        <!-- SEO -->
        <section
            id="page-panel-seo"
            v-show="tab === 'seo'"
            role="tabpanel"
            aria-labelledby="page-tab-seo"
            class="grid max-w-[1100px] gap-6"
        >
            <AdminSection
                title="Search and sharing"
                description="How the page looks in Google and when its link is shared."
            >
                <SeoPanel
                    v-model="form.seo"
                    :path="path"
                    :fallback-title="`${form.title || 'Page title'} | Gemcon City`"
                    :fallback-description="`${form.title || 'This page'} at Gemcon City.`"
                    :errors="form.errors"
                />
            </AdminSection>
        </section>

        <!-- Revisions -->
        <section
            v-if="page"
            id="page-panel-revisions"
            v-show="tab === 'revisions'"
            role="tabpanel"
            aria-labelledby="page-tab-revisions"
            class="grid max-w-[1100px] gap-6"
        >
            <AdminSection
                title="Revisions"
                description="Pending changes and what was published before. Compare a version with the live page, or restore it."
            >
                <RevisionsPanel
                    type="page"
                    :id="page.id"
                    :refresh-key="`${page.lock_version}:${page.status}`"
                    :can-restore="can.update"
                    noun="page"
                />
            </AdminSection>
        </section>
    </AdminEditor>

    <!-- Block form below 1700 px -->
    <BlockDrawer
        v-if="!wide && !isLegal"
        v-model:open="drawerOpen"
        :block="selectedBlock"
        :index="selectedIndex"
        :at-limit="ops.atLimit.value"
        @duplicate="ops.duplicate"
        @remove="removeBlock"
    />

    <AddBlockDialog
        v-model:open="adding"
        :has-opener="hasOpener || form.blocks.length > 0"
        @choose="(type) => ops.add(type, selectedId)"
    />

    <!-- Publishing workflow -->
    <Sheet v-model:open="publishing">
        <SheetContent side="right" class="w-full gap-0 p-0 sm:max-w-sm">
            <SheetHeader class="border-b p-5 pr-12">
                <SheetTitle>Publishing</SheetTitle>
                <SheetDescription
                    >Status, schedule, preview and the checklist for this
                    page.</SheetDescription
                >
            </SheetHeader>
            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <PageRail
                    :page="page"
                    :checklist="checklist"
                    :dirty="form.isDirty"
                    :can="can"
                    :path="path"
                    @duplicate="duplicateNow"
                    @delete="deleteNow"
                    @fix="fix"
                />
            </div>
        </SheetContent>
    </Sheet>

    <ConflictDialog
        v-model:open="editor.conflictOpen.value"
        :details="editor.conflict.value"
        :can-overwrite="page?.can_overwrite ?? false"
        :busy="form.processing"
        noun="page"
        @reload="editor.reload()"
        @overwrite="editor.overwrite(save)"
    />
</template>
