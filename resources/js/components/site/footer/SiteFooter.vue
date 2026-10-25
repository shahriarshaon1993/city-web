<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, useId } from 'vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import FooterColumn from '@/components/site/footer/FooterColumn.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { socialLinks } from '@/lib/contact';
import { formatPhone } from '@/lib/format';
import { notify } from '@/lib/toast';
import { home } from '@/routes';
import type { SiteShared } from '@/types/site';

// CMP-FOOT-01: dark scope on --gradient-dusk. Rows: brand + newsletter;
// footer_1…4 menus + offices; top 8 areas (SEO-ONP-005); legal, ©, parent
// company, the "Reduce motion" toggle (slot, wired in 3.1) and social.
const page = usePage<SiteShared>();
const site = computed(() => page.props.site);
const footer = computed(() => page.props.footer);
const menus = computed(() => page.props.menus);
const social = computed(() => socialLinks(site.value.social));

const ids = {
    newsletter: useId(),
    email: useId(),
    offices: useId(),
    locations: useId(),
};

function isCurrent(url: string): boolean {
    return page.url.split(/[?#]/)[0] === url;
}

// The newsletter submit (FR-LEAD newsletter) is wired in 7.2.
function subscribe(): void {
    notify.info('Newsletter sign-up opens soon.');
}

const linkClass =
    'inline-flex min-h-11 items-center rounded-sm text-muted-foreground transition-colors duration-(--dur-fast) hover:text-foreground aria-[current=page]:text-foreground md:min-h-9';
</script>

<template>
    <footer
        id="site-footer"
        class="theme-dark bg-(image:--gradient-dusk) text-foreground"
    >
        <div
            class="mx-auto flex max-w-content flex-col gap-12 px-4 pt-16 pb-8 sm:px-6 lg:gap-14 lg:px-8 lg:pt-20 xl:px-10"
        >
            <!-- Row 1: brand + newsletter -->
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-8">
                <div class="flex flex-col gap-5 lg:col-span-5">
                    <Link :href="home()" class="self-start rounded-sm">
                        <img
                            :src="site.logos.light"
                            :alt="site.name"
                            width="240"
                            height="56"
                            loading="lazy"
                            class="h-10 w-auto"
                        />
                    </Link>
                    <p class="max-w-prose text-muted-foreground">
                        {{ site.footer.about }}
                    </p>
                </div>

                <section
                    v-if="site.footer.newsletter"
                    :aria-labelledby="ids.newsletter"
                    class="flex flex-col gap-3 lg:col-span-6 lg:col-start-7"
                >
                    <h2 :id="ids.newsletter" class="text-h4">
                        {{ site.footer.newsletter_title }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ site.footer.newsletter_text }}
                    </p>
                    <form
                        class="mt-1 flex flex-col gap-2"
                        @submit.prevent="subscribe"
                    >
                        <Label :for="ids.email">Email address</Label>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Input
                                :id="ids.email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                inputmode="email"
                                maxlength="255"
                                required
                                class="h-12 sm:flex-1"
                            />
                            <Button type="submit" size="lg">Subscribe</Button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- Row 2: menu columns + offices -->
            <div class="grid gap-x-8 gap-y-10 lg:grid-cols-6">
                <nav
                    aria-label="Footer"
                    class="grid gap-x-8 md:grid-cols-4 lg:col-span-4"
                >
                    <FooterColumn
                        v-for="column in footer.columns"
                        :key="column.menu"
                        :heading="column.heading"
                        :items="menus[column.menu]"
                        :is-current="isCurrent"
                    />
                </nav>

                <section
                    :aria-labelledby="ids.offices"
                    class="flex flex-col gap-4 lg:col-span-2"
                >
                    <h2 :id="ids.offices" class="font-display font-bold">
                        {{ footer.offices_heading }}
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-1">
                        <div
                            v-for="office in footer.offices"
                            :key="office.id"
                            class="flex flex-col gap-1"
                        >
                            <p class="font-semibold">{{ office.name }}</p>
                            <address
                                class="flex flex-col text-muted-foreground not-italic"
                            >
                                <span>{{ office.address }}</span>
                                <span>
                                    {{ office.city
                                    }}<template v-if="office.postal_code">
                                        {{ office.postal_code }}</template
                                    >
                                </span>
                                <a
                                    v-for="phone in office.phones"
                                    :key="phone"
                                    :href="`tel:${phone}`"
                                    :class="linkClass"
                                    class="tabular"
                                >
                                    {{ formatPhone(phone) }}
                                </a>
                                <a
                                    v-if="office.email"
                                    :href="`mailto:${office.email}`"
                                    :class="linkClass"
                                    class="break-all"
                                >
                                    {{ office.email }}
                                </a>
                            </address>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Row 3: top 8 areas -->
            <nav
                v-if="footer.locations.length > 0"
                :aria-labelledby="ids.locations"
                class="flex flex-col gap-2 border-t border-border pt-8"
            >
                <h2 :id="ids.locations" class="font-display font-bold">
                    {{ footer.locations_heading }}
                </h2>
                <ul class="flex flex-wrap gap-x-6">
                    <li v-for="area in footer.locations" :key="area.id">
                        <Link
                            :href="area.url"
                            :aria-current="
                                isCurrent(area.url) ? 'page' : undefined
                            "
                            :class="linkClass"
                        >
                            {{ area.label }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Row 4: legal · © · parent company · motion toggle · social -->
            <div
                class="flex flex-col gap-6 border-t border-border pt-6 text-sm lg:flex-row lg:flex-wrap lg:items-center lg:justify-between"
            >
                <div
                    class="flex flex-col gap-x-6 gap-y-2 lg:flex-row lg:flex-wrap lg:items-center"
                >
                    <nav v-if="menus.legal.length > 0" aria-label="Legal">
                        <ul class="flex flex-wrap gap-x-6">
                            <li v-for="item in menus.legal" :key="item.id">
                                <Link
                                    :href="item.url"
                                    :aria-current="
                                        isCurrent(item.url) ? 'page' : undefined
                                    "
                                    :class="linkClass"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </nav>
                    <p class="text-muted-foreground">
                        {{ site.footer.copyright }}
                    </p>
                    <p
                        v-if="
                            site.footer.show_parent_company &&
                            site.parent_company
                        "
                        class="text-muted-foreground"
                    >
                        <a
                            v-if="site.parent_company.url"
                            :href="site.parent_company.url"
                            :class="linkClass"
                            class="underline underline-offset-4"
                        >
                            A {{ site.parent_company.name }} company
                        </a>
                        <template v-else>
                            A {{ site.parent_company.name }} company
                        </template>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    <slot name="motion-toggle" />
                    <ul
                        v-if="social.length > 0"
                        class="-mx-2.5 flex flex-wrap gap-1"
                    >
                        <li v-for="link in social" :key="link.network">
                            <a
                                :href="link.url"
                                target="_blank"
                                rel="noopener"
                                :aria-label="link.label"
                                :class="
                                    buttonVariants({
                                        variant: 'ghost',
                                        size: 'icon',
                                    })
                                "
                            >
                                <SocialIcon
                                    :network="link.network"
                                    class="size-5"
                                />
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</template>
