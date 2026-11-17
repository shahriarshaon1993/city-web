<script setup lang="ts">
import { computed } from 'vue';
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
import { formatDateTime } from '@/lib/admin-format';
import type { ConflictDetails } from '@/types';

// CMS-PUB-006: someone saved this item after the editor opened it. "Changed by {user} at {time}":
// Reload (take their version, lose the typed changes), Overwrite (Admin only, keeps the typed
// changes and replaces theirs) or keep editing. The form stays as typed until a choice is made.
const props = defineProps<{
    details: ConflictDetails | null;
    canOverwrite: boolean;
    busy: boolean;
    noun: string;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{ reload: []; overwrite: [] }>();

const who = computed(() => props.details?.by ?? 'another editor');
const when = computed(() =>
    props.details?.at ? formatDateTime(props.details.at) : null,
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>This {{ noun }} was changed</DialogTitle>
                <DialogDescription>
                    Changed by <strong>{{ who }}</strong>
                    <template v-if="when"> at {{ when }}</template
                    >, after you opened it. Nothing was saved. Your changes are
                    still in the form.
                </DialogDescription>
            </DialogHeader>

            <p class="text-sm text-muted-foreground">
                <strong class="text-foreground">Reload</strong> shows their
                version and drops what you typed.
                <template v-if="canOverwrite">
                    <strong class="text-foreground">Overwrite</strong> saves
                    your version over theirs.
                </template>
                <template v-else>
                    Only an Admin can overwrite someone else's changes. Copy
                    what you need first, then reload.
                </template>
            </p>

            <DialogFooter>
                <Button type="button" variant="ghost" @click="open = false"
                    >Keep editing</Button
                >
                <Button type="button" variant="outline" @click="emit('reload')"
                    >Reload</Button
                >
                <Button
                    v-if="canOverwrite"
                    type="button"
                    variant="destructive"
                    :disabled="busy"
                    @click="emit('overwrite')"
                >
                    <Spinner v-if="busy" />
                    Overwrite
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
