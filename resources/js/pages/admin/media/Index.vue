<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    FolderInput,
    ImageOff,
    ImagePlus,
    PanelRightOpen,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import MediaDetailSheet from '@/components/admin/media/MediaDetailSheet.vue';
import MediaDropzone from '@/components/admin/media/MediaDropzone.vue';
import MediaFolderTree from '@/components/admin/media/MediaFolderTree.vue';
import MediaMoveDialog from '@/components/admin/media/MediaMoveDialog.vue';
import MediaTile from '@/components/admin/media/MediaTile.vue';
import MediaUploadQueue from '@/components/admin/media/MediaUploadQueue.vue';
import { Button } from '@/components/ui/button';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import type { UploadBatchResult } from '@/composables/useMediaUploads';
import { formatDateTime } from '@/lib/admin-format';
import { parseTableState, serializeTableState } from '@/lib/table-state';
import { cn } from '@/lib/utils';
import {
    bulk,
    destroy,
    index as mediaIndex,
    uploaded,
} from '@/routes/admin/media';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    MediaAsset,
    MediaFolderTree as FolderTree,
    MediaLimits,
    SortOption,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

const props = defineProps<{
    rows: MediaAsset[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    folders: FolderTree;
    limits: MediaLimits;
    openAsset: number | null;
    canShareViews: boolean;
    binCount: number;
}>();

const { can, canAny } = useCan();
const canUpload = computed(() => can('media.upload'));
const canEdit = computed(() => can('media.update'));
const canDelete = computed(() => canAny('media.delete', 'media.delete-unused'));

// design/admin.md §12.3 "Media (list view)": File, Type, Dimensions, Size, Folder, Usages, Uploaded.
const columns: DataTableColumn[] = [
    { id: 'title', label: 'File', required: true, sortable: true, width: 320 },
    { id: 'kind', label: 'Type', width: 110 },
    { id: 'dimensions', label: 'Dimensions', width: 140 },
    { id: 'bytes', label: 'Size', sortable: true, width: 110, align: 'right' },
    { id: 'folder', label: 'Folder', width: 160 },
    { id: 'usages', label: 'Usages', width: 100, align: 'right' },
    { id: 'created_at', label: 'Uploaded', sortable: true, width: 150 },
    { id: 'alt', label: 'Alt text', hidden: true, width: 240 },
];

// CMS-MED-009 sorts: newest / oldest / name / size.
const sortOptions: SortOption[] = [
    { value: '-created_at', label: 'Newest first' },
    { value: 'created_at', label: 'Oldest first' },
    { value: 'title', label: 'Name A–Z' },
    { value: '-bytes', label: 'Largest first' },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'kind',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.kind,
    },
    { id: 'uploaded', label: 'Uploaded', kind: 'date' },
    { id: 'missing_alt', label: 'Missing alt', kind: 'boolean', hidden: true },
]);

// --- folders (CMS-MED-002): links that set filter[folder], keeping the rest of the view --------
const page = usePage();
const perPageConfig = computed(() => ({
    options: props.meta.per_page_options ?? [24, 48, 96],
    fallback: props.meta.default_per_page ?? 24,
}));
const urlState = computed(() => parseTableState(page.url, perPageConfig.value));
const currentFolder = computed(() => urlState.value.filters.folder ?? '');
const currentFolderId = computed(() =>
    /^\d+$/.test(currentFolder.value) ? Number(currentFolder.value) : null,
);

function folderHref(key: string): string {
    const state = urlState.value;
    const nextFilters = { ...state.filters };

    if (key === '') {
        delete nextFilters.folder;
    } else {
        nextFilters.folder = key;
    }

    const query = serializeTableState(
        { ...state, filters: nextFilters, page: 1, view: null },
        [],
        perPageConfig.value.fallback,
    ).toString();

    return mediaIndex.url() + (query ? `?${query}` : '');
}

