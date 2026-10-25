<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { ImagePreset } from '@/lib/images';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { NewsCard } from '@/types/site';

// CMP-NEWS-01: cover · "Category · date" overline (DEC-48: a quiet overline
// instead of a chip) · title · excerpt. Read time only where asked
// (FR-NEWS-004: never on home). Variants: `default` (16:10, `text-h4`, 3-line
// title), `lead` (latest_news lead story, `text-h3`), `compact` (thumbnail
// row, no excerpt), `featured` (horizontal, 60 % cover from 768 px). One
// link: the title, stretched over the card.
const props = withDefaults(
    defineProps<{
        post: NewsCard;
        variant?: 'default' | 'lead' | 'compact' | 'featured';
        headingAs?: 'h2' | 'h3';
        showReadTime?: boolean;
    }>(),
    { variant: 'default', headingAs: 'h3', showReadTime: false },
);

const presets: Record<NonNullable<typeof props.variant>, ImagePreset> = {
    default: 'news-card',
    lead: 'news-lead',
    compact: 'news-thumb',
    featured: 'news-card',
};

const layout = computed(
    () =>
        ({
            default: 'flex-col gap-5',
            lead: 'flex-col gap-6',
            compact: 'flex-row items-start gap-5',
            featured:
                'flex-col gap-5 md:grid md:grid-cols-5 md:items-center md:gap-8',
        })[props.variant],
);

const title = computed(
    () =>
        ({
            default: 'line-clamp-3 text-h4',
            lead: 'text-h3',
            compact: 'line-clamp-3 text-lg',
            featured: 'text-h3',
        })[props.variant],
);
</script>

<template>
    <article
        :class="
            cn(
                'media-card group/card relative flex h-full rounded-xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid',
                layout,
            )
        "
    >
        <ResponsiveImage
            :image="post.cover"
            :preset="presets[variant]"
            :class="
                cn(
                    variant === 'compact'
                        ? 'w-28 shrink-0 rounded-lg sm:w-44'
                        : 'rounded-xl',
                    variant === 'featured' && 'md:col-span-3',
                )
            "
            img-class="media-zoom"
        />
        <div
            :class="
                cn(
                    'flex min-w-0 flex-1 flex-col gap-3',
                    variant === 'featured' && 'md:col-span-2',
                    variant === 'lead' && 'gap-4',
                )
            "
        >
            <p class="text-overline text-muted-foreground uppercase">
                <span class="text-primary">{{ post.category.name }}</span>
                <span aria-hidden="true"> · </span>
                <time :datetime="post.published_at">{{
                    formatDate(post.published_at)
                }}</time>
                <template v-if="showReadTime">
                    <span aria-hidden="true"> · </span>
                    {{ post.read_minutes }} min read
                </template>
            </p>
            <component :is="headingAs" :class="cn('text-balance', title)">
                <Link
                    :href="post.url"
                    class="transition-colors duration-(--dur-fast) outline-none group-hover/card:text-primary after:absolute after:inset-0 after:rounded-xl after:content-['']"
                >
                    {{ post.title }}
                </Link>
            </component>
            <p
                v-if="variant !== 'compact'"
                :class="
                    cn(
                        'text-muted-foreground',
                        variant === 'lead'
                            ? 'line-clamp-3 max-w-prose text-body-lg'
                            : 'line-clamp-2',
                    )
                "
            >
                {{ post.excerpt }}
            </p>
        </div>
    </article>
</template>
