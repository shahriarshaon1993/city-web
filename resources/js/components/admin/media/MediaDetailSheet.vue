<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Copy, ExternalLink, FileText, Replace, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import { destroy, show, update } from '@/routes/admin/media';
import type { MediaAssetDetail, MediaFolderTree } from '@/types';
import FocalPointPicker from './FocalPointPicker.vue';
import MediaFolderSelect from './MediaFolderSelect.vue';
import MediaReplaceDialog from './MediaReplaceDialog.vue';
import MediaTagsInput from './MediaTagsInput.vue';

// CMP-ADM-MEDIA detail drawer: preview, alt / caption / credit / title, focal point, tags, folder,
// usages (CMS-MED-007), Replace everywhere and Delete (CMS-MED-008). Limits mirror
// UpdateMediaAssetRequest (VAL-03); closing with unsaved changes asks first (VAL-06).
const props = defineProps<{ assetId: number | null; tree: MediaFolderTree }>();
const open = defineModel<boolean>('open', { default: false });

const confirm = useConfirm();
const detail = ref<MediaAssetDetail | null>(null);
const loading = ref(false);
const failed = ref(false);
const copied = ref(false);
const replaceOpen = ref(false);
const replaceMode = ref<'delete' | 'replace'>('replace');

const form = useForm({
    title: '',
    alt: '',
    is_decorative: false,
    caption: '',
    credit: '',
    tags: [] as string[],
    folder_id: null as number | null,
    focal_x: 50,
    focal_y: 50,
});

const focal = computed({
    get: () => ({ x: form.focal_x, y: form.focal_y }),
    set: (value: { x: number; y: number }) => {
        form.focal_x = value.x;
        form.focal_y = value.y;
    },
});

function fill(asset: MediaAssetDetail): void {
    form.defaults({
        title: asset.title,
        alt: asset.alt ?? '',
        is_decorative: asset.is_decorative,
        caption: asset.caption ?? '',
        credit: asset.credit ?? '',
        tags: [...asset.tags],
        folder_id: asset.folder_id,
        focal_x: asset.focal.x,
        focal_y: asset.focal.y,
    });
    form.reset();
    form.clearErrors();
}

