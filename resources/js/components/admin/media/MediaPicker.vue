<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCan } from '@/composables/useCan';
import type { UploadBatchResult } from '@/composables/useMediaUploads';
import { browse } from '@/routes/admin/media';
import type {
    MediaAsset,
    MediaFolderTree,
    MediaKind,
    MediaLimits,
    TableMeta,
} from '@/types';
import MediaDropzone from './MediaDropzone.vue';
import MediaFolderTreeView from './MediaFolderTree.vue';
import MediaTile from './MediaTile.vue';
import MediaUploadQueue from './MediaUploadQueue.vue';

// CMP-ADM-PICKER / CMS-MED-010: the library in selection mode (single or multiple, type preset),
// with an "Upload new" tab, reused by every media field. It reads the library as JSON
// (admin.media.browse), so it works on any admin page without a page visit.
const props = withDefaults(
    defineProps<{
        title?: string;
        multiple?: boolean;
        /** Type preset; all kinds when absent. */
        kinds?: MediaKind[];
        /** Items that may not be picked (the asset being replaced). */
        excludeIds?: number[];
        insertLabel?: string;
    }>(),
    {
        title: 'Choose from the media library',
        multiple: false,
        kinds: undefined,
        excludeIds: () => [],
        insertLabel: 'Insert',
    },
);

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ insert: [assets: MediaAsset[]] }>();

const { can, canAny } = useCan();
const canBrowse = computed(() =>
    canAny('media.view', 'media.upload', 'media.update'),
);
const canUpload = computed(() => can('media.upload'));

const tab = ref<'library' | 'upload'>('library');
const search = ref('');
const folder = ref('');
const page = ref(1);
const rows = ref<MediaAsset[]>([]);
const meta = ref<TableMeta | null>(null);
const tree = ref<MediaFolderTree | null>(null);
const limits = ref<MediaLimits | null>(null);
const loading = ref(false);
const failed = ref(false);
const selected = ref<Map<number, MediaAsset>>(new Map());

let controller: AbortController | null = null;

async function load(withFolders = false): Promise<void> {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    failed.value = false;

    const query: Record<string, string | number> = {
        page: page.value,
        per_page: 24,
    };

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    if (props.kinds?.length) {
        query['filter[kind]'] = props.kinds.join(',');
    }

    if (folder.value) {
        query['filter[folder]'] = folder.value;
    }

    if (withFolders || tree.value === null) {
        query.with_folders = 1;
    }

    try {
        const response = await fetch(browse.url({ query }), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const data = (await response.json()) as {
            rows: MediaAsset[];
            meta: TableMeta;
            folders: MediaFolderTree | null;
            limits: MediaLimits | null;
        };

        rows.value = data.rows;
        meta.value = data.meta;
        tree.value = data.folders ?? tree.value;
        limits.value = data.limits ?? limits.value;
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            failed.value = true;
        }
    } finally {
        loading.value = false;
    }
}

const reloadForSearch = useDebounceFn(() => {
    page.value = 1;
    void load();
}, 300);

watch(search, () => reloadForSearch());

watch(open, (isOpen) => {
    if (isOpen) {
        selected.value = new Map();
        tab.value = canBrowse.value ? 'library' : 'upload';

        if (canBrowse.value) {
            void load(true);
        }
    } else {
        controller?.abort();
    }
});

function selectFolder(key: string): void {
    folder.value = key;
    page.value = 1;
    void load();
}

function goTo(next: number): void {
    page.value = next;
    void load();
}

function toggle(asset: MediaAsset, value: boolean): void {
    const next = new Map(props.multiple ? selected.value : []);

    if (value) {
        next.set(asset.id, asset);
    } else {
        next.delete(asset.id);
    }

    selected.value = next;
}

function onUploaded(result: UploadBatchResult): void {
    const fresh = result.assets.filter(
        (asset) => !props.excludeIds.includes(asset.id),
    );

    if (fresh.length === 0) {
        return;
    }

    const next = new Map(props.multiple ? selected.value : []);

    for (const asset of props.multiple ? fresh : fresh.slice(0, 1)) {
        next.set(asset.id, asset);
    }

    selected.value = next;

    if (canBrowse.value) {
        tab.value = 'library';
        folder.value = '';
        search.value = '';
        page.value = 1;
        void load(true);
    }
}

function insert(): void {
    emit('insert', [...selected.value.values()]);
    open.value = false;
}

