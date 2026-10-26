import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { installMotionDirectives } from '@/motion/directives';

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
    // v-reveal / v-split / v-mask / v-counter: no-ops until the lazy motion
    // runtime loads (design/motion.md §8.5), and ignored during SSR.
    withApp(app) {
        installMotionDirectives(app);
    },
    // MOT-PAGE: coral-500 bar after 150 ms (same delay as the main dim).
    progress: {
        color: 'var(--coral-500)',
        delay: 150,
        showSpinner: false,
    },
});
