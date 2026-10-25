<script setup lang="ts">
// Brand glyphs drawn in the Lucide style (24 grid, stroke currentColor),
// since @lucide/vue ships no brand icons (design/foundations.md §5.1).
// Decorative: the surrounding link carries the accessible name.
export type SocialNetwork =
    | 'facebook'
    | 'instagram'
    | 'linkedin'
    | 'youtube'
    | 'x'
    | 'whatsapp';

defineProps<{
    network: SocialNetwork;
}>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <template v-if="network === 'facebook'">
            <path
                d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"
            />
        </template>
        <template v-else-if="network === 'instagram'">
            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
            <path d="M17.5 6.5h.01" />
        </template>
        <template v-else-if="network === 'linkedin'">
            <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"
            />
            <rect width="4" height="12" x="2" y="9" />
            <circle cx="4" cy="4" r="2" />
        </template>
        <template v-else-if="network === 'youtube'">
            <path
                d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"
            />
            <path d="m10 15 5-3-5-3z" />
        </template>
        <template v-else-if="network === 'x'">
            <path d="M4 4l11.733 16h4.267l-11.733-16z" />
            <path d="M4 20l6.768-6.768m2.46-2.46L20 4" />
        </template>
        <template v-else>
            <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21" />
            <path
                d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"
            />
        </template>
    </svg>
</template>
