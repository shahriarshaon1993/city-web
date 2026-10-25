import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import SiteLayout from '@/layouts/SiteLayout.vue';

// Public entry (ARC-008): only pages/Site/* are bundled here, so no admin
// chunk ever reaches a public route (SEO-PERF-004). Also the SSR entry.
const pages = import.meta.glob<{ default: DefineComponent }>(
    './pages/Site/**/*.vue',
);

void createInertiaApp({
    // `seo.title` is final: the template is applied server-side (SEO-ONP-001).
    title: (title) => title,
    resolve: async (name) => {
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return (await page()).default;
    },
    layout: () => SiteLayout,
});
