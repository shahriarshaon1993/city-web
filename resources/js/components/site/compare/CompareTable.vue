<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, Plus, X } from '@lucide/vue';
import { computed } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { COMPARE_MAX } from '@/composables/useCompare';
import { cn } from '@/lib/utils';
import type { CompareGroup } from '@/types/shortlist';
import type { ProjectCard } from '@/types/site';

// CMP-COMPARE-01 table (PG-21, FR-SHORT-003) in the hairline language: a
// real <table> (caption, column and row headers) with the attribute names
// pinned left and a header row of compact project cards pinned on top.
// Below 1024 px it scrolls inside its own box both ways (first column
// 120 px); from 1024 px the page scrolls and the header row sticks under the
// site header (`.compare-*` in app.css). Rows whose values differ are tinted
// greige-teal (`surface-brand`, the spec's teal-50) — the intro says so in
// words. Fewer than 3 projects leave an "Add a project" column.
const props = defineProps<{
    projects: ProjectCard[];
    groups: CompareGroup[];
    addUrl: string;
    addLabel: string;
}>();

const emit = defineEmits<{
    remove: [project: ProjectCard];
}>();

const openSlot = computed(() => props.projects.length < COMPARE_MAX);
const caption = computed(
    () =>
        `Comparison of ${props.projects.map((project) => project.name).join(', ')}`,
);

const cell = 'border-b border-border px-3 py-3.5 text-left align-top sm:px-4';
const rowHead =
    'sticky left-0 z-(--z-raised) w-30 min-w-30 border-r text-sm font-normal text-muted-foreground sm:w-44 sm:min-w-44 lg:w-56';

function tint(differs: boolean): string {
    return differs ? 'bg-surface-brand' : 'bg-background';
}
</script>

<template>
    <div class="compare-scroll -mx-4 sm:mx-0" data-lenis-prevent>
        <table class="w-full border-separate border-spacing-0">
            <caption class="sr-only">
                {{
                    caption
                }}
            </caption>
            <thead>
                <tr>
                    <td
                        :class="
                            cn(
                                cell,
                                rowHead,
                                'compare-head z-(--z-sticky) bg-background align-bottom',
                            )
                        "
                    >
                        <span
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ projects.length }} / {{ COMPARE_MAX }}
                        </span>
                    </td>
                    <th
                        v-for="project in projects"
                        :key="project.id"
                        scope="col"
                        :class="
                            cn(
                                cell,
                                'compare-head sticky z-(--z-raised) w-56 min-w-56 bg-background font-normal',
                            )
                        "
                    >
                        <div class="group/card media-card relative pr-10">
                            <ResponsiveImage
                                :image="project.cover"
                                preset="mega-card"
                                img-class="media-zoom"
                                class="mb-3 hidden w-28 rounded-lg sm:block"
                            />
                            <p
                                class="truncate text-overline text-muted-foreground uppercase"
                            >
                                {{ project.segment_label }} ·
                                {{ project.location.area }}
                            </p>
                            <p
                                class="mt-1 font-display text-lg leading-snug text-balance"
                            >
                                <Link
                                    :href="project.url"
                                    class="transition-colors duration-(--dur-fast) hover:text-primary"
                                >
                                    {{ project.name }}
                                </Link>
                            </p>
                            <button
                                type="button"
                                class="absolute -top-2 -right-2 flex size-11 items-center justify-center rounded-full text-muted-foreground transition-colors duration-(--dur-fast) hover:bg-muted hover:text-foreground"
                                :aria-label="`Remove ${project.name} from the comparison`"
                                @click="emit('remove', project)"
                            >
                                <X class="size-5" aria-hidden="true" />
                            </button>
                        </div>
                    </th>
                    <td
                        v-if="openSlot"
                        :class="
                            cn(
                                cell,
                                'compare-head sticky z-(--z-raised) w-56 min-w-56 bg-background',
                            )
                        "
                    >
                        <Link
                            :href="addUrl"
                            class="flex min-h-11 items-center gap-2 text-sm text-primary underline-offset-4 hover:underline sm:min-h-21 sm:flex-col sm:items-start sm:justify-end"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-full border border-foreground/30 text-foreground"
                                aria-hidden="true"
                            >
                                <Plus class="size-5" />
                            </span>
                            {{ addLabel }}
                        </Link>
                    </td>
                </tr>
            </thead>
            <tbody v-for="group in groups" :key="group.key">
                <tr v-if="group.heading && group.rows.length > 0">
                    <th
                        scope="colgroup"
                        :colspan="projects.length + (openSlot ? 2 : 1)"
                        class="border-b border-border bg-background pt-10 pb-3 text-left font-normal"
                    >
                        <span
                            class="sticky left-0 px-3 text-overline text-foreground uppercase sm:px-4"
                        >
                            {{ group.heading }}
                        </span>
                    </th>
                </tr>
                <tr v-for="row in group.rows" :key="row.key">
                    <th
                        scope="row"
                        :class="cn(cell, rowHead, tint(row.differs))"
                    >
                        {{ row.label }}
                    </th>
                    <td
                        v-for="(value, index) in row.values"
                        :key="projects[index]?.id ?? index"
                        :class="cn(cell, 'tabular text-sm', tint(row.differs))"
                    >
                        <template v-if="value === true">
                            <Check
                                class="size-5 text-primary"
                                aria-hidden="true"
                            />
                            <span class="sr-only">Yes</span>
                        </template>
                        <template v-else-if="value === false || value === null">
                            <span
                                class="text-muted-foreground"
                                aria-hidden="true"
                                >–</span
                            >
                            <span class="sr-only">{{
                                value === false ? 'No' : 'Not applicable'
                            }}</span>
                        </template>
                        <template v-else>{{ value }}</template>
                    </td>
                    <td v-if="openSlot" :class="cn(cell, tint(row.differs))" />
                </tr>
            </tbody>
        </table>
    </div>
</template>
