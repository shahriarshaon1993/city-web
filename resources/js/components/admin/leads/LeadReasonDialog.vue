<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import FormField from '@/components/forms/FormField.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { leadChanged } from '@/composables/useLeadActions';
import type { ReasonRequest } from '@/composables/useLeadActions';
import { status } from '@/routes/admin/leads';
import type { LeadReasonLists } from '@/types';

// FR-LEAD-006: Won and Lost need a reason — a choice from a short list plus an optional note
// (200 characters, VAL-03). Closing the dialog without saving tells the page, so a card that was
// dragged here can snap back.
const props = defineProps<{
    request: ReasonRequest | null;
    reasons: LeadReasonLists;
}>();

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ done: []; cancelled: [] }>();

const NOTE_MAX = 200;
const form = useForm({ status: '', reason: '' as string | null, note: '' });
const won = computed(() => props.request?.to === 'won');
const options = computed(() =>
    won.value ? props.reasons.won : props.reasons.lost,
);

let saved = false;

watch(open, (isOpen) => {
    if (isOpen) {
        saved = false;
        form.clearErrors();
        form.defaults({
            status: props.request?.to ?? '',
            reason: null,
            note: '',
        });
        form.reset();
    } else if (!saved) {
        emit('cancelled');
    }
});

function submit(): void {
    if (!props.request) {
        return;
    }

    form.patch(status.url(props.request.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            saved = true;
            open.value = false;
            emit('done');
        },
        onFinish: leadChanged,
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        won ? 'Mark this lead as won' : 'Mark this lead as lost'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ request?.name }} — tell the team
                        {{ won ? 'how it was won' : 'why it was lost' }}. The
                        answer stays on the lead's timeline.
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    id="lead-reason"
                    :label="won ? 'How was it won?' : 'Why was it lost?'"
                    required
                    :error="form.errors.reason"
                >
                    <template #default="{ id, describedby, invalid }">
                        <AdminSelect
                            :id="id"
                            v-model="form.reason"
                            :options="options"
                            placeholder="Choose a reason"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>

                <FormField
                    id="lead-reason-note"
                    label="Note"
                    helper="Optional. What would help the next person who reads this lead?"
                    :counter="{ length: form.note.length, max: NOTE_MAX }"
                    :error="form.errors.note"
                >
                    <template #default="{ id, describedby, invalid }">
                        <Textarea
                            :id="id"
                            v-model="form.note"
                            :maxlength="NOTE_MAX"
                            rows="3"
                            :aria-describedby="describedby"
                            :aria-invalid="invalid || undefined"
                        />
                    </template>
                </FormField>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :variant="won ? 'primary' : 'destructive'"
                        :disabled="form.processing || !form.reason"
                    >
                        <Spinner v-if="form.processing" />
                        {{ won ? 'Mark as won' : 'Mark as lost' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
