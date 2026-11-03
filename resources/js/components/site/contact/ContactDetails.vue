<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, useId } from 'vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { socialLinks, whatsappUrl } from '@/lib/contact';
import { formatPhone } from '@/lib/format';
import type { SiteShared } from '@/types/site';

// LAY-CONTACT contact card: hotline set large, WhatsApp, email, opening hours
// and the social profiles as hairline rows (no card). On phones the hotline
// and WhatsApp are the full-width buttons above the form (ContactQuickActions
// in the page), so those two rows only show from 1024 px.
const props = defineProps<{
    heading: string;
    socialHeading: string;
    whatsappText: string;
}>();

const site = computed(() => usePage<SiteShared>().props.site);
const social = computed(() => socialLinks(site.value.social));
const whatsapp = computed(() =>
    site.value.contact.whatsapp
        ? whatsappUrl(site.value.contact.whatsapp, props.whatsappText)
        : null,
);

const headingId = useId();

const labelClass = 'text-overline text-muted-foreground uppercase';
const linkClass =
    'inline-flex min-h-11 items-center underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline';
</script>

<template>
    <section :aria-labelledby="headingId" class="flex flex-col gap-8">
        <h2 :id="headingId" class="font-display text-h3 text-balance">
            {{ heading }}
        </h2>

        <dl class="flex flex-col divide-y divide-border border-y border-border">
            <div
                v-if="site.contact.hotline"
                class="flex flex-col gap-1 py-5 max-lg:hidden"
            >
                <dt :class="labelClass">Hotline</dt>
                <dd>
                    <a
                        :href="`tel:${site.contact.hotline}`"
                        class="tabular inline-flex min-h-11 items-center text-h3 font-light text-primary underline-offset-4 hover:underline"
                    >
                        {{ formatPhone(site.contact.hotline) }}
                    </a>
                </dd>
            </div>
            <div v-if="whatsapp" class="flex flex-col gap-1 py-5 max-lg:hidden">
                <dt :class="labelClass">WhatsApp</dt>
                <dd>
                    <a
                        :href="whatsapp"
                        target="_blank"
                        rel="noopener"
                        :class="linkClass"
                        class="gap-2"
                    >
                        <SocialIcon
                            network="whatsapp"
                            class="size-5 text-whatsapp"
                        />
                        Chat on WhatsApp
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </dd>
            </div>
            <div
                v-if="site.contact.emails.length > 0"
                class="flex flex-col gap-1 py-5"
            >
                <dt :class="labelClass">Email</dt>
                <dd v-for="address in site.contact.emails" :key="address">
                    <a
                        :href="`mailto:${address}`"
                        :class="linkClass"
                        class="break-all"
                    >
                        {{ address }}
                    </a>
                </dd>
            </div>
            <div
                v-if="site.contact.office_hours"
                class="flex flex-col gap-1 py-5"
            >
                <dt :class="labelClass">Opening hours</dt>
                <dd class="tabular">{{ site.contact.office_hours }}</dd>
            </div>
        </dl>

        <div v-if="social.length > 0" class="flex flex-col gap-3">
            <h3 :class="labelClass">{{ socialHeading }}</h3>
            <ul class="-mx-2.5 flex flex-wrap gap-1">
                <li v-for="link in social" :key="link.network">
                    <Button
                        as="a"
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        variant="ghost"
                        size="icon"
                        :aria-label="`${link.label} (opens in a new tab)`"
                    >
                        <SocialIcon :network="link.network" class="size-5" />
                    </Button>
                </li>
            </ul>
        </div>
    </section>
</template>
