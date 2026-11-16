<script setup lang="ts">
import { computed } from 'vue';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

// Rich text, interim (DEC-83): plain paragraphs (a blank line starts a new one) or simple HTML —
// p, h2–h4, ul/ol/li, strong, em, a, blockquote. The server keeps only those tags. The Tiptap
// toolbar of CMP-ADM-RTE arrives with the news editor (16.3) and replaces this field everywhere.
const props = defineProps<{
    id: string;
    describedby?: string;
    invalid?: boolean;
    /** Words needed to publish or to be indexed; the counter turns green when reached. */
    minWords?: number;
    rows?: number;
}>();

const model = defineModel<string>({ default: '' });

const words = computed(() => {
    const text = model.value
        .replace(/<\/(p|li|h[2-4]|blockquote)>|<br\s*\/?>/gi, ' ')
        .replace(/<[^>]*>/g, ' ')
        .trim();

    return text === '' ? 0 : text.split(/\s+/u).length;
});

const reached = computed(
    () => props.minWords !== undefined && words.value >= props.minWords,
);
</script>

<template>
    <div class="grid gap-1.5">
        <Textarea
            :id="id"
            v-model="model"
            :rows="rows ?? 10"
            class="max-h-[32rem] font-mono text-[13px]/6"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            spellcheck="true"
        />
        <p
            :class="
                cn(
                    'text-xs tabular-nums',
                    minWords === undefined
                        ? 'text-muted-foreground'
                        : reached
                          ? 'text-success'
                          : 'text-warning',
                )
            "
            aria-live="off"
        >
            {{ words }} {{ words === 1 ? 'word' : 'words' }}
            <template v-if="minWords !== undefined">
                · {{ reached ? 'long enough' : `${minWords} needed` }}
            </template>
        </p>
    </div>
</template>
