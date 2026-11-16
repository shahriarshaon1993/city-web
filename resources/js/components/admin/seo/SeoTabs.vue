<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCan } from '@/composables/useCan';
import { cn } from '@/lib/utils';
import { index as notFoundIndex } from '@/routes/admin/not-found';
import { index as redirectsIndex } from '@/routes/admin/redirects';
import { index as robotsIndex } from '@/routes/admin/robots';

// ADM-SCR-14: Redirects, 404 monitor and Robots.txt are tabs of one SEO screen (Sitemaps and
// Snapshots join them in 19.x). A tab shows only for those who may open it.
defineProps<{ current: 'redirects' | 'not-found' | 'robots' }>();

const { canAny } = useCan();

const tab =
    'inline-flex h-11 items-center border-b-2 px-1 text-sm font-medium transition-colors';
</script>

<template>
    <nav aria-label="SEO" class="-mb-px flex gap-6">
        <Link
            v-if="canAny('redirects.manage', 'seo.view')"
            :href="redirectsIndex.url()"
            :aria-current="current === 'redirects' ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === 'redirects'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            Redirects
        </Link>
        <Link
            v-if="canAny('redirects.manage', 'seo.view')"
            :href="notFoundIndex.url()"
            :aria-current="current === 'not-found' ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === 'not-found'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            404 monitor
        </Link>
        <Link
            v-if="canAny('seo.view', 'seo.manage')"
            :href="robotsIndex.url()"
            :aria-current="current === 'robots' ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === 'robots'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            Robots.txt
        </Link>
    </nav>
</template>
