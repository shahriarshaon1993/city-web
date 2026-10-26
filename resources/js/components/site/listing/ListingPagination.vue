<script setup lang="ts">
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';

// CMP-PAGI-01 / FR-SRCH-007: real `<a href="?page=n">` links (SEO-TEC-005),
// first, last, current ±1 and ellipses; below 640 px "Page 3 of 9" with
// Prev/Next only. With JS the same URLs load as a partial reload.
const listing = useListing();

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
    listing.goToPage(url);
}

const linkClass =
    'inline-flex size-11 items-center justify-center rounded-md text-body tabular transition-colors duration-(--dur-fast) hover:bg-muted';
const stepClass =
    'inline-flex h-11 items-center gap-2 rounded-md px-3 text-sm font-medium transition-colors duration-(--dur-fast) hover:bg-muted';
</script>

<template>
    <nav
        v-if="listing.meta.value.last_page > 1"
        aria-label="Pagination"
        class="flex items-center justify-between gap-4 border-t border-border pt-6"
    >
        <a
            v-if="listing.meta.value.pagination.prev"
            :href="listing.meta.value.pagination.prev"
            :class="stepClass"
            @click="go($event, listing.meta.value.pagination.prev)"
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
                v-for="(link, index) in listing.meta.value.pagination.pages"
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
                    v-else-if="link.page === listing.meta.value.page"
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
            Page {{ listing.meta.value.page }} of
            {{ listing.meta.value.last_page }}
        </p>

        <a
            v-if="listing.meta.value.pagination.next"
            :href="listing.meta.value.pagination.next"
            :class="stepClass"
            @click="go($event, listing.meta.value.pagination.next)"
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
