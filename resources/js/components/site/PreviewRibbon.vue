<script setup lang="ts">
import type { SiteShared } from '@/types/site';

// CMS-PUB-005: the fixed "Preview — not published" ribbon of a signed preview page. Floating UI (like
// the compare bar), so it may carry a shadow; it never takes pointer events, so nothing behind it
// becomes unclickable. Above the mobile contact bar on phones.
defineProps<{ preview: NonNullable<SiteShared['preview']> }>();
</script>

<template>
    <div
        class="pointer-events-none fixed bottom-24 left-1/2 z-[var(--z-toast)] -translate-x-1/2 px-4 lg:bottom-6"
        role="status"
    >
        <p
            class="flex flex-wrap items-center justify-center gap-x-3 gap-y-0.5 rounded-md bg-foreground px-4 py-2.5 text-center text-white shadow-lg"
        >
            <span class="text-overline font-medium tracking-[0.16em] uppercase"
                >Preview — not published</span
            >
            <span class="text-sm text-white/75"
                >{{ preview.label }} · link valid until
                {{ preview.expires }}</span
            >
        </p>
    </div>
</template>
