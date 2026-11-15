<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { Check, CircleX, LoaderCircle } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { check } from '@/routes/admin/slugs';

// VAL-04: the URL field. It fills itself from `from` (usually the name) until it is edited by
// hand, then shows ✓ / ✕ with the reason 400 ms after typing stops, and warns that old links
// redirect when the slug of a published item changes (SEO-TEC-003). The server answers
// (App\Support\Slugs\SlugAvailability), including "this URL belongs to an item in the Recycle bin".
const props = withDefaults(
    defineProps<{
        id: string;
        /** The text the slug follows while untouched, e.g. the name. */
        from?: string;
        /** SlugAvailability source key: `site` or an entity registered there. */
        source?: string;
        /** The record being edited, so its own slug does not count as taken. */
        ignoreId?: number | null;
        /** Changing the slug of a published item redirects the old URL. */
        published?: boolean;
        label?: string;
        prefix?: string;
        error?: string;
        required?: boolean;
    }>(),
    { source: 'site', label: 'URL', prefix: '/', ignoreId: null, from: '' },
);

const model = defineModel<string>({ default: '' });

type State = 'idle' | 'checking' | 'ok' | 'taken';
type Answer = {
    available: boolean;
    reason: string | null;
    bin: { label: string; url: string } | null;
    suggestion: string | null;
};

const original = model.value;
const touched = ref(model.value !== '');
const state = ref<State>('idle');
const answer = ref<Answer | null>(null);
let controller: AbortController | null = null;

function slugify(text: string): string {
    return text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 120);
}

watch(
    () => props.from,
    (text) => {
        if (!touched.value) {
            model.value = slugify(text);
        }
    },
);

function onInput(value: string | number): void {
    touched.value = true;
    model.value = String(value)
        .toLowerCase()
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-{2,}/g, '-');
}

function onBlur(): void {
    model.value = model.value.replace(/^-+|-+$/g, '');
}

async function verify(slug: string): Promise<void> {
    controller?.abort();

    if (slug === '' || slug === original) {
        state.value = 'idle';
        answer.value = null;

        return;
    }

    controller = new AbortController();
    state.value = 'checking';

    try {
        const response = await fetch(
            check.url({
                query: {
                    source: props.source,
                    slug,
                    ...(props.ignoreId ? { ignore: props.ignoreId } : {}),
                },
            }),
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            },
        );

        if (!response.ok) {
            state.value = 'idle';

            return;
        }

        answer.value = (await response.json()) as Answer;
        state.value = answer.value.available ? 'ok' : 'taken';
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            state.value = 'idle';
        }
    }
}

watchDebounced(model, (slug) => void verify(slug), { debounce: 400 });

const changed = computed(
    () => props.published && original !== '' && model.value !== original,
);
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-destructive" aria-hidden="true"
                >*</span
            >
        </Label>

        <div class="relative">
            <span
                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                aria-hidden="true"
                >{{ prefix }}</span
            >
            <Input
                :id="id"
                :model-value="model"
                class="pr-10"
                :style="{ paddingLeft: `${0.75 + prefix.length * 0.55}rem` }"
                maxlength="120"
                autocomplete="off"
                spellcheck="false"
                :aria-invalid="state === 'taken' || !!error || undefined"
                :aria-describedby="`${id}-help ${id}-status`"
                @update:model-value="onInput"
                @blur="onBlur"
            />
            <span
                class="absolute inset-y-0 right-3 flex items-center"
                aria-hidden="true"
            >
                <LoaderCircle
                    v-if="state === 'checking'"
                    class="size-4 animate-spin text-muted-foreground"
                />
                <Check v-else-if="state === 'ok'" class="size-4 text-success" />
                <CircleX
                    v-else-if="state === 'taken'"
                    class="size-4 text-destructive"
                />
            </span>
        </div>

        <p :id="`${id}-status`" class="min-h-4 text-xs" aria-live="polite">
            <template v-if="state === 'ok'">
                <span class="text-success">This URL is available.</span>
            </template>
            <template v-else-if="state === 'taken' && answer">
                <span class="text-destructive">
                    {{ answer.reason }}
                    <a
                        v-if="answer.bin"
                        :href="answer.bin.url"
                        class="underline underline-offset-2"
                        >Open the Recycle bin</a
                    >
                </span>
                <button
                    v-if="answer.suggestion"
                    type="button"
                    class="ml-2 underline underline-offset-2"
                    @click="onInput(answer.suggestion)"
                >
                    Use “{{ answer.suggestion }}”
                </button>
            </template>
        </p>

        <FieldHelp :id="id">
            Lowercase letters, numbers and hyphens. It fills itself from the
            name until you change it.
        </FieldHelp>
        <p v-if="changed" class="text-xs text-warning" role="status">
            Old links will redirect to the new URL automatically.
        </p>
        <InputError :message="error" />
    </div>
</template>
