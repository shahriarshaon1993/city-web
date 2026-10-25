<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { ref, useId } from 'vue';
import { cn } from '@/lib/utils';
import type { MenuItem } from '@/types/site';

// One CMP-FOOT-01 menu column: a disclosure below md, a plain list above.
// The list is always in the HTML (crawlable); without JavaScript the
// `footer-collapsible` noscript rule in app.blade.php keeps it visible.
defineProps<{
    heading: string;
    items: MenuItem[];
    isCurrent: (url: string) => boolean;
}>();

const open = ref(false);
const listId = useId();
</script>

<template>
    <div class="max-md:border-b max-md:border-border">
        <h2 class="font-display font-bold">
            <button
                type="button"
                class="flex min-h-14 w-full items-center justify-between gap-4 rounded-sm text-left text-body-lg md:hidden"
                :aria-expanded="open"
                :aria-controls="listId"
                @click="open = !open"
            >
                {{ heading }}
                <ChevronDown
                    :class="
                        cn(
                            'size-5 shrink-0 text-muted-foreground transition-transform duration-(--dur-base)',
                            open && 'rotate-180',
                        )
                    "
                    aria-hidden="true"
                />
            </button>
            <span class="max-md:hidden">{{ heading }}</span>
        </h2>
        <ul
            :id="listId"
            :class="
                cn(
                    'footer-collapsible flex flex-col pb-4 md:mt-3 md:pb-0',
                    !open && 'max-md:hidden',
                )
            "
        >
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="item.url"
                    :target="item.new_tab ? '_blank' : undefined"
                    :rel="item.new_tab ? 'noopener' : undefined"
                    :aria-current="isCurrent(item.url) ? 'page' : undefined"
                    class="inline-flex min-h-11 items-center rounded-sm text-muted-foreground transition-colors duration-(--dur-fast) hover:text-foreground aria-[current=page]:text-foreground md:min-h-9"
                >
                    {{ item.label }}
                </Link>
            </li>
        </ul>
    </div>
</template>