const visibleRows = computed(() =>
    rows.value.filter((asset) => !props.excludeIds.includes(asset.id)),
);
const count = computed(() => selected.value.size);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="grid max-h-[calc(100dvh-2rem)] grid-rows-[auto_1fr_auto] gap-4 sm:max-w-[1080px]"
        >
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{
                        multiple
                            ? 'Pick one or more files, or upload new ones.'
                            : 'Pick a file, or upload a new one.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="tab" class="min-h-0 overflow-hidden">
                <TabsList>
                    <TabsTrigger v-if="canBrowse" value="library"
                        >Library</TabsTrigger
                    >
                    <TabsTrigger v-if="canUpload" value="upload"
                        >Upload new</TabsTrigger
                    >
                </TabsList>

                <TabsContent v-if="canBrowse" value="library" class="min-h-0">
                    <div class="grid min-h-0 gap-4 md:grid-cols-[220px_1fr]">
                        <div
                            class="hidden max-h-[60dvh] overflow-y-auto md:block"
                        >
                            <MediaFolderTreeView
                                v-if="tree"
                                :tree="tree"
                                :current="folder"
                                @select="selectFolder"
                            />
                        </div>

                        <div
                            class="grid min-h-0 grid-rows-[auto_1fr_auto] gap-3"
                        >
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <label for="media-picker-search" class="sr-only"
                                    >Search files</label
                                >
                                <Input
                                    id="media-picker-search"
                                    v-model="search"
                                    type="search"
                                    class="h-9 pr-8 pl-9 text-sm"
                                    placeholder="Search by name, alt text, caption or tag"
                                    maxlength="100"
                                    autocomplete="off"
                                />
                                <button
                                    v-if="search"
                                    type="button"
                                    class="absolute top-1/2 right-1.5 grid size-6 -translate-y-1/2 place-items-center rounded-full hover:bg-accent"
                                    aria-label="Clear search"
                                    @click="search = ''"
                                >
                                    <X class="size-3.5" aria-hidden="true" />
                                </button>
                            </div>

                            <div
                                class="max-h-[52dvh] min-h-48 overflow-y-auto"
                                :aria-busy="loading"
                            >
                                <div
                                    v-if="failed"
                                    role="alert"
                                    class="grid h-48 place-items-center gap-2 text-center text-sm"
                                >
                                    <p>
                                        The library could not be loaded. Check
                                        your connection.
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="load()"
                                        >Try again</Button
                                    >
                                </div>
                                <div
                                    v-else-if="loading && rows.length === 0"
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                                >
                                    <Skeleton
                                        v-for="n in 8"
                                        :key="n"
                                        class="aspect-[4/3]"
                                    />
                                </div>
                                <div
                                    v-else-if="visibleRows.length === 0"
                                    class="grid h-48 place-items-center text-center text-sm text-muted-foreground"
                                >
                                    {{
                                        search
                                            ? 'No files match that search.'
                                            : 'No files here yet.'
                                    }}
                                </div>
                                <ul
                                    v-else
                                    class="grid grid-cols-2 gap-3 transition-opacity sm:grid-cols-3 lg:grid-cols-4"
                                    :class="loading ? 'opacity-60' : ''"
                                >
                                    <li
                                        v-for="asset in visibleRows"
                                        :key="asset.id"
                                    >
                                        <MediaTile
                                            :asset="asset"
                                            pick-mode
                                            :selected="selected.has(asset.id)"
                                            :search="search"
                                            @toggle="
                                                (value) => toggle(asset, value)
                                            "
                                        />
                                    </li>
                                </ul>
                            </div>

                            <nav
                                v-if="meta && meta.last_page > 1"
                                class="flex items-center justify-between gap-2 text-sm"
                                aria-label="Library pages"
                            >
                                <span
                                    class="text-muted-foreground tabular-nums"
                                >
                                    {{ meta.from }}–{{ meta.to }} of
                                    {{ meta.total }}
                                </span>
                                <span class="flex gap-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="meta.page <= 1"
                                        @click="goTo(meta.page - 1)"
                                        >Previous</Button
                                    >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="meta.page >= meta.last_page"
                                        @click="goTo(meta.page + 1)"
                                        >Next</Button
                                    >
                                </span>
                            </nav>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent v-if="canUpload" value="upload" class="grid gap-4">
                    <MediaDropzone
                        v-if="limits"
                        :limits="limits"
                        :kinds="kinds"
                        :multiple="multiple"
                        :folder-id="
                            folder && folder !== 'unfiled'
                                ? Number(folder)
                                : null
                        "
                        @done="onUploaded"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        Loading upload limits…
                    </p>
                    <MediaUploadQueue />
                </TabsContent>
            </Tabs>

            <DialogFooter class="items-center gap-3 sm:justify-between">
                <p class="text-sm text-muted-foreground" aria-live="polite">
                    {{ count === 0 ? 'Nothing selected' : `${count} selected` }}
                </p>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="button"
                        :disabled="count === 0"
                        @click="insert"
                    >
                        {{ insertLabel
                        }}<template v-if="multiple && count > 1">
                            ({{ count }})</template
                        >
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
