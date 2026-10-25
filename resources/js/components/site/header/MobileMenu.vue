<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Menu, Phone, X } from '@lucide/vue';
import {
    AccordionContent,
    AccordionHeader,
    AccordionItem,
    AccordionRoot,
    AccordionTrigger,
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import type { SocialNetwork } from '@/components/site/SocialIcon.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { formatPhone } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { MenuItem, SiteShared } from '@/types/site';

// CMP-NAV-03 (+ CMP-MODAL-01): full-screen sheet from the right, max 420 px
// from md, dark scope. Reka Dialog traps focus, closes on Esc, returns focus
// to the trigger, hides the background from AT and locks body scroll.
// MOT-MENU is CSS here (sheet slide + link stagger); GSAP replaces it in 3.2.
const props = defineProps<{
    items: MenuItem[];
    onMedia: boolean;
    isCurrent: (url: string) => boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage<SiteShared>();
const site = computed(() => page.props.site);
const extraItems = computed(() => page.props.menus.mobile_extra);

const socialLinks = computed(() =>
    (Object.entries(site.value.social) as [SocialNetwork, string][]).filter(
        ([, url]) => Boolean(url),
    ),
);

const whatsappUrl = computed(() =>
    site.value.contact.whatsapp
        ? `https://wa.me/${site.value.contact.whatsapp.replace(/\D/g, '')}`
        : null,
);

const socialNames: Record<SocialNetwork, string> = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    linkedin: 'LinkedIn',
    youtube: 'YouTube',
    x: 'X',
    whatsapp: 'WhatsApp',
};

/** Child links of an item: its children, or its mega link columns. */
function groupsOf(
    item: MenuItem,
): { heading: string | null; items: MenuItem[] }[] {
    if (item.mega && item.mega.length > 0) {
        return item.mega.flatMap((column) =>
            column.kind === 'featured_projects'
                ? []
                : [{ heading: column.heading, items: column.items }],
        );
    }

    return item.children.length > 0
        ? [{ heading: null, items: item.children }]
        : [];
}

const openSection = computed(
    () =>
        props.items
            .find((item) =>
                groupsOf(item).some((group) =>
                    group.items.some((link) => props.isCurrent(link.url)),
                ),
            )
            ?.id.toString() ?? undefined,
);

// Inertia keeps the layout mounted: close on every navigation.
watch(
    () => page.url,
    () => {
        open.value = false;
    },
);

// The desktop nav takes over from lg (patterns.md §10).
let desktopQuery: MediaQueryList | undefined;

function closeOnDesktop(event: MediaQueryListEvent): void {
    if (event.matches) {
        open.value = false;
    }
}

onMounted(() => {
    desktopQuery = window.matchMedia('(min-width: 1024px)');
    desktopQuery.addEventListener('change', closeOnDesktop);
});

onBeforeUnmount(() => {
    desktopQuery?.removeEventListener('change', closeOnDesktop);
});

const linkClass =
    'flex min-h-11 items-center rounded-sm py-1 transition-colors duration-(--dur-fast) hover:text-primary aria-[current=page]:text-primary';
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogTrigger
            aria-label="Open menu"
            :class="
                cn(
                    buttonVariants({ variant: 'ghost', size: 'icon' }),
                    'lg:hidden',
                    onMedia &&
                        'text-white hover:not-disabled:bg-white/15 hover:not-disabled:text-white',
                )
            "
        >
            <Menu class="size-6" aria-hidden="true" />
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-(--z-overlay) bg-stone-950/60 backdrop-blur-sm duration-200 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                :aria-describedby="undefined"
                class="theme-dark mobile-menu fixed inset-y-0 right-0 z-(--z-drawer) flex w-screen flex-col bg-(image:--gradient-dusk) shadow-lg outline-none md:max-w-105"
            >
                <DialogTitle class="sr-only">Menu</DialogTitle>

                <div
                    class="flex h-16 shrink-0 items-center justify-between gap-4 px-4 sm:px-6"
                >
                    <Link href="/" class="shrink-0 rounded-sm">
                        <img
                            :src="site.logos.light"
                            :alt="site.name"
                            width="240"
                            height="56"
                            class="h-8 w-auto"
                        />
                    </Link>
                    <DialogClose
                        aria-label="Close menu"
                        :class="
                            buttonVariants({ variant: 'ghost', size: 'icon' })
                        "
                    >
                        <X class="size-6" aria-hidden="true" />
                    </DialogClose>
                </div>

                <div
                    class="flex min-h-0 flex-1 flex-col gap-8 overflow-y-auto overscroll-contain px-4 pt-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:px-6"
                >
                    <nav aria-label="Main">
                        <AccordionRoot
                            type="single"
                            collapsible
                            :default-value="openSection"
                            as="ul"
                            class="flex flex-col"
                        >
                            <li
                                v-for="(item, index) in items"
                                :key="item.id"
                                class="mobile-menu-link border-b border-border"
                                :style="{ '--i': index }"
                            >
                                <AccordionItem
                                    v-if="groupsOf(item).length > 0"
                                    :value="String(item.id)"
                                >
                                    <AccordionHeader as="div">
                                        <AccordionTrigger
                                            class="group flex min-h-14 w-full items-center justify-between gap-4 rounded-sm py-2 text-left font-display text-h3"
                                        >
                                            {{ item.label }}
                                            <ChevronDown
                                                class="size-6 shrink-0 text-muted-foreground transition-transform duration-(--dur-base) group-data-[state=open]:rotate-180"
                                                aria-hidden="true"
                                            />
                                        </AccordionTrigger>
                                    </AccordionHeader>
                                    <AccordionContent
                                        class="overflow-hidden data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down"
                                    >
                                        <div class="flex flex-col gap-5 pb-5">
                                            <div
                                                v-for="(
                                                    group, groupIndex
                                                ) in groupsOf(item)"
                                                :key="groupIndex"
                                            >
                                                <p
                                                    v-if="group.heading"
                                                    class="mb-1 text-overline text-muted-foreground uppercase"
                                                >
                                                    {{ group.heading }}
                                                </p>
                                                <ul>
                                                    <li
                                                        v-for="link in group.items"
                                                        :key="link.id"
                                                    >
                                                        <Link
                                                            :href="link.url"
                                                            :aria-current="
                                                                isCurrent(
                                                                    link.url,
                                                                )
                                                                    ? 'page'
                                                                    : undefined
                                                            "
                                                            :class="linkClass"
                                                            class="text-body-lg"
                                                        >
                                                            {{ link.label }}
                                                        </Link>
                                                    </li>
                                                </ul>
                                            </div>
                                            <Link
                                                v-if="item.mega"
                                                :href="item.url"
                                                :aria-current="
                                                    isCurrent(item.url)
                                                        ? 'page'
                                                        : undefined
                                                "
                                                :class="linkClass"
                                                class="font-semibold text-primary"
                                            >
                                                {{
                                                    `View all ${item.label.toLowerCase()}`
                                                }}
                                            </Link>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>

                                <Link
                                    v-else
                                    :href="item.url"
                                    :aria-current="
                                        isCurrent(item.url) ? 'page' : undefined
                                    "
                                    :class="linkClass"
                                    class="min-h-14 font-display text-h3"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </AccordionRoot>

                        <ul
                            v-if="extraItems.length > 0"
                            class="mobile-menu-link mt-4 flex flex-col"
                            :style="{ '--i': items.length }"
                        >
                            <li v-for="link in extraItems" :key="link.id">
                                <Link
                                    :href="link.url"
                                    :aria-current="
                                        isCurrent(link.url) ? 'page' : undefined
                                    "
                                    :class="linkClass"
                                    class="text-body-lg font-medium"
                                >
                                    {{ link.label }}
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <div
                        class="mobile-menu-link mt-auto flex flex-col gap-3"
                        :style="{ '--i': items.length + 1 }"
                    >
                        <Button
                            v-if="site.contact.hotline"
                            as="a"
                            :href="`tel:${site.contact.hotline}`"
                            variant="outline"
                            size="lg"
                        >
                            <Phone class="size-5" aria-hidden="true" />
                            <span class="sr-only">Call</span>
                            {{ formatPhone(site.contact.hotline) }}
                        </Button>
                        <Button
                            v-if="whatsappUrl"
                            as="a"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            variant="outline"
                            size="lg"
                        >
                            <SocialIcon network="whatsapp" class="size-5" />
                            WhatsApp
                        </Button>
                        <Button as-child variant="accent" size="lg">
                            <Link :href="site.header.cta.url">
                                {{ site.header.cta.label }}
                            </Link>
                        </Button>

                        <ul
                            v-if="socialLinks.length > 0"
                            class="mt-2 flex flex-wrap justify-center gap-1"
                        >
                            <li
                                v-for="[network, url] in socialLinks"
                                :key="network"
                            >
                                <a
                                    :href="url"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="socialNames[network]"
                                    :class="
                                        buttonVariants({
                                            variant: 'ghost',
                                            size: 'icon',
                                        })
                                    "
                                >
                                    <SocialIcon
                                        :network="network"
                                        class="size-5"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