// --- detail drawer -------------------------------------------------------------------------
const detailOpen = ref(false);
const detailId = ref<number | null>(null);

function openDetail(asset: MediaAsset | number): void {
    detailId.value = typeof asset === 'number' ? asset : asset.id;
    detailOpen.value = true;
}

// --- upload ----------------------------------------------------------------------------------
const dropzone = ref<InstanceType<typeof MediaDropzone> | null>(null);

function onUploaded(result: UploadBatchResult): void {
    if (result.assets.length === 0 && result.failed === 0) {
        return;
    }

    // TOAST-05: one summary toast for the batch; the redirect brings the new tiles.
    router.post(
        uploaded.url(),
        {
            count: result.assets.length,
            images: result.images,
            failed: result.failed,
        },
        { preserveScroll: true, preserveState: true },
    );
}

// Tiles whose sizes are still being made refresh by themselves (conversions run on the queue).
let poll: number | undefined;
let polls = 0;

const pending = computed(() =>
    props.rows.some((row) => row.conversions_status === 'pending'),
);

function schedulePoll(): void {
    window.clearTimeout(poll);

    if (!pending.value || polls >= 30) {
        return;
    }

    poll = window.setTimeout(() => {
        polls++;
        router.reload({ only: ['rows'] });
    }, 4000);
}

watch(
    () => props.rows,
    () => schedulePoll(),
);

onMounted(() => {
    schedulePoll();

    if (props.openAsset) {
        openDetail(props.openAsset);
    }
});

onBeforeUnmount(() => window.clearTimeout(poll));

// --- delete / move -----------------------------------------------------------------------------
const confirm = useConfirm();
const moveOpen = ref(false);
const moveSelection = ref<TableSelection | null>(null);

function postBulk(
    payload: Record<string, unknown>,
    selection: TableSelection,
): void {
    router.post(
        bulk.url(),
        {
            ...payload,
            ...(selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids }),
        },
        { preserveScroll: true },
    );
}

