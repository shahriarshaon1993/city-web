<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { replace } from '@/routes/admin/media';
import type { MediaAsset, MediaAssetDetail } from '@/types';
import MediaPicker from './MediaPicker.vue';

// CMS-MED-008 / DEL-03: a used file cannot be deleted. The dialog says why, lists every usage
// with its link, and offers "Replace everywhere" (pick another file; all usages move in one
// transaction) — in `delete` mode the freed file then goes to the Recycle bin.
const props = defineProps<{
    asset: MediaAssetDetail;
    mode: 'delete' | 'replace';
}>();
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ done: [] }>();

const pickerOpen = ref(false);
const processing = ref(false);
const error = ref<string | null>(null);

function onPicked(assets: MediaAsset[]): void {
    const replacement = assets[0];

    if (!replacement) {
        return;
    }

    processing.value = true;
    error.value = null;

    router.post(
        replace.url(props.asset.id),
        {
            replacement_id: replacement.id,
            delete_after: props.mode === 'delete',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                emit('done');
            },
            onError: (errors) => {
                error.value =
                    errors.replacement_id ?? Object.values(errors)[0] ?? null;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

const places = (count: number): string =>
    count === 1 ? '1 place' : `${count} places`;
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        mode === 'delete'
                            ? 'This file is in use'
                            : 'Replace everywhere'
                    }}
                </DialogTitle>
                <DialogDescription>
                    <template v-if="mode === 'delete'">
                        “{{ asset.title }}” is used in
                        {{ places(asset.usages.length) }}, so it cannot be
                        deleted. Replace it with another file everywhere, and it
                        then moves to the Recycle bin — or cancel and remove it
                        from those pages first.
                    </template>
                    <template v-else>
                        Every place below will show the file you pick instead of
                        “{{ asset.title }}”. This file stays in the library.
                    </template>
                </DialogDescription>
            </DialogHeader>

            <ul
                class="max-h-60 divide-y overflow-y-auto rounded-md border text-sm"
            >
                <li
                    v-for="usage in asset.usages"
                    :key="usage.id"
                    class="flex items-center gap-3 px-3 py-2"
                >
                    <span class="min-w-0 flex-1">
                        <span class="block truncate">{{ usage.label }}</span>
                        <span class="block text-xs text-muted-foreground">{{
                            usage.field
                        }}</span>
                    </span>
                    <a
                        v-if="usage.url"
                        :href="usage.url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex shrink-0 items-center gap-1 text-xs text-primary hover:underline"
                    >
                        Open
                        <ExternalLink class="size-3" aria-hidden="true" />
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </li>
            </ul>

            <p v-if="error" role="alert" class="text-sm text-destructive">
                {{ error }}
            </p>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false"
                    >Cancel</Button
                >
                <Button
                    type="button"
                    :disabled="processing"
                    @click="pickerOpen = true"
                >
                    <Spinner v-if="processing" />
                    {{
                        mode === 'delete'
                            ? 'Replace everywhere and delete…'
                            : 'Pick the replacement…'
                    }}
                </Button>
            </DialogFooter>

            <MediaPicker
                v-model:open="pickerOpen"
                title="Pick the replacement"
                :kinds="asset.replacement_kinds"
                :exclude-ids="[asset.id]"
                insert-label="Replace everywhere"
                @insert="onPicked"
            />
        </DialogContent>
    </Dialog>
</template>
