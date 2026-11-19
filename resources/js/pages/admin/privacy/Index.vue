<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import { HttpError, sendJson } from '@/lib/http';
import { notify } from '@/lib/toast';
import { erase, exportMethod, search } from '@/routes/admin/privacy';
import type { PersonalDataResults } from '@/types';

// NFR-PRIV-03: find everything held for a phone and/or an email, download it, or erase it for good.
// The person's details travel in request bodies, never in the address bar.
const props = defineProps<{
    searched: { phone: string | null; email: string | null } | null;
    results: PersonalDataResults | null;
    confirmWord: string;
}>();

const confirm = useConfirm();
const form = useForm({ phone: '', email: '' });
const exporting = ref(false);

const total = computed(() =>
    props.results === null
        ? 0
        : props.results.leads.length +
          props.results.applications.length +
          props.results.subscriptions.length,
);

const who = computed(() =>
    [props.searched?.phone, props.searched?.email].filter(Boolean).join(' · '),
);

function submit(): void {
    form.post(search.url(), { preserveState: true, preserveScroll: true });
}

async function download(): Promise<void> {
    if (!props.searched) {
        return;
    }

    exporting.value = true;

    try {
        const data = await sendJson<unknown>(
            'POST',
            exportMethod.url(),
            props.searched,
        );
        const blob = new Blob([JSON.stringify(data, null, 2)], {
            type: 'application/json',
        });
        const link = document.createElement('a');

        link.href = URL.createObjectURL(blob);
        link.download = `personal-data-${new Date().toISOString().slice(0, 10)}.json`;
        link.click();
        URL.revokeObjectURL(link.href);
        notify.success('Their data was downloaded');
    } catch (error) {
        notify.error(
            error instanceof HttpError && error.status === 429
                ? 'Too many requests. Wait a minute and try again.'
                : 'The download failed. Try again.',
        );
    } finally {
        exporting.value = false;
    }
}

async function eraseAll(): Promise<void> {
    if (!props.searched) {
        return;
    }

    const answer = await confirm({
        title: 'Erase everything held for this person?',
        subject: who.value,
        description:
            'Their leads (with notes and landowner documents), job applications (with CVs) and newsletter subscriptions are deleted for good, including any in the Recycle bin. This cannot be undone and nothing goes to the Recycle bin. The activity log records only that it happened, with counts.',
        confirmLabel: 'Erase everything',
        destructive: true,
        typeToConfirm: props.confirmWord,
    });

    if (answer) {
        router.delete(erase.url(), {
            data: { ...props.searched, confirm: props.confirmWord },
        });
    }
}

const statusTone = (inBin: boolean): string => (inBin ? 'warning' : 'neutral');
</script>

<template>
    <Head title="Personal data" />

    <AdminPageHeader
        title="Personal data"
        description="When someone asks what we hold about them, or asks to be forgotten: find everything for their phone and email, download it, or erase it."
    />

    <div class="flex max-w-[1100px] flex-col gap-6">
        <AdminSection
            title="Find a person"
            description="Enter the phone, the email, or both. Both are searched together across leads, job applications and newsletter subscriptions, including items in the Recycle bin."
        >
            <form class="grid gap-5" novalidate @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="pd-phone">Phone</Label>
                        <Input
                            id="pd-phone"
                            v-model="form.phone"
                            type="tel"
                            inputmode="tel"
                            autocomplete="off"
                            placeholder="01712 345678 or +44 7911 123456"
                            :aria-invalid="!!form.errors.phone || undefined"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pd-email">Email</Label>
                        <Input
                            id="pd-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                            placeholder="name@example.com"
                            :aria-invalid="!!form.errors.email || undefined"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>
                <div>
                    <Button type="submit" :loading="form.processing">
                        <template #icon><Search aria-hidden="true" /></template>
                        Find
                    </Button>
                </div>
            </form>
        </AdminSection>

        <AdminSection
            v-if="results !== null"
            :title="
                total === 0
                    ? 'Nothing found'
                    : `Found ${total} ${total === 1 ? 'record' : 'records'}`
            "
            :description="who"
        >
            <p v-if="total === 0" class="text-sm text-muted-foreground">
                Nothing is held for this phone or email. Check how it was typed;
                a number is matched in its full international form, an email in
                lower case.
            </p>

            <div v-else class="grid gap-8">
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :loading="exporting"
                        @click="download"
                    >
                        <template #icon
                            ><Download aria-hidden="true"
                        /></template>
                        Download their data (JSON)
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="eraseAll"
                    >
                        <Trash2 aria-hidden="true" />
                        Erase everything
                    </Button>
                </div>

                <section v-if="results.leads.length > 0" class="grid gap-3">
                    <h3 class="text-sm font-medium">
                        Leads ({{ results.leads.length }})
                    </h3>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="lead in results.leads"
                            :key="lead.id"
                            class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 p-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="font-medium">{{ lead.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ lead.phone }}
                                    <template v-if="lead.email">
                                        · {{ lead.email }}</template
                                    >
                                    · {{ lead.type }}
                                    <template v-if="lead.project">
                                        · {{ lead.project }}</template
                                    >
                                    · {{ lead.status }}
                                    <template v-if="lead.created_at">
                                        ·
                                        {{
                                            formatDateTime(lead.created_at)
                                        }}</template
                                    >
                                </p>
                            </div>
                            <StatusBadge
                                v-if="lead.in_bin"
                                label="In the Recycle bin"
                                :tone="statusTone(true)"
                            />
                        </li>
                    </ul>
                </section>

                <section
                    v-if="results.applications.length > 0"
                    class="grid gap-3"
                >
                    <h3 class="text-sm font-medium">
                        Job applications ({{ results.applications.length }})
                    </h3>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="application in results.applications"
                            :key="application.id"
                            class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 p-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="font-medium">
                                    {{ application.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ application.phone }} ·
                                    {{ application.email }} ·
                                    {{ application.job }} ·
                                    {{ application.status }}
                                    <template v-if="application.created_at">
                                        ·
                                        {{
                                            formatDateTime(
                                                application.created_at,
                                            )
                                        }}</template
                                    >
                                </p>
                            </div>
                            <StatusBadge
                                v-if="application.in_bin"
                                label="In the Recycle bin"
                                :tone="statusTone(true)"
                            />
                        </li>
                    </ul>
                    <p class="text-xs text-muted-foreground">
                        The download names each CV file but does not contain it;
                        open the application to give them their CV.
                    </p>
                </section>

                <section
                    v-if="results.subscriptions.length > 0"
                    class="grid gap-3"
                >
                    <h3 class="text-sm font-medium">
                        Newsletter ({{ results.subscriptions.length }})
                    </h3>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="subscription in results.subscriptions"
                            :key="subscription.id"
                            class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 p-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="font-medium">
                                    {{ subscription.email }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ subscription.status }}
                                    <template v-if="subscription.created_at">
                                        ·
                                        {{
                                            formatDateTime(
                                                subscription.created_at,
                                            )
                                        }}</template
                                    >
                                </p>
                            </div>
                            <StatusBadge
                                v-if="subscription.in_bin"
                                label="In the Recycle bin"
                                :tone="statusTone(true)"
                            />
                        </li>
                    </ul>
                </section>
            </div>
        </AdminSection>
    </div>
</template>
