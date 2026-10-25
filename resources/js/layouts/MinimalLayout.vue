<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SeoHead from '@/components/site/SeoHead.vue';
import SkipLink from '@/components/site/SkipLink.vue';
import type { SiteShared } from '@/types/site';

// LAY-ERROR for 500/503: logo-only header, no menus, footer or contact bar,
// so the page needs nothing but `site` and `seo`. The logo is a plain link:
// leaving a failed state reloads the whole app.
const page = usePage<Pick<SiteShared, 'site' | 'seo'>>();
const site = computed(() => page.props.site);
</script>

<template>
    <div class="flex min-h-svh flex-col">
        <SeoHead />
        <SkipLink />
        <header class="border-b border-border">
            <div
                class="mx-auto flex h-16 max-w-content items-center px-4 sm:px-6 lg:h-20 lg:px-8 xl:px-10"
            >
                <a href="/" class="rounded-sm">
                    <img
                        :src="site.logos.dark"
                        :alt="site.name"
                        width="240"
                        height="56"
                        class="h-8 w-auto lg:h-10"
                    />
                </a>
            </div>
        </header>
        <main id="content" tabindex="-1" class="flex-1 outline-none">
            <slot />
        </main>
    </div>
</template>
