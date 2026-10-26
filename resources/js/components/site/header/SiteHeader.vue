<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Phone } from '@lucide/vue';
import { computed, ref } from 'vue';
import DesktopNav from '@/components/site/header/DesktopNav.vue';
import MobileMenu from '@/components/site/header/MobileMenu.vue';
import ShortlistLink from '@/components/site/header/ShortlistLink.vue';
import { Button } from '@/components/ui/button';
import { useHeadroom } from '@/composables/useHeadroom';
import { useShortlist } from '@/composables/useShortlist';
import { usePauseSmoothScroll } from '@/motion/bridge';
import { formatPhone } from '@/lib/format';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { SiteShared } from '@/types/site';

// CMP-HEAD-01. Variants: `transparent` over a first-block hero (until 24 px
// of scroll, or while the mega menu is open), `solid`, and `hidden` (headroom).
// Backgrounds cross-fade by opacity and hiding is a transform (MOT-R01).
const props = defineProps<{
    /** The page starts with a hero and `transparent_on_hero` is on. */
    overHero: boolean;
}>();

const page = usePage<SiteShared>();
const site = computed(() => page.props.site);
const header = computed(() => site.value.header);
const items = computed(() => page.props.menus.header);

const megaOpen = ref('');
const mobileOpen = ref(false);
const focusInside = ref(false);

const { scrolled, hidden } = useHeadroom({
    enabled: computed(() => header.value.sticky),
    pinned: computed(
        () => focusInside.value || megaOpen.value !== '' || mobileOpen.value,
    ),
});

// §8.3: Lenis stops while the mega menu or the mobile menu is open.
usePauseSmoothScroll(() => megaOpen.value !== '' || mobileOpen.value);

const transparent = computed(
    () => props.overHero && !scrolled.value && megaOpen.value === '',
);

// FR-SHORT-001 count badge.
const { count: shortlistCount } = useShortlist();

function currentPath(): string {
    return page.url.split(/[?#]/)[0];
}

/** Exact page or a page below it (`/projects` is current on `/projects/x`). */
function isCurrent(url: string): boolean {
    const path = currentPath();

    return url === '/'
        ? path === '/'
        : path === url || path.startsWith(`${url}/`);
}

function onFocusOut(event: FocusEvent): void {
    const next = event.relatedTarget as Node | null;
    focusInside.value =
        next !== null && (event.currentTarget as HTMLElement).contains(next);
}
</script>

<template>
    <header
        :class="
            cn(
                'inset-x-0 top-0 z-(--z-header) transition-transform duration-(--dur-base) ease-(--ease-standard)',
                header.sticky ? 'fixed' : 'absolute',
                hidden && '-translate-y-full',
                transparent ? 'text-white' : 'text-foreground',
            )
        "
        @focusin="focusInside = true"
        @focusout="onFocusOut"
    >
        <!-- transparent: bottom gradient for legibility; solid: frosted bar -->
        <div
            :class="
                cn(
                    'pointer-events-none absolute inset-0 bg-linear-to-b from-stone-950/35 to-transparent transition-opacity duration-(--dur-base)',
                    transparent ? 'opacity-100' : 'opacity-0',
                )
            "
            aria-hidden="true"
        />
        <div
            :class="
                cn(
                    'pointer-events-none absolute inset-0 border-b border-border bg-background/92 shadow-sm backdrop-blur-md transition-opacity duration-(--dur-base)',
                    transparent ? 'opacity-0' : 'opacity-100',
                )
            "
            aria-hidden="true"
        />

        <div
            class="relative mx-auto flex h-16 max-w-content items-center gap-4 px-4 sm:px-6 lg:h-20 lg:gap-6 lg:px-8 xl:px-10"
        >
            <Link :href="home()" class="shrink-0 rounded-sm">
                <img
                    :src="site.logos.light"
                    :alt="site.name"
                    width="240"
                    height="56"
                    :class="cn('h-8 w-auto lg:h-10', !transparent && 'hidden')"
                />
                <img
                    :src="site.logos.dark"
                    :alt="site.name"
                    width="240"
                    height="56"
                    :class="cn('h-8 w-auto lg:h-10', transparent && 'hidden')"
                />
            </Link>

            <DesktopNav
                v-model="megaOpen"
                :items="items"
                :on-media="transparent"
                :is-current="isCurrent"
                class="hidden lg:flex"
            />

            <div class="ml-auto flex items-center gap-1 lg:gap-3">
                <a
                    v-if="header.show_phone && site.contact.hotline"
                    :href="`tel:${site.contact.hotline}`"
                    class="hidden min-h-11 items-center gap-2 rounded-sm px-2 text-sm whitespace-nowrap xl:inline-flex"
                >
                    <Phone class="size-5" aria-hidden="true" />
                    <span class="sr-only">Hotline</span>
                    <span class="tabular">{{
                        formatPhone(site.contact.hotline)
                    }}</span>
                </a>

                <ShortlistLink
                    v-if="header.show_shortlist"
                    :count="shortlistCount"
                    :on-media="transparent"
                />

                <!-- DEC-46: white over the hero, whose search button is the coral CTA (DP-04). -->
                <Button
                    as-child
                    :variant="transparent ? 'light' : 'accent'"
                    class="hidden lg:inline-flex"
                >
                    <Link :href="header.cta.url">{{ header.cta.label }}</Link>
                </Button>

                <MobileMenu
                    v-model:open="mobileOpen"
                    :items="items"
                    :on-media="transparent"
                    :is-current="isCurrent"
                />
            </div>
        </div>
    </header>
</template>
