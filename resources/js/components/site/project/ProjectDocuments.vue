<script setup lang="ts">
import { Download, FileText, LockKeyhole } from '@lucide/vue';
import { buttonVariants } from '@/components/ui/button';
import { formatFileSize } from '@/lib/format';
import type { ProjectDocument } from '@/types/project';

// PG-04 brochure and documents (DM-PDOC): hairline rows — thin file icon,
// title, "Kind · PDF · size" overline, one action. Public files download
// directly; gated ones show a lock and open the brochure dialog (name +
// phone first, AC-PROJ-05) — without JS the link goes to the contact page.
defineProps<{
    documents: ProjectDocument[];
    requestUrl: string;
}>();

const emit = defineEmits<{
    request: [document: ProjectDocument];
}>();

function meta(document: ProjectDocument): string {
    return [
        document.kind_label,
        document.format,
        document.bytes === null ? null : formatFileSize(document.bytes),
    ]
        .filter(Boolean)
        .join(' · ');
}
</script>

<template>
    <ul class="divide-y divide-border border-y border-border">
        <li
            v-for="document in documents"
            :key="document.id"
            class="flex items-center gap-4 py-5 sm:gap-6"
        >
            <FileText
                class="size-6 shrink-0 text-primary"
                stroke-width="1.25"
                aria-hidden="true"
            />
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <p class="text-body-lg text-pretty">{{ document.title }}</p>
                <p
                    class="tabular flex items-center gap-1.5 text-overline text-muted-foreground uppercase"
                >
                    <LockKeyhole
                        v-if="document.is_gated"
                        class="size-3.5"
                        aria-hidden="true"
                    />
                    {{ meta(document) }}
                </p>
            </div>
            <a
                v-if="document.is_gated"
                :href="requestUrl"
                :class="buttonVariants({ variant: 'outline', size: 'md' })"
                aria-haspopup="dialog"
                :aria-label="`${document.title}: request the download with your name and phone`"
                @click.prevent="emit('request', document)"
            >
                <LockKeyhole aria-hidden="true" />
                <span class="max-sm:sr-only">Request</span>
            </a>
            <a
                v-else-if="document.url"
                :href="document.url"
                download
                :class="buttonVariants({ variant: 'outline', size: 'md' })"
                :aria-label="`Download ${document.title} (${meta(document)})`"
            >
                <Download aria-hidden="true" />
                <span class="max-sm:sr-only">Download</span>
            </a>
        </li>
    </ul>
</template>
