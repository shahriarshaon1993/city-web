<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SiteShared } from '@/types/site';

// Client twin of the head in resources/views/app.blade.php (SEO-REN-01):
// same `head-key`s as the Blade `data-inertia` values, so a client visit
// replaces the server tags instead of adding a second set. The LCP preload
// stays server-only (HERO-030).
const page = usePage<SiteShared>();
const seo = computed(() => page.props.seo);

/** Same escaping as PHP's JSON_HEX_TAG | JSON_HEX_AMP, so the JSON cannot close its tag. */
function jsonLd(graph: Record<string, unknown>): string {
    return JSON.stringify(graph)
        .replace(/</g, '\\u003C')
        .replace(/>/g, '\\u003E')
        .replace(/&/g, '\\u0026');
}
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta
            v-if="seo.description"
            head-key="description"
            name="description"
            :content="seo.description"
        />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link
            v-if="seo.canonical"
            head-key="canonical"
            rel="canonical"
            :href="seo.canonical"
        />
        <meta head-key="og:type" property="og:type" :content="seo.og.type" />
        <meta head-key="og:title" property="og:title" :content="seo.og.title" />
        <meta
            v-if="seo.og.description"
            head-key="og:description"
            property="og:description"
            :content="seo.og.description"
        />
        <meta head-key="og:url" property="og:url" :content="seo.og.url" />
        <template v-if="seo.og.image">
            <meta
                head-key="og:image"
                property="og:image"
                :content="seo.og.image"
            />
            <meta
                head-key="og:image:alt"
                property="og:image:alt"
                :content="seo.og.image_alt ?? ''"
            />
        </template>
        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="seo.og.site_name"
        />
        <meta
            head-key="og:locale"
            property="og:locale"
            :content="seo.og.locale"
        />
        <meta
            head-key="twitter:card"
            name="twitter:card"
            :content="seo.twitter_card"
        />
        <component
            :is="'script'"
            v-for="(graph, index) in seo.json_ld"
            :key="index"
            :head-key="`json-ld-${index}`"
            type="application/ld+json"
            >{{ jsonLd(graph) }}</component
        >
    </Head>
</template>
