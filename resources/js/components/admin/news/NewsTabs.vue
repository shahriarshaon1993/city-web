<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { index as postsIndex } from '@/routes/admin/news';
import { index as categoriesIndex } from '@/routes/admin/news/categories';
import { index as tagsIndex } from '@/routes/admin/news/tags';

// ADM-SCR-08: posts, categories and tags are tabs of one News screen (they share the `news.*` permissions).
defineProps<{ current: 'posts' | 'categories' | 'tags' }>();

const tab =
    'inline-flex h-11 items-center border-b-2 px-1 text-sm font-medium transition-colors';
const tabs = [
    { key: 'posts', label: 'Posts', href: postsIndex.url() },
    { key: 'categories', label: 'Categories', href: categoriesIndex.url() },
    { key: 'tags', label: 'Tags', href: tagsIndex.url() },
] as const;
</script>

<template>
    <nav aria-label="News" class="-mb-px flex gap-6">
        <Link
            v-for="item in tabs"
            :key="item.key"
            :href="item.href"
            :aria-current="current === item.key ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === item.key
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
