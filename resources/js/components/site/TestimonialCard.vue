<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { getInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import type { Testimonial } from '@/types/site';

// CMP-TEST-01: decorative quote mark, quote as text (never an image), person
// (48 px photo or initials, DEC-47), designation and project link, in
// <figure><blockquote><figcaption>. `feature` (carousel, DEC-48) is one large
// light quote with the person beside it from 1024 px; `card` is the grid
// tile. `clamp` cuts long quotes with "Read more" — only after mount and only
// when they overflow, so SSR and no-JS output always show the full text.
const props = withDefaults(
    defineProps<{
        testimonial: Testimonial;
        variant?: 'card' | 'feature';
        clamp?: boolean;
    }>(),
    { variant: 'card', clamp: false },
);

const feature = computed(() => props.variant === 'feature');
const clampClass = computed(() =>
    feature.value ? 'line-clamp-6' : 'line-clamp-4',
);

const quote = useTemplateRef<HTMLElement>('quote');
const overflowing = ref(false);
const expanded = ref(false);

onMounted(() => {
    if (!props.clamp || !quote.value) {
        return;
    }

    // Measure the clamped height against the full one.
    quote.value.classList.add(clampClass.value);
    overflowing.value = quote.value.scrollHeight > quote.value.clientHeight + 1;
    quote.value.classList.remove(clampClass.value);
});
</script>

<template>
    <figure
        :class="
            cn(
                'flex h-full flex-col gap-6',
                feature
                    ? 'lg:grid lg:grid-cols-12 lg:items-end lg:gap-8'
                    : 'border-t border-border pt-8',
            )
        "
    >
        <div :class="cn('flex flex-col gap-6', feature && 'lg:col-span-8')">
            <svg
                :class="
                    cn(
                        feature ? 'size-10 lg:size-12' : 'size-12',
                        'text-brand-accent',
                    )
                "
                viewBox="0 0 48 48"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    d="M20 12c-6.6 2.2-11 8.1-11 15.4V36h11V25h-6c.3-4.3 2.7-7.6 6.9-9.4zm19 0c-6.6 2.2-11 8.1-11 15.4V36h11V25h-6c.3-4.3 2.7-7.6 6.9-9.4z"
                />
            </svg>

            <blockquote class="flex flex-col items-start gap-2">
                <p
                    :id="`testimonial-${testimonial.id}`"
                    ref="quote"
                    :class="
                        cn(
                            'text-pretty',
                            feature
                                ? 'max-w-[40ch] font-display text-h3 font-light'
                                : 'text-lead',
                            overflowing && !expanded && clampClass,
                        )
                    "
                >
                    {{ testimonial.quote }}
                </p>
                <button
                    v-if="overflowing"
                    type="button"
                    class="-my-2 inline-flex min-h-11 items-center text-sm font-medium text-primary underline-offset-4 hover:underline"
                    :aria-expanded="expanded"
                    :aria-controls="`testimonial-${testimonial.id}`"
                    @click="expanded = !expanded"
                >
                    {{ expanded ? 'Show less' : 'Read more' }}
                </button>
            </blockquote>
        </div>

        <figcaption
            :class="
                cn(
                    'mt-auto flex items-center gap-4',
                    feature &&
                        'border-t border-border pt-6 lg:col-span-4 lg:mt-0 lg:border-t-0 lg:border-l lg:pt-0 lg:pb-2 lg:pl-8',
                )
            "
        >
            <ResponsiveImage
                v-if="testimonial.photo"
                :image="testimonial.photo"
                preset="avatar"
                class="size-12 shrink-0 rounded-full"
            />
            <span
                v-else
                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-medium text-secondary-foreground"
                aria-hidden="true"
            >
                {{ getInitials(testimonial.person_name) }}
            </span>
            <span class="flex min-w-0 flex-col">
                <span class="font-medium">{{ testimonial.person_name }}</span>
                <span
                    v-if="testimonial.designation || testimonial.project"
                    class="text-sm text-muted-foreground"
                >
                    {{ testimonial.designation
                    }}<template
                        v-if="testimonial.designation && testimonial.project"
                        >,
                    </template>
                    <Link
                        v-if="testimonial.project"
                        :href="testimonial.project.url"
                        class="inline-flex min-h-6 items-center underline decoration-foreground/30 underline-offset-4 hover:text-foreground hover:decoration-foreground"
                        >{{ testimonial.project.name }}</Link
                    >
                </span>
            </span>
        </figcaption>
    </figure>
</template>