async function load(id: number): Promise<void> {
    loading.value = true;
    failed.value = false;

    try {
        const response = await fetch(show.url(id), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        detail.value = (
            (await response.json()) as { data: MediaAssetDetail }
        ).data;
        fill(detail.value);
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

watch(
    () => [open.value, props.assetId] as const,
    ([isOpen, id]) => {
        if (isOpen && id !== null) {
            // Always fresh: usages and conversions change while the drawer is closed.
            if (detail.value?.id !== id) {
                detail.value = null;
            }

            void load(id);
        }
    },
    { immediate: true },
);

const canEdit = computed(() => detail.value?.can.edit ?? false);
const isImage = computed(
    () => detail.value?.kind === 'image' || detail.value?.kind === 'svg',
);
const usages = computed(() => detail.value?.usages ?? []);
const inUse = computed(() => usages.value.length > 0);

function save(): void {
    if (!detail.value || !canEdit.value) {
        return;
    }

    const id = detail.value.id;

    form.patch(update.url(id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => void load(id),
    });
}

// VAL-06: never lose typed text by closing the drawer.
async function requestClose(next: boolean): Promise<void> {
    if (next || !form.isDirty) {
        open.value = next;

        return;
    }

    const answer = await confirm({
        title: 'Discard your changes?',
        description: 'The changes to this file’s details are not saved yet.',
        confirmLabel: 'Discard changes',
        destructive: true,
    });

    if (answer) {
        form.reset();
        open.value = false;
    }
}

async function remove(): Promise<void> {
    const asset = detail.value;

    if (!asset) {
        return;
    }

    if (inUse.value) {
        replaceMode.value = 'delete';
        replaceOpen.value = true;

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
        router.delete(destroy.url(asset.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                open.value = false;
            },
        });
    }
}

function startReplace(): void {
    replaceMode.value = 'replace';
    replaceOpen.value = true;
}

function afterReplace(): void {
    if (replaceMode.value === 'delete') {
        open.value = false;
    } else if (detail.value) {
        void load(detail.value.id);
    }
}

async function copyUrl(): Promise<void> {
    if (!detail.value?.url) {
        return;
    }

    await navigator.clipboard.writeText(
        new URL(detail.value.url, window.location.origin).href,
    );
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 2000);
}

const facts = computed(() => {
    const asset = detail.value;

    if (!asset) {
        return [];
    }

    return [
        [
            'Type',
            asset.mime
                ? `${asset.kind_label} (${asset.mime})`
                : asset.kind_label,
        ],
        [
            'Dimensions',
            asset.width && asset.height
                ? `${asset.width} × ${asset.height} px`
                : null,
        ],
        ['Duration', asset.duration_s ? `${asset.duration_s} s` : null],
        ['Size', asset.size_label],
        ['Original name', asset.original_name],
        [
            'Uploaded',
            asset.created_at
                ? `${formatDateTime(asset.created_at)}${asset.uploaded_by ? ` · ${asset.uploaded_by}` : ''}`
                : null,
        ],
        [
            'Image sizes',
            asset.kind === 'image'
                ? {
                      pending: 'Being prepared…',
                      ready: 'Ready',
                      failed: 'Failed — the original is used',
                  }[asset.conversions_status]
                : null,
        ],
    ].filter((row): row is [string, string] => !!row[1]);
});
</script>

<template>
    <Sheet :open="open" @update:open="requestClose">
        <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
            <SheetHeader class="border-b px-6 py-4">
                <SheetTitle class="truncate pr-8">{{
                    detail?.title ?? 'File details'
                }}</SheetTitle>
                <SheetDescription>
                    {{
                        canEdit
                            ? 'Describe the file, set its focal point and see where it is used.'
                            : 'Details and where the file is used.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div
                    v-if="loading && !detail"
                    class="grid gap-4"
                    aria-busy="true"
                >
                    <Skeleton class="aspect-[4/3] w-full" />
                    <Skeleton class="h-4 w-2/3" />
                    <Skeleton class="h-4 w-1/2" />
                </div>

                <div
                    v-else-if="failed"
                    role="alert"
                    class="grid justify-items-start gap-3 text-sm"
                >
                    <p>
                        The file could not be loaded. It may have been deleted.
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="assetId && load(assetId)"
                        >Try again</Button
                    >
                </div>

                <form
                    v-else-if="detail"
                    id="media-detail-form"
                    class="grid gap-6"
                    @submit.prevent="save"
                >
                    <!-- preview / focal point (CMP-ADM-FOCAL) -->
                    <FocalPointPicker
                        v-if="
                            detail.kind === 'image' &&
                            detail.preview_url &&
                            canEdit
                        "
                        v-model="focal"
                        :src="detail.preview_url"
                        :width="detail.width"
                        :height="detail.height"
                        :alt="detail.alt ?? ''"
                    />
                    <img
                        v-else-if="detail.preview_url"
                        :src="detail.preview_url"
                        :alt="detail.alt ?? ''"
                        class="max-h-80 w-full rounded-md bg-muted object-contain"
                    />
                    <video
                        v-else-if="detail.kind === 'video' && detail.url"
                        :src="detail.url"
                        controls
                        preload="metadata"
                        class="w-full rounded-md bg-black"
                    />
                    <div
                        v-else
                        class="grid place-items-center gap-2 rounded-md bg-muted p-8 text-muted-foreground"
                    >
                        <FileText class="size-8" aria-hidden="true" />
                        <span class="text-sm">PDF document</span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="detail.url"
                            as="a"
                            :href="detail.url"
                            target="_blank"
                            rel="noopener"
                            variant="outline"
                            size="sm"
                        >
                            <ExternalLink aria-hidden="true" />
                            Open original
                            <span class="sr-only">(opens in a new tab)</span>
                        </Button>
                        <Button
                            v-if="detail.url"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="copyUrl"
                        >
                            <Copy aria-hidden="true" />
                            {{ copied ? 'Copied' : 'Copy link' }}
                        </Button>
                    </div>

                    <fieldset class="grid gap-5" :disabled="!canEdit">
                        <legend class="sr-only">Details</legend>

                        <div class="grid gap-2">
                            <Label for="media-title">
                                Title
                                <span
                                    class="text-destructive"
                                    aria-hidden="true"
                                    >*</span
                                >
                            </Label>
                            <Input
                                id="media-title"
                                v-model="form.title"
                                required
                                maxlength="150"
                                :aria-invalid="!!form.errors.title || undefined"
                            />
                            <CharCounter :value="form.title" :max="150" />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div v-if="isImage" class="grid gap-2">
                            <Label for="media-alt">
                                Alt text
                                <span
                                    v-if="!form.is_decorative"
                                    class="text-destructive"
                                    aria-hidden="true"
                                    >*</span
                                >
                            </Label>
                            <Textarea
                                id="media-alt"
                                v-model="form.alt"
                                rows="2"
                                maxlength="125"
                                :disabled="form.is_decorative || !canEdit"
                                :required="!form.is_decorative"
                                aria-describedby="media-alt-help"
                                :aria-invalid="!!form.errors.alt || undefined"
                            />
                            <p
                                id="media-alt-help"
                                class="text-xs text-muted-foreground"
                            >
                                What the image shows, for people who cannot see
                                it and for search engines. Leave out “image of”.
                            </p>
                            <CharCounter :value="form.alt" :max="125" />
                            <label class="flex items-start gap-2 text-sm">
                                <Checkbox
                                    v-model="form.is_decorative"
                                    class="mt-0.5"
                                />
                                <span>
                                    Decorative — adds nothing to the text, so
                                    screen readers skip it
                                </span>
                            </label>
                            <InputError :message="form.errors.alt" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="media-caption">Caption</Label>
                            <Textarea
                                id="media-caption"
                                v-model="form.caption"
                                rows="2"
                                maxlength="255"
                                :aria-invalid="
                                    !!form.errors.caption || undefined
                                "
                            />
                            <CharCounter :value="form.caption" :max="255" />
                            <InputError :message="form.errors.caption" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="media-credit">Credit</Label>
                            <Input
                                id="media-credit"
                                v-model="form.credit"
                                maxlength="120"
                                placeholder="Photo: …"
                                :aria-invalid="
                                    !!form.errors.credit || undefined
                                "
                            />
                            <InputError :message="form.errors.credit" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="media-tags">Tags</Label>
                            <MediaTagsInput
                                id="media-tags"
                                v-model="form.tags"
                                describedby="media-tags-help"
                            />
                            <p
                                id="media-tags-help"
                                class="text-xs text-muted-foreground"
                            >
                                Help people find the file in search. Up to 20
                                tags.
                            </p>
                            <InputError
                                :message="
                                    form.errors.tags ?? form.errors['tags.0']
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="media-folder">Folder</Label>
                            <MediaFolderSelect
                                id="media-folder"
                                v-model="form.folder_id"
                                :folders="tree.folders"
                                :invalid="!!form.errors.folder_id"
                            />
                            <InputError :message="form.errors.folder_id" />
                        </div>
                    </fieldset>

                    <dl
                        class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 border-t pt-4 text-sm"
                    >
                        <template v-for="[term, value] in facts" :key="term">
                            <dt class="text-muted-foreground">{{ term }}</dt>
                            <dd class="min-w-0 truncate" :title="value">
                                {{ value }}
                            </dd>
                        </template>
                    </dl>

                    <!-- CMS-MED-007 usages -->
                    <section
                        class="grid gap-3 border-t pt-4"
                        aria-labelledby="media-usages"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h3 id="media-usages" class="text-sm font-medium">
                                Used in
                                {{
                                    usages.length === 1
                                        ? '1 place'
                                        : `${usages.length} places`
                                }}
                            </h3>
                            <Button
                                v-if="inUse && canEdit"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="startReplace"
                            >
                                <Replace aria-hidden="true" />
                                Replace everywhere
                            </Button>
                        </div>
                        <ul
                            v-if="inUse"
                            class="divide-y rounded-md border text-sm"
                        >
                            <li
                                v-for="usage in usages"
                                :key="usage.id"
                                class="flex items-center gap-3 px-3 py-2"
                            >
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">{{
                                        usage.label
                                    }}</span>
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ usage.field }}</span
                                    >
                                </span>
                                <a
                                    v-if="usage.url"
                                    :href="usage.url"
                                    class="shrink-0 text-xs text-primary hover:underline"
                                    >Open</a
                                >
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">
                            Not used anywhere yet. It can be deleted safely.
                        </p>
                    </section>
                </form>
            </div>

            <SheetFooter
                v-if="detail"
                class="flex-row items-center justify-between gap-2 border-t px-6 py-4"
            >
                <ReasonButton
                    v-if="detail.can.delete_visible"
                    type="button"
                    variant="ghost"
                    class="text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 aria-hidden="true" />
                    Delete
                </ReasonButton>
                <span v-else />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="requestClose(false)"
                    >
                        {{ canEdit ? 'Cancel' : 'Close' }}
                    </Button>
                    <Button
                        v-if="canEdit"
                        type="submit"
                        form="media-detail-form"
                        :disabled="form.processing || !form.isDirty"
                    >
                        <Spinner v-if="form.processing" />
                        Save
                    </Button>
                </div>
            </SheetFooter>

            <MediaReplaceDialog
                v-if="detail && inUse"
                v-model:open="replaceOpen"
                :asset="detail"
                :mode="replaceMode"
                @done="afterReplace"
            />
        </SheetContent>
    </Sheet>
</template>
