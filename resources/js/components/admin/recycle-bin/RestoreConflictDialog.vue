<script setup lang="ts">
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { RestoreConflict } from '@/types';

// DEL-05: a restore that needs a decision opens this dialog instead of failing silently —
// a slug that is now used by another item gets a new value (suggested `-restored`), a parent
// that is itself in the bin offers "Restore {parent} too".
const props = defineProps<{
    label: string;
    conflicts: RestoreConflict[];
    processing: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    submit: [resolution: Record<string, string | boolean>];
}>();
const open = defineModel<boolean>('open', { default: false });

const answers = ref<Record<string, string | boolean>>({});

watch(open, (isOpen) => {
    if (isOpen) {
        answers.value = Object.fromEntries(
            props.conflicts.map((conflict) => [
                conflict.field,
                conflict.kind === 'slug' ? (conflict.suggestion ?? '') : true,
            ]),
        );
    }
});

function submit(): void {
    emit('submit', answers.value);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Restore “{{ label }}”</DialogTitle>
                    <DialogDescription>
                        Something changed while this was in the Recycle bin.
                        Decide how to bring it back.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-for="conflict in conflicts"
                    :key="conflict.field"
                    class="grid gap-2"
                >
                    <p class="text-sm">{{ conflict.message }}</p>

                    <template v-if="conflict.kind === 'slug'">
                        <Label :for="`conflict-${conflict.field}`"
                            >New URL</Label
                        >
                        <Input
                            :id="`conflict-${conflict.field}`"
                            v-model="answers[conflict.field] as string"
                            maxlength="120"
                            autocomplete="off"
                        />
                    </template>
                    <div v-else class="flex items-center gap-2">
                        <Checkbox
                            :id="`conflict-${conflict.field}`"
                            :model-value="answers[conflict.field] === true"
                            @update:model-value="
                                (value) =>
                                    (answers[conflict.field] = value === true)
                            "
                        />
                        <Label :for="`conflict-${conflict.field}`"
                            >Restore it too</Label
                        >
                    </div>
                    <InputError :message="errors?.[conflict.field]" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Restore
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
