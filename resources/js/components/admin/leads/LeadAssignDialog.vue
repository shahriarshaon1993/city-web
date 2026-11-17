<script setup lang="ts">
import { ref, watch } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { LeadPerson } from '@/types';

// FR-LEAD-007: pick who gets the ticked leads (empty = take the owner away).
const props = defineProps<{ count: number; assignees: LeadPerson[] }>();

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ submit: [assigneeId: number | null] }>();

const choice = ref<number | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        choice.value = null;
    }
});

const options = () =>
    props.assignees.map((person) => ({ value: person.id, label: person.name }));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form
                class="grid gap-5"
                @submit.prevent="
                    emit('submit', choice);
                    open = false;
                "
            >
                <DialogHeader>
                    <DialogTitle
                        >Assign {{ count }}
                        {{ count === 1 ? 'lead' : 'leads' }}</DialogTitle
                    >
                    <DialogDescription>
                        The new owner sees these leads in their list. The change
                        goes on each lead's timeline.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="lead-assign-owner">Owner</Label>
                    <AdminSelect
                        id="lead-assign-owner"
                        v-model="choice"
                        :options="options()"
                        none-label="Unassigned"
                        placeholder="Unassigned"
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit">{{
                        choice === null ? 'Leave unassigned' : 'Assign'
                    }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
