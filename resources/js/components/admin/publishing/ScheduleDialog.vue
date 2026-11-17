<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import DateTimePicker from '@/components/forms/DateTimePicker.vue';
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
import { schedule } from '@/routes/admin/publishing';

// CMS-PUB-003: pick the go-live time in Asia/Dhaka. The scheduler runs every minute, so the time is
// to the minute and at least one minute ahead. `changes` = the live item's pending changes go live,
// not the item itself.
const props = defineProps<{
    type: string;
    id: number;
    /** ISO time of the schedule being changed, or null. */
    current: string | null;
    changes: boolean;
    noun: string;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ publish_at: '' });

const ZONE = 'Asia/Dhaka';
const PARTS = new Intl.DateTimeFormat('sv-SE', {
    timeZone: ZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

/** `2026-12-01T09:00` as Dhaka wall time for a moment. */
function dhaka(date: Date): string {
    const part = Object.fromEntries(
        PARTS.formatToParts(date).map((item) => [item.type, item.value]),
    );

    return `${part.year}-${part.month}-${part.day}T${part.hour}:${part.minute}`;
}

const earliest = computed(() =>
    open.value ? dhaka(new Date(Date.now() + 2 * 60_000)) : '',
);

const latest = computed(() =>
    open.value ? dhaka(new Date(Date.now() + 2 * 365 * 86_400_000)) : '',
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    // Tomorrow 09:00 Dhaka time unless a schedule exists.
    const tomorrow = dhaka(new Date(Date.now() + 24 * 3_600_000)).slice(0, 10);

    form.publish_at = props.current
        ? dhaka(new Date(props.current))
        : `${tomorrow}T09:00`;
    form.defaults({ publish_at: form.publish_at });
});

function submit(): void {
    form.post(schedule.url({ type: props.type, id: props.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        changes
                            ? 'Schedule these changes'
                            : `Schedule this ${noun}`
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            changes
                                ? 'The changes replace the live version at this time.'
                                : `The ${noun} goes live at this time.`
                        }}
                        The publish checklist must already be complete.
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    id="field-publish_at"
                    label="Go live at"
                    required
                    helper="Dhaka time (UTC+6). The site checks every minute."
                    :error="form.errors.publish_at"
                >
                    <template #default="{ id, describedby, invalid }">
                        <DateTimePicker
                            :id="id"
                            v-model="form.publish_at"
                            :min="earliest"
                            :max="latest"
                            :describedby="describedby"
                            :invalid="invalid"
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
                        :disabled="form.processing || form.publish_at === ''"
                    >
                        <Spinner v-if="form.processing" />
                        Schedule
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
