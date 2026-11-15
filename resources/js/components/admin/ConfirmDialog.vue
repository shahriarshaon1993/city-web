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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { ConfirmRedirect } from '@/composables/useConfirm';

// CMP-ADM-CONFIRM: the title says what happens and to how many, the body the consequence, the
// button the verb. `subject` names the object, `typeToConfirm` asks for a word to be typed first
// (irreversible deletes), `redirect` adds the optional "send visitors to…" picker (DEL-02).
// Most screens use `useConfirm()` + ConfirmHost instead of mounting this directly.
const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel: string;
        subject?: string;
        destructive?: boolean;
        processing?: boolean;
        typeToConfirm?: string;
        redirect?: ConfirmRedirect;
    }>(),
    { destructive: false, processing: false },
);

const emit = defineEmits<{ confirm: [redirect: string | null] }>();
const open = defineModel<boolean>('open', { default: false });

const NONE = '__none__';
const typed = ref('');
const target = ref(NONE);
const ready = computed(
    () => !props.typeToConfirm || typed.value.trim() === props.typeToConfirm,
);

watch(open, (isOpen) => {
    if (isOpen) {
        typed.value = '';
        target.value = NONE;
    }
});

function confirm(): void {
    emit('confirm', target.value === NONE ? null : target.value);
}
</script>

<template>
    <AlertDialog v-model:open="open">
        <AlertDialogContent>
            <AlertDialogTitle>{{ title }}</AlertDialogTitle>
            <AlertDialogDescription>
                <strong
                    v-if="subject"
                    class="mb-1 block font-medium text-foreground"
                    >{{ subject }}</strong
                >
                {{ description }}
            </AlertDialogDescription>

            <div v-if="redirect" class="grid gap-2">
                <Label for="confirm-redirect" class="text-sm">{{
                    redirect.label
                }}</Label>
                <Select v-model="target">
                    <SelectTrigger id="confirm-redirect" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="NONE">No redirect</SelectItem>
                        <SelectItem
                            v-for="option in redirect.options"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
            </div>

            <div v-if="typeToConfirm" class="grid gap-2">
                <Label for="confirm-typed" class="text-sm">
                    Type <strong>{{ typeToConfirm }}</strong> to confirm
                </Label>
                <Input
                    id="confirm-typed"
                    v-model="typed"
                    autocomplete="off"
                    @keydown.enter.prevent="ready && !processing && confirm()"
                />
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AlertDialogCancel :disabled="processing"
                    >Cancel</AlertDialogCancel
                >
                <Button
                    :variant="destructive ? 'destructive' : 'primary'"
                    :disabled="!ready || processing"
                    @click="confirm"
                >
                    <Spinner v-if="processing" />
                    {{ confirmLabel }}
                </Button>
            </div>
        </AlertDialogContent>
    </AlertDialog>
</template>
