<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { NavigationMenuLink } from 'reka-ui';
import { useId } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Badge } from '@/components/ui/badge';
import { menuIcons } from '@/lib/icons';
import type { MegaColumn, MenuItem } from '@/types/site';

// CMP-NAV-02 panel body: up to 4 columns (CMS-NAV-003) + "View all" row.
// Column titles are labels, not headings (outline stays H1-first).
const props = defineProps<{
    item: MenuItem & { mega: MegaColumn[] };
    isCurrent: (url: string) => boolean;
}>();

const baseId = useId();

const gridColumns: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-3',
    4: 'grid-cols-4',
};

const linkClass =
    'rounded-sm transition-colors duration-(--dur-fast) hover:text-primary';

function columnId(index: number): string {
    return `${baseId}-col-${index}`;
}

const viewAllLabel = `View all ${props.item.label.toLowerCase()}`;
</script>

<template>
    <div
        class="overflow-hidden rounded-b-xl border-x border-b bg-popover text-popover-foreground shadow-lg"
    >
        <div
            :class="[gridColumns[item.mega.length] ?? 'grid-cols-4']"
            class="grid gap-8 p-8"
        >
            <div
                v-for="(column, index) in item.mega"
                :key="index"
                class="mega-column min-w-0"
                :style="{ '--i': index }"
            >
                <p
                    v-if="column.heading"
                    :id="columnId(index)"
                    class="mb-4 text-overline text-muted-foreground uppercase"
                >
                    {{ column.heading }}
                </p>

                <ul
                    v-if="column.kind === 'segments'"
                    :aria-labelledby="
                        column.heading ? columnId(index) : undefined
                    "
                    class="flex flex-col gap-1"
                >
                    <li v-for="link in column.items" :key="link.id">
                        <NavigationMenuLink
                            as-child
                            :active="isCurrent(link.url)"
                        >
                            <Link
                                :href="link.url"
                                class="group -mx-2 flex items-start gap-3 rounded-md p-2 transition-colors duration-(--dur-fast) hover:bg-muted"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-surface-brand text-primary"
                                >
                                    <component
                                        :is="menuIcons[link.icon]"
                                        v-if="link.icon && menuIcons[link.icon]"
                                        class="size-6"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span class="flex min-w-0 flex-col">
                                    <span
                                        class="font-semibold group-hover:text-primary"
                                    >
                                        {{ link.label }}
                                    </span>
                                    <span
                                        v-if="link.description"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ link.description }}
                                    </span>
                                </span>
                            </Link>
                        </NavigationMenuLink>
                    </li>
                </ul>

                <ul
                    v-else-if="column.kind === 'featured_projects'"
                    :aria-labelledby="
                        column.heading ? columnId(index) : undefined
                    "
                    class="flex flex-col gap-3"
                >
                    <li v-for="project in column.projects" :key="project.id">
                        <NavigationMenuLink
                            as-child
                            :active="isCurrent(project.url)"
                        >
                            <Link
                                :href="project.url"
                                class="group -mx-2 flex items-center gap-3 rounded-md p-2 transition-colors duration-(--dur-fast) hover:bg-muted"
                            >
                                <ResponsiveImage
                                    :image="project.cover"
                                    preset="mega-card"
                                    class="w-28 shrink-0 overflow-hidden rounded-md"
                                />
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span
                                        class="leading-tight font-semibold group-hover:text-primary"
                                    >
                                        {{ project.name }}
                                    </span>
                                    <span class="text-sm text-muted-foreground">
                                        {{ project.location.area }}
                                    </span>
                                    <Badge
                                        variant="lifecycle"
                                        :value="project.lifecycle"
                                        size="sm"
                                        class="self-start"
                                    >
                                        {{ project.lifecycle_label }}
                                    </Badge>
                                </span>
                            </Link>
                        </NavigationMenuLink>
                    </li>
                </ul>

                <ul
                    v-else
                    :aria-labelledby="
                        column.heading ? columnId(index) : undefined
                    "
                    class="flex flex-col gap-0.5"
                >
                    <li v-for="link in column.items" :key="link.id">
                        <NavigationMenuLink
                            as-child
                            :active="isCurrent(link.url)"
                        >
                            <Link
                                :href="link.url"
                                :class="linkClass"
                                class="flex items-center justify-between gap-3 py-1.5 data-active:font-semibold data-active:text-primary"
                            >
                                <span>{{ link.label }}</span>
                                <span
                                    v-if="link.count !== null"
                                    class="tabular text-sm text-muted-foreground"
                                >
                                    {{ link.count
                                    }}<span class="sr-only"> projects</span>
                                </span>
                            </Link>
                        </NavigationMenuLink>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t bg-surface-subtle px-8 py-4">
            <NavigationMenuLink as-child>
                <Link
                    :href="item.url"
                    class="group inline-flex items-center gap-2 font-semibold text-primary"
                >
                    {{ viewAllLabel }}
                    <ArrowRight
                        class="size-5 transition-transform duration-(--dur-fast) group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </Link>
            </NavigationMenuLink>
        </div>
    </div>
</template>
