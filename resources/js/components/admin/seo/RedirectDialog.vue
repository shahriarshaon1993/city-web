<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CornerUpRight, Ban, Clock } from '@lucide/vue';
import { computed, watch } from 'vue';
import AdminRadioCards from '@/components/admin/AdminRadioCards.vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { store, update } from '@/routes/admin/redirects';
import type { RedirectDraft } from '@/types';

// CRUD-01: redirects are a dialog entity — one dialog creates (no id) or edits. Loops and chains
// are decided on the server (SaveRedirect); a refusal lands on the field it is about.
const props = defineProps<{ draft: RedirectDraft | null }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    source: '',
    target: '',
    code: '301',
    is_active: true,
});

const editing = computed(() => props.draft?.id !== undefined);
const gone = computed(() => form.code === '410');
const isPrefix = computed(() => form.source.trim().endsWith('/*'));

const types = [
    {
        value: '301',
        label: 'Permanent (301)',
        description: 'The page moved for good. Search engines follow it.',
        icon: CornerUpRight,
    },
    {
        value: '302',
        label: 'Temporary (302)',
        description: 'Only for now, e.g. a campaign. The old address stays.',
        icon: Clock,
    },
    {
        value: '410',
        label: 'Gone (410)',
        description: 'The page was removed and nothing replaces it.',
        icon: Ban,
    },
];

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({
            source: props.draft?.source ?? '',
            target: props.draft?.target ?? '',
            code: String(props.draft?.code ?? 301),
            is_active: props.draft?.is_active ?? true,
        });
        form.reset();
    }
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        code: Number(data.code),
        target: data.code === '410' ? null : data.target,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (props.draft?.id !== undefined) {
        form.patch(update.url(props.draft.id), options);
    } else {
        form.post(store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit redirect' : 'New redirect'
                    }}</DialogTitle>
                    <DialogDescription>
                        Visitors and search engines who ask for the old address
                        are sent to the new one. Rules run before the site looks
                        for a page.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="redirect-source">
                        Old address
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="redirect-source"
                        v-model="form.source"
                        required
                        maxlength="500"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder="/old-page"
                        class="font-mono text-sm"
                        aria-describedby="redirect-source-help"
                        :aria-invalid="!!form.errors.source || undefined"
                    />
                    <FieldHelp id="redirect-source">
                        The path visitors used, like /old-page. End it with /*
                        to include everything below it.
                        <strong v-if="isPrefix"
                            >This rule covers the address and all its
                            sub-pages.</strong
                        >
                    </FieldHelp>
                    <CharCounter :value="form.source" :max="500" />
                    <InputError :message="form.errors.source" />
                </div>

                <AdminRadioCards
                    v-model="form.code"
                    legend="Type"
                    name="redirect-code"
                    :options="types"
                />
                <InputError :message="form.errors.code" />

                <div v-if="!gone" class="grid gap-2">
                    <Label for="redirect-target">
                        Send visitors to
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="redirect-target"
                        v-model="form.target"
                        required
                        maxlength="500"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder="/projects"
                        class="font-mono text-sm"
                        aria-describedby="redirect-target-help"
                        :aria-invalid="!!form.errors.target || undefined"
                    />
                    <FieldHelp id="redirect-target">
                        A path like /projects, or a full address starting with
                        https://.
                        <template v-if="isPrefix">
                            End it with /* to keep the rest of the path (/old/a
                            → /new/a).
                        </template>
                        The visitor's query string (utm_… tags) is kept.
                    </FieldHelp>
                    <CharCounter :value="form.target" :max="500" />
                    <InputError :message="form.errors.target" />
                </div>

                <div class="flex items-start justify-between gap-4">
                    <div class="grid gap-1">
                        <Label for="redirect-active">Active</Label>
                        <FieldHelp id="redirect-active"
                            >Switch off to keep the rule without using
                            it.</FieldHelp
                        >
                    </div>
                    <Switch
                        id="redirect-active"
                        v-model="form.is_active"
                        aria-describedby="redirect-active-help"
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            form.source.trim() === '' ||
                            (!gone && form.target.trim() === '')
                        "
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save changes' : 'Create redirect' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
