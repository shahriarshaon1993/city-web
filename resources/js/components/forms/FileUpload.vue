<script setup lang="ts">
import { FileText, Paperclip, X } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';

// CMP-FORM-01 file upload: a dropzone with a keyboard-reachable button and
// the list of chosen files. Types, size and count are stated up front and
// wrong files are refused before anything is sent (VAL-03); `rejected`
// carries the reason for the field's error line. The server re-checks by
// content (FR-LEAD-011, VAL-01).
const props = defineProps<{
    id: string;
    /** MIME types accepted. */
    accept: string[];
    max: number;
    maxMb: number;
    /** e.g. "PDF, JPG or PNG". */
    typesLabel: string;
    invalid?: boolean;
    describedby?: string;
}>();

const emit = defineEmits<{
    rejected: [message: string];
    change: [];
}>();

const files = defineModel<File[]>({ default: () => [] });
const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

const EXTENSIONS: Record<string, string> = {
    'application/pdf': '.pdf',
    'image/jpeg': '.jpg,.jpeg',
    'image/png': '.png',
};

const acceptAttribute = () =>
    props.accept.map((mime) => EXTENSIONS[mime] ?? mime).join(',');

function add(incoming: FileList | File[]): void {
    let next = [...files.value];
    let problem: string | null = null;

    for (const file of Array.from(incoming)) {
        if (!props.accept.includes(file.type)) {
            problem ??= `Attach ${props.typesLabel} files only.`;
        } else if (file.size > props.maxMb * 1024 * 1024) {
            problem ??= `Each file must be ${props.maxMb} MB or smaller.`;
        } else if (next.length >= props.max) {
            problem ??= `You can attach up to ${props.max} files.`;
        } else if (
            !next.some(
                (item) => item.name === file.name && item.size === file.size,
            )
        ) {
            next = [...next, file];
        }
    }

    files.value = next;

    if (problem !== null) {
        emit('rejected', problem);
    } else {
        emit('change');
    }
}

function onPick(event: Event): void {
    const target = event.target as HTMLInputElement;

    if (target.files) {
        add(target.files);
    }

    target.value = '';
}

function onDrop(event: DragEvent): void {
    dragging.value = false;

    if (event.dataTransfer?.files) {
        add(event.dataTransfer.files);
    }
}

function remove(index: number): void {
    files.value = files.value.filter((_, position) => position !== index);
    emit('change');
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            :class="
                cn(
                    'flex flex-col items-start gap-3 rounded-md border border-dashed border-foreground/30 bg-background p-4 transition-colors duration-(--dur-fast) sm:flex-row sm:items-center',
                    dragging && 'border-primary bg-primary/5',
                    invalid && 'border-destructive',
                )
            "
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="onDrop"
        >
            <Button
                :id="id"
                type="button"
                variant="outline"
                :aria-describedby="describedby"
                :aria-invalid="invalid || undefined"
                @click="input?.click()"
            >
                <Paperclip aria-hidden="true" />
                Choose files
            </Button>
            <p class="text-sm text-muted-foreground">
                or drop them here. Up to {{ max }} files, {{ typesLabel }},
                {{ maxMb }} MB each.
            </p>
            <input
                ref="input"
                type="file"
                multiple
                class="sr-only"
                tabindex="-1"
                aria-hidden="true"
                :accept="acceptAttribute()"
                @change="onPick"
            />
        </div>

        <ul
            v-if="files.length"
            class="divide-y divide-border border-y border-border"
            aria-label="Attached files"
        >
            <li
                v-for="(file, index) in files"
                :key="`${file.name}-${file.size}`"
                class="flex min-h-12 items-center gap-3 text-sm"
            >
                <FileText
                    class="size-5 shrink-0 text-primary"
                    stroke-width="1.5"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1 truncate">{{ file.name }}</span>
                <span class="tabular shrink-0 text-muted-foreground">
                    {{ formatFileSize(file.size) }}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="-mr-2.5"
                    :aria-label="`Remove ${file.name}`"
                    @click="remove(index)"
                >
                    <X aria-hidden="true" />
                </Button>
            </li>
        </ul>
    </div>
</template>
