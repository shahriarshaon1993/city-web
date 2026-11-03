<script setup lang="ts">
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { cn } from '@/lib/utils';
import type { PageLinksData } from '@/types/news';

// CMP-PAGI-01 / SEO-TEC-005: real `<a href="?page=n">` links, first, last,
// current ±1 and ellipses; below 640 px "Page 3 of 9" with Prev/Next only.
// Plain clicks emit `navigate` so the parent can visit with Inertia; modified
// clicks keep the browser's default (new tab).
defineProps<{
    page: number;
    lastPage: number;
    pagination: PageLinksData;
}>();

const emit = defineEmits<{ navigate: [url: string] }>();

function go(event: MouseEvent, url: string): void {
    if (
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.button !== 0
    ) {
        return;
    }

    event.preventDefault();
    emit('navigate', url);
}

const linkClass =
    'inline-flex size-11 items-center justify-center rounded-md text-body tabular transition-colors duration-(--dur-fast) hover:bg-muted';
const stepClass =
    'inline-flex h-11 items-center gap-2 rounded-md px-3 text-sm font-medium transition-colors duration-(--dur-fast) hover:bg-muted';
</script>

<template>
    <nav
        v-if="lastPage > 1"
        aria-label="Pagination"
        class="flex items-center justify-between gap-4 border-t border-border pt-6"
    >
        <a
            v-if="pagination.prev"
            :href="pagination.prev"
            :class="stepClass"
            @click="go($event, pagination.prev)"
        >
            <ArrowLeft class="size-4" aria-hidden="true" />
            Previous
        </a>
        <span v-else :class="cn(stepClass, 'pointer-events-none opacity-40')">
            <ArrowLeft class="size-4" aria-hidden="true" />
            Previous
        </span>

        <ol class="hidden items-center gap-1 sm:flex">
            <li
                v-for="(link, index) in pagination.pages"
                :key="link?.page ?? `gap-${index}`"
            >
                <span
                    v-if="link === null"
                    class="inline-flex size-11 items-center justify-center text-muted-foreground"
                    aria-hidden="true"
                >
                    …
                </span>
                <span
                    v-else-if="link.page === page"
                    aria-current="page"
                    :class="
                        cn(
                            linkClass,
                            'bg-foreground text-background hover:bg-foreground',
                        )
                    "
                >
                    <span class="sr-only">Page</span>
                    {{ link.page }}
                </span>
                <a
                    v-else
                    :href="link.url"
                    :class="linkClass"
                    @click="go($event, link.url)"
                >
                    <span class="sr-only">Page</span>
                    {{ link.page }}
                </a>
            </li>
        </ol>
        <p class="tabular text-sm text-muted-foreground sm:hidden">
            Page {{ page }} of {{ lastPage }}
        </p>

        <a
            v-if="pagination.next"
            :href="pagination.next"
            :class="stepClass"
            @click="go($event, pagination.next)"
        >
            Next
            <ArrowRight class="size-4" aria-hidden="true" />
        </a>
        <span v-else :class="cn(stepClass, 'pointer-events-none opacity-40')">
            Next
            <ArrowRight class="size-4" aria-hidden="true" />
        </span>
    </nav>
</template>
