<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

// CMP-ADM-CONFIRM: the title says what happens and to how many, the body the consequence, the
// button the verb. `typeToConfirm` asks for a word to be typed first (irreversible bulk deletes).
// 10.4 adds the `useConfirm()` helper on top of this component.
const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel: string;
        destructive?: boolean;
        processing?: boolean;
        typeToConfirm?: string;
    }>(),
    { destructive: false, processing: false },
);

const emit = defineEmits<{ confirm: [] }>();
const open = defineModel<boolean>('open', { default: false });

const typed = ref('');
const ready = computed(
    () => !props.typeToConfirm || typed.value.trim() === props.typeToConfirm,
);

watch(open, (isOpen) => {
    if (isOpen) {
        typed.value = '';
    }
});
</script>

<template>
    <AlertDialog v-model:open="open">
        <AlertDialogContent>
            <AlertDialogTitle>{{ title }}</AlertDialogTitle>
            <AlertDialogDescription>{{ description }}</AlertDialogDescription>

            <div v-if="typeToConfirm" class="grid gap-2">
                <Label for="confirm-typed" class="text-sm">
                    Type <strong>{{ typeToConfirm }}</strong> to confirm
                </Label>
                <Input id="confirm-typed" v-model="typed" autocomplete="off" />
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AlertDialogCancel :disabled="processing"
                    >Cancel</AlertDialogCancel
                >
                <Button
                    :variant="destructive ? 'destructive' : 'primary'"
                    :disabled="!ready || processing"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    {{ confirmLabel }}
                </Button>
            </div>
        </AlertDialogContent>
    </AlertDialog>
</template>
