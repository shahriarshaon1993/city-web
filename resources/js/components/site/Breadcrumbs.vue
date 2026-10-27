<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { computed } from 'vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { Breadcrumb as Crumb } from '@/types/site';

// CMP-BREAD-01 (SEO-ONP-005): Home › Section › Page from the page's
// `breadcrumbs` prop. The last item is plain text with aria-current. Below
// 640 px the middle items collapse into a "…" menu; they stay in the HTML
// (hidden by CSS) so the trail is crawlable at every width. `media` is
// white text for a photo hero (area pages, 5.1).
const props = withDefaults(
    defineProps<{
        items: Crumb[];
        tone?: 'default' | 'media';
    }>(),
    { tone: 'default' },
);

const first = computed(() => props.items[0]);
const middle = computed(() => props.items.slice(1, -1));
const current = computed(() => props.items.at(-1));

const media = computed(() => props.tone === 'media');
const linkClass = computed(() =>
    cn(
        'inline-flex min-h-6 items-center rounded-sm transition-colors duration-(--dur-fast)',
        media.value ? 'hover:text-white' : 'hover:text-foreground',
    ),
);
</script>

<template>
    <Breadcrumb v-if="items.length > 1" aria-label="Breadcrumb">
        <BreadcrumbList
            :class="cn('gap-1.5 text-sm sm:gap-2', media && 'text-white/80')"
        >
            <BreadcrumbItem v-if="first">
                <Link :href="first.url ?? '/'" :class="linkClass">
                    {{ first.label }}
                </Link>
            </BreadcrumbItem>

            <template v-if="middle.length > 0">
                <BreadcrumbSeparator />
                <BreadcrumbItem class="sm:hidden">
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            :class="
                                cn(
                                    '-my-2.5 inline-flex size-11 items-center justify-center rounded-md',
                                    media
                                        ? 'hover:text-white'
                                        : 'hover:text-foreground',
                                )
                            "
                            aria-label="Show more breadcrumbs"
                        >
                            <MoreHorizontal class="size-4" aria-hidden="true" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="start"
                            class="z-(--z-dropdown)"
                        >
                            <DropdownMenuItem
                                v-for="crumb in middle"
                                :key="crumb.label"
                                as-child
                            >
                                <Link :href="crumb.url ?? '/'" class="min-h-11">
                                    {{ crumb.label }}
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </BreadcrumbItem>

                <template v-for="crumb in middle" :key="crumb.label">
                    <BreadcrumbItem class="max-sm:hidden">
                        <Link :href="crumb.url ?? '/'" :class="linkClass">
                            {{ crumb.label }}
                        </Link>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator class="max-sm:hidden" />
                </template>
                <BreadcrumbSeparator class="sm:hidden" />
            </template>
            <BreadcrumbSeparator v-else />

            <BreadcrumbItem v-if="current">
                <span
                    aria-current="page"
                    :class="
                        cn(
                            'font-medium',
                            media ? 'text-white' : 'text-foreground',
                        )
                    "
                >
                    {{ current.label }}
                </span>
            </BreadcrumbItem>
        </BreadcrumbList>
    </Breadcrumb>
</template>
