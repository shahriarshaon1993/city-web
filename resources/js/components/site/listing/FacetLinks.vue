<script setup lang="ts">
import { computed, useId } from 'vue';
import { useListing } from '@/composables/useProjectListing';
import { isCrawlable } from '@/lib/listing';
import { cn } from '@/lib/utils';
import type { FacetOption } from '@/types/listing';

// CMP-FILT-01 single-select facets (segment, status) as an always-rendered
// row, so the landing pages stay crawl paths in the server HTML: an option
// whose target is an indexable page is an `<a href>`, a multi-facet target
// a `<button>` (SEO-TEC-023). Options with 0 results are disabled, not
// hidden (FR-SRCH-004). Scrolls sideways on small screens.
const props = withDefaults(
    defineProps<{
        facet: 'segment' | 'status';
        label: string;
        allLabel: string;
        allCount: number;
        options: FacetOption[];
        /** Unique per instance (page row vs filter sheet). */
        idPrefix?: string;
        /** `wrap` (the sheet) wraps lines and shows the label above. */
        layout?: 'row' | 'wrap';
    }>(),
    { idPrefix: 'facets', layout: 'row' },
);

const listing = useListing();
const labelId = useId();

const selected = computed(() => listing.state[props.facet]);

const items = computed(() =>
    [
        { value: null, label: props.allLabel, count: props.allCount },
        ...props.options,
    ].map((option) => {
        const href = listing.urlFor({ [props.facet]: option.value });
        const active = selected.value === option.value;

        return {
            key: `${props.idPrefix}:${props.facet}:${option.value ?? 'all'}`,
            value: option.value,
            label: option.label,
            count: option.count,
            href,
            active,
            link: isCrawlable(href) && (active || option.count > 0),
            disabled: !active && option.count === 0,
        };
    }),
);

function select(event: MouseEvent, value: string | null): void {
    if (
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.button !== 0
    ) {
        return;
    }

    event.preventDefault();

    if (selected.value !== value) {
        listing.update({ [props.facet]: value });
    }
}

const itemClass =
    'relative inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-xs text-body whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:not-disabled:text-foreground disabled:cursor-not-allowed disabled:opacity-50';
</script>

<template>
    <div
        :class="
            cn(
                'flex min-w-0',
                layout === 'row' ? 'items-center gap-5' : 'flex-col gap-1',
            )
        "
    >
        <span
            :id="labelId"
            :class="
                cn(
                    'shrink-0 text-overline text-muted-foreground uppercase',
                    layout === 'wrap' && 'sr-only',
                )
            "
        >
            {{ label }}
        </span>
        <div
            role="group"
            :aria-labelledby="labelId"
            :class="
                cn(
                    'flex min-w-0 items-center',
                    layout === 'row'
                        ? '-my-1 snap-x [scrollbar-width:none] gap-5 overflow-x-auto py-1 [&::-webkit-scrollbar]:hidden'
                        : 'flex-wrap gap-x-5',
                )
            "
        >
            <template v-for="item in items" :key="item.key">
                <a
                    v-if="item.link"
                    :href="item.href"
                    :data-filter-key="item.key"
                    :aria-current="item.active ? 'page' : undefined"
                    :class="
                        cn(
                            itemClass,
                            'snap-start',
                            item.active && 'text-foreground after:scale-x-100',
                        )
                    "
                    @click="select($event, item.value)"
                >
                    {{ item.label }}
                    <span class="tabular text-sm text-muted-foreground">
                        {{ item.count }}
                    </span>
                </a>
                <button
                    v-else
                    type="button"
                    :data-filter-key="item.key"
                    :aria-pressed="item.active"
                    :disabled="item.disabled"
                    :class="
                        cn(
                            itemClass,
                            'snap-start',
                            item.active && 'text-foreground after:scale-x-100',
                        )
                    "
                    @click="select($event, item.value)"
                >
                    {{ item.label }}
                    <span class="tabular text-sm text-muted-foreground">
                        {{ item.count }}
                    </span>
                </button>
            </template>
        </div>
    </div>
</template>
