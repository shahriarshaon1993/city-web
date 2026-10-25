import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Public entry (ARC-008): only pages/Site/* are bundled here, so no admin
// chunk ever reaches a public route (SEO-PERF-004). Also the SSR entry.
const pages = import.meta.glob<{ default: DefineComponent }>(
    './pages/Site/**/*.vue',
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return (await page()).default;
    },
});
