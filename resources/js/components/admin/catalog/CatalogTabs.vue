<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { index as amenitiesIndex } from '@/routes/admin/amenities';
import { index as facetsIndex } from '@/routes/admin/facets';
import { index as locationsIndex } from '@/routes/admin/locations';

// ADM-SCR-03/04/05: cities and areas, amenities and facet pages are tabs of one catalogue
// screen (they share the `catalog.*` permissions, so every tab shows for everyone who sees one).
defineProps<{ current: 'locations' | 'amenities' | 'facets' }>();

const tab =
    'inline-flex h-11 items-center border-b-2 px-1 text-sm font-medium transition-colors';
const tabs = [
    { key: 'locations', label: 'Cities & areas', href: locationsIndex.url() },
    { key: 'amenities', label: 'Amenities', href: amenitiesIndex.url() },
    { key: 'facets', label: 'Facet pages', href: facetsIndex.url() },
] as const;
</script>

<template>
    <nav aria-label="Locations and catalogue" class="-mb-px flex gap-6">
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
