<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import CatalogTabs from '@/components/admin/catalog/CatalogTabs.vue';
import { Button } from '@/components/ui/button';
import type { FacetRow } from '@/types';

// ADM-SCR-05 Facet pages: the landing pages for one segment or one project status
// (/projects/type/residential, /projects/status/ongoing). The rows are fixed — only their heading,
// intro and SEO change — so this is a short list, not a table with paging and filters.
const props = defineProps<{ facets: FacetRow[]; canManage: boolean }>();

const groups = computed(() => [
    {
        label: 'By segment',
        rows: props.facets.filter((facet) => facet.kind === 'segment'),
    },
    {
        label: 'By project status',
        rows: props.facets.filter((facet) => facet.kind === 'status'),
    },
]);
</script>

<template>
    <Head title="Facet pages" />

    <ListPageHeader
        title="Facet pages"
        description="The landing pages for a segment or a project status. Each has its own heading, intro and search settings."
    >
        <template #tabs>
            <CatalogTabs current="facets" />
        </template>
    </ListPageHeader>

    <div class="grid max-w-4xl gap-8">
        <section
            v-for="group in groups"
            :key="group.label"
            :aria-label="group.label"
            class="grid gap-3"
        >
            <h2
                class="text-overline font-medium tracking-[0.12em] text-muted-foreground uppercase"
            >
                {{ group.label }}
            </h2>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="facet in group.rows"
                    :key="facet.id"
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 p-3"
                >
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="facet.edit_url"
                            class="text-sm font-medium hover:underline"
                            >{{ facet.title }}</Link
                        >
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ facet.path }}
                        </p>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Intro:
                        {{
                            facet.intro_words === 0
                                ? 'none'
                                : `${facet.intro_words} words`
                        }}
                        <template v-if="facet.has_seo"> · own SEO</template>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="facet.edit_url">
                                <Pencil aria-hidden="true" />
                                {{ canManage ? 'Edit' : 'View'
                                }}<span class="sr-only">
                                    {{ facet.title }}</span
                                >
                            </Link>
                        </Button>
                        <Button as-child variant="ghost" size="icon-sm">
                            <a
                                :href="facet.public_url"
                                target="_blank"
                                rel="noopener"
                                :aria-label="`View ${facet.title} on the site`"
                            >
                                <ExternalLink aria-hidden="true" />
                            </a>
                        </Button>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