async function deleteOne(asset: MediaAsset): Promise<void> {
    if ((asset.usages_count ?? 0) > 0) {
        // The drawer explains the usages and offers "Replace everywhere" (CMS-MED-008).
        openDetail(asset);

        return;
    }

    const answer = await confirm({
        title: 'Delete this file?',
        subject: asset.title,
        description:
            'It moves to the Recycle bin and is removed for good, with all its sizes, after 30 days. You can restore it from the bin until then.',
        confirmLabel: 'Delete file',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(asset.id), { preserveScroll: true });
    }
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const noun = selection.count === 1 ? 'file' : 'files';
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun}?`,
        description:
            'They move to the Recycle bin for 30 days. Files that are used somewhere are skipped and named; replace them first.',
        confirmLabel: `Delete ${noun}`,
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk({ action: 'delete' }, selection);
    }
}

function moveTo(folderId: number | null): void {
    if (moveSelection.value) {
        postBulk({ action: 'move', folder_id: folderId }, moveSelection.value);
    }
}

function rowActions(asset: MediaAsset): DataTableRowAction[] {
    return [
        {
            id: 'open',
            label: 'Open details',
            icon: PanelRightOpen,
            onSelect: () => openDetail(asset),
        },
        {
            id: 'move',
            label: 'Move to folder…',
            icon: FolderInput,
            hidden: !canEdit.value,
            onSelect: () => {
                moveSelection.value = {
                    ids: [asset.id],
                    all: false,
                    count: 1,
                    query: {},
                };
                moveOpen.value = true;
            },
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !asset.can.delete_visible,
            disabledReason: asset.blocked.delete,
            onSelect: () => void deleteOne(asset),
        },
    ];
}

const bulkActions = computed<DataTableBulkAction[]>(() => [
    ...(canEdit.value
        ? [
              {
                  id: 'move',
                  label: 'Move to folder',
                  icon: FolderInput,
                  run: (selection: TableSelection) => {
                      moveSelection.value = selection;
                      moveOpen.value = true;
                  },
              },
          ]
        : []),
    ...(canDelete.value
        ? [
              {
                  id: 'delete',
                  label: 'Delete',
                  icon: Trash2,
                  destructive: true,
                  run: (selection: TableSelection) =>
                      void deleteMany(selection),
              },
          ]
        : []),
]);

const folderName = computed(() => {
    if (currentFolder.value === 'unfiled') {
        return 'Unfiled';
    }

    const find = (nodes: FolderTree['folders']): string | null => {
        for (const node of nodes) {
            if (String(node.id) === currentFolder.value) {
                return node.path;
            }

            const hit = find(node.children);

            if (hit) {
                return hit;
            }
        }

        return null;
    };

    return currentFolder.value ? find(props.folders.folders) : null;
});
</script>

<template>
    <Head title="Media" />

    <ListPageHeader
        title="Media"
        description="Photos, videos and PDFs used across the site. Give images alt text and a focal point; a file that is in use cannot be deleted."
        :bin-count="canDelete ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'media_asset' } })"
    >
        <template v-if="canUpload" #actions>
            <Button @click="dropzone?.open()">
                <Upload aria-hidden="true" />
                Upload files
            </Button>
        </template>
    </ListPageHeader>

    <div class="grid items-start gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="grid gap-6 lg:sticky lg:top-20">
            <section aria-labelledby="media-folders-title">
                <h2
                    id="media-folders-title"
                    class="px-2.5 pb-2 text-sm font-semibold"
                >
                    Folders
                </h2>
                <MediaFolderTree
                    :tree="folders"
                    :current="currentFolder"
                    :href-for="folderHref"
                    :can-manage="canEdit"
                />
            </section>

            <section v-if="canUpload" aria-label="Upload">
                <MediaDropzone
                    ref="dropzone"
                    :limits="limits"
                    :folder-id="currentFolderId"
                    compact
                    @done="onUploaded"
                />
                <p
                    v-if="currentFolderId"
                    class="mt-2 px-1 text-center text-xs text-muted-foreground"
                >
                    New files go into “{{ folderName }}”.
                </p>
            </section>
        </aside>

        <div class="flex min-w-0 flex-col gap-4">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h2 class="text-lg leading-tight font-semibold">
                    {{
                        folderName ??
                        (currentFolder === 'unfiled' ? 'Unfiled' : 'All files')
                    }}
                </h2>
                <p class="text-sm text-muted-foreground tabular-nums">
                    {{ meta.total }} {{ meta.total === 1 ? 'file' : 'files' }}
                </p>
            </div>

            <DataTable
                table-key="media"
                noun="file"
                :columns="columns"
                :rows="rows"
                :meta="meta"
                :filters="filters"
                :views="views"
                :can-share-views="canShareViews"
                :sort-options="sortOptions"
                :locked-filters="['folder']"
                grid-view
                split-toolbar
                search-placeholder="Search by name, alt text, caption or tag"
                :row-label="(asset: MediaAsset) => asset.title"
                :row-actions="rowActions"
                :bulk-actions="bulkActions"
                :selectable="bulkActions.length > 0"
                clickable-rows
                empty-title="No files here yet"
                :empty-description="
                    canUpload
                        ? 'Drop photos, videos or PDFs on the upload box, or use “Upload files”.'
                        : 'Nothing has been uploaded yet.'
                "
                @row-click="openDetail"
            >
                <template #empty-icon>
                    <span
                        class="mb-1 grid size-16 place-items-center rounded-2xl bg-primary/10 text-primary"
                    >
                        <ImagePlus class="size-7" aria-hidden="true" />
                    </span>
                </template>
                <template v-if="canUpload" #empty-action>
                    <Button class="mt-2" @click="dropzone?.open()">
                        <Upload aria-hidden="true" />
                        Upload files
                    </Button>
                </template>

                <template #toolbar-filters="{ filters: active, setFilters }">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :class="
                            cn(
                                active.missing_alt === '1' &&
                                    'border-warning bg-warning-50 text-foreground',
                            )
                        "
                        :aria-pressed="active.missing_alt === '1'"
                        @click="
                            setFilters({
                                missing_alt:
                                    active.missing_alt === '1' ? null : '1',
                            })
                        "
                    >
                        <ImageOff aria-hidden="true" />
                        Missing alt
                    </Button>
                </template>

                <template
                    #grid="{ rows: tiles, isSelected, toggleSelected, search }"
                >
                    <ul
                        class="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-x-5 gap-y-6"
                        aria-label="Files"
                    >
                        <li v-for="asset in tiles" :key="asset.id">
                            <MediaTile
                                :asset="asset"
                                :selectable="bulkActions.length > 0"
                                :selected="isSelected(asset)"
                                :search="search"
                                @open="openDetail(asset)"
                                @toggle="
                                    (value) => toggleSelected(asset, value)
                                "
                            />
                        </li>
                    </ul>
                </template>

                <template #cell-title="{ row, search }">
                    <span class="flex min-w-0 items-center gap-3">
                        <span
                            class="relative grid size-10 shrink-0 place-items-center overflow-hidden rounded-md bg-muted ring-1 ring-foreground/10"
                            :style="
                                row.dominant_color
                                    ? { backgroundColor: row.dominant_color }
                                    : undefined
                            "
                        >
                            <img
                                v-if="row.thumb_url"
                                :src="row.thumb_url"
                                alt=""
                                loading="lazy"
                                class="size-full object-cover"
                            />
                            <span
                                v-if="row.missing_alt"
                                class="absolute top-0 right-0 size-0 border-t-[12px] border-l-[12px] border-t-warning border-l-transparent"
                                title="Missing alt text"
                            />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-medium"
                                ><HighlightText
                                    :text="row.title"
                                    :term="search"
                            /></span>
                            <span
                                v-if="row.missing_alt"
                                class="block text-xs text-muted-foreground"
                                >Missing alt text</span
                            >
                        </span>
                    </span>
                </template>
                <template #cell-kind="{ row }">{{ row.kind_label }}</template>
                <template #cell-dimensions="{ row }">
                    <span class="tabular-nums">{{
                        row.width && row.height
                            ? `${row.width} × ${row.height}`
                            : row.duration_s
                              ? `${row.duration_s} s`
                              : '–'
                    }}</span>
                </template>
                <template #cell-bytes="{ row }">
                    <span class="tabular-nums">{{
                        row.size_label ?? '–'
                    }}</span>
                </template>
                <template #cell-folder="{ row }">{{
                    row.folder ?? 'Unfiled'
                }}</template>
                <template #cell-usages="{ row }">
                    <span class="tabular-nums">{{
                        row.usages_count ?? 0
                    }}</span>
                </template>
                <template #cell-created_at="{ row }">
                    <span class="tabular-nums">{{
                        row.created_at
                            ? formatDateTime(row.created_at).split(' · ')[0]
                            : '–'
                    }}</span>
                </template>
                <template #cell-alt="{ row, search }">
                    <span v-if="row.is_decorative" class="text-muted-foreground"
                        >Decorative</span
                    >
                    <HighlightText
                        v-else-if="row.alt"
                        :text="row.alt"
                        :term="search"
                    />
                    <span v-else class="text-muted-foreground">–</span>
                </template>
            </DataTable>
        </div>
    </div>

    <MediaDetailSheet
        v-model:open="detailOpen"
        :asset-id="detailId"
        :tree="folders"
    />

    <MediaMoveDialog
        v-model:open="moveOpen"
        :count="moveSelection?.count ?? 0"
        :folders="folders.folders"
        @move="moveTo"
    />

    <MediaUploadQueue dock />
</template>
