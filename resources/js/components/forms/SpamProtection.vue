<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue';
import type { SpamProtection } from '@/composables/useSpamProtection';

// FR-LEAD-003: the honeypot input (off-screen, hidden from assistive tech and
// the tab order; only bots fill it) and the Turnstile mount point. The widget
// loads when the form scrolls into view, never with the page. It stays empty
// unless Cloudflare needs a challenge (`interaction-only`).
const props = defineProps<{
    spam: SpamProtection;
    id?: string;
    error?: string;
}>();

const host = useTemplateRef<HTMLElement>('host');

onMounted(() => {
    const element = host.value;

    if (!element || !props.spam.config.turnstile_site_key) {
        return;
    }

    if (typeof IntersectionObserver === 'undefined') {
        void props.spam.mount(element);

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                void props.spam.mount(element);
            }
        },
        { rootMargin: '200px' },
    );

    observer.observe(element);
});
</script>

<template>
    <div>
        <div
            v-if="spam.config.honeypot.enabled"
            aria-hidden="true"
            class="pointer-events-none absolute -left-[9999px] size-px overflow-hidden"
        >
            <input
                v-model="spam.trap.value"
                type="text"
                :name="spam.config.honeypot.name"
                tabindex="-1"
                autocomplete="off"
            />
        </div>
        <div
            v-if="spam.config.turnstile_site_key"
            :id="id"
            ref="host"
            tabindex="-1"
            class="empty:hidden"
        />
        <p v-if="error" class="text-sm text-destructive" role="alert">
            {{ error }}
        </p>
    </div>
</template>
