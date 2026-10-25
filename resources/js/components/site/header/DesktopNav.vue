<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import {
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuRoot,
    NavigationMenuTrigger,
} from 'reka-ui';
import { computed, onBeforeUnmount } from 'vue';
import MegaPanel from '@/components/site/header/MegaPanel.vue';
import { cn } from '@/lib/utils';
import type { MegaColumn, MenuItem } from '@/types/site';

// CMP-HEAD-01 primary nav + CMP-NAV-02 mega menu on Reka NavigationMenu
// (A11Y-D03: triggers are buttons with aria-expanded, arrows move, Esc and
// outside click close). No viewport: the mega panel spans the header
// container (Reka's list wrapper is forced static for that), plain child
// lists drop down under their trigger.
const props = defineProps<{
    items: MenuItem[];
    onMedia: boolean;
    isCurrent: (url: string) => boolean;
}>();

/** The open item's value ('' = closed), shared with the header (headroom pin). */
const open = defineModel<string>({ default: '' });

// CMP-NAV-02: opens after 150 ms hover intent; Reka closes 150 ms after the
// pointer leaves, so another 150 ms here makes the 300 ms of the spec.
// Esc/select while the pointer is inside close at once.
const CLOSE_EXTRA_MS = 150;
let pointerInside = false;
let closeTimer: ReturnType<typeof setTimeout> | undefined;

const value = computed({
    get: () => open.value,
    set: (next: string) => {
        clearTimeout(closeTimer);

        if (next === '' && open.value !== '' && !pointerInside) {
            closeTimer = setTimeout(() => {
                open.value = '';
            }, CLOSE_EXTRA_MS);

            return;
        }

        open.value = next;
    },
});

function onPointerEnter(): void {
    pointerInside = true;
    clearTimeout(closeTimer);
}

function onPointerLeave(): void {
    pointerInside = false;
}

onBeforeUnmount(() => clearTimeout(closeTimer));

function hasMega(item: MenuItem): item is MenuItem & { mega: MegaColumn[] } {
    return item.mega !== null && item.mega.length > 0;
}

function megaUrls(columns: MegaColumn[]): string[] {
    return columns.flatMap((column) =>
        column.kind === 'featured_projects'
            ? column.projects.map((project) => project.url)
            : column.items.map((link) => link.url),
    );
}

/** Active section: the item, one of its children or a mega link is current. */
function isActive(item: MenuItem): boolean {
    const urls = [
        item.url,
        ...item.children.map((child) => child.url),
        ...(item.mega ? megaUrls(item.mega) : []),
    ];

    return urls.some((url) => props.isCurrent(url));
}

const itemClass = computed(() =>
    cn(
        'group/nav relative inline-flex h-full items-center gap-1 rounded-sm px-3 text-[0.9375rem] font-medium whitespace-nowrap xl:px-4',
        props.onMedia ? 'text-white' : 'text-foreground',
    ),
);

// MOT-UNDERLINE (transform only, MOT-R01): grows from the left on hover and
// focus; the active section keeps a 2 px coral-500 bar and a bold label.
function labelClass(active: boolean): string {
    return cn(
        'relative after:absolute after:inset-x-0 after:-bottom-1.5 after:h-px after:origin-left after:scale-x-0 after:bg-current after:transition-transform after:duration-(--dur-base) after:ease-(--ease-standard) group-hover/nav:after:scale-x-100 group-focus-visible/nav:after:scale-x-100 group-data-[state=open]/nav:after:scale-x-100',
        active && 'font-bold after:h-0.5 after:scale-x-100 after:bg-coral-500',
    );
}
</script>

<template>
    <NavigationMenuRoot
        v-model="value"
        :delay-duration="150"
        aria-label="Main"
        class="h-full [&>div]:static! [&>div]:h-full"
        @pointerenter="onPointerEnter"
        @pointerleave="onPointerLeave"
    >
        <NavigationMenuList class="flex h-full items-center">
            <NavigationMenuItem
                v-for="item in items"
                :key="item.id"
                :value="String(item.id)"
                :class="[
                    'h-full',
                    !hasMega(item) && item.children.length > 0 && 'relative',
                ]"
            >
                <template v-if="hasMega(item) || item.children.length > 0">
                    <NavigationMenuTrigger :class="itemClass">
                        <span :class="labelClass(isActive(item))">
                            {{ item.label }}
                        </span>
                        <ChevronDown
                            class="size-4 transition-transform duration-(--dur-base) group-data-[state=open]/nav:rotate-180"
                            aria-hidden="true"
                        />
                    </NavigationMenuTrigger>

                    <NavigationMenuContent
                        v-if="hasMega(item)"
                        class="mega-content absolute inset-x-0 top-full mx-auto max-w-content"
                    >
                        <MegaPanel :item="item" :is-current="isCurrent" />
                    </NavigationMenuContent>

                    <NavigationMenuContent
                        v-else
                        class="mega-content absolute top-full left-0 w-64"
                    >
                        <ul
                            class="flex flex-col rounded-b-xl border-x border-b bg-popover p-2 text-popover-foreground shadow-lg"
                        >
                            <li v-for="child in item.children" :key="child.id">
                                <NavigationMenuLink
                                    as-child
                                    :active="isCurrent(child.url)"
                                >
                                    <Link
                                        :href="child.url"
                                        class="flex min-h-11 items-center rounded-md px-3 transition-colors duration-(--dur-fast) hover:bg-muted hover:text-primary data-active:font-semibold data-active:text-primary"
                                    >
                                        {{ child.label }}
                                    </Link>
                                </NavigationMenuLink>
                            </li>
                        </ul>
                    </NavigationMenuContent>
                </template>

                <NavigationMenuLink
                    v-else
                    as-child
                    :active="isCurrent(item.url)"
                >
                    <Link :href="item.url" :class="itemClass">
                        <span :class="labelClass(isActive(item))">
                            {{ item.label }}
                        </span>
                    </Link>
                </NavigationMenuLink>
            </NavigationMenuItem>
        </NavigationMenuList>
    </NavigationMenuRoot>
</template>
