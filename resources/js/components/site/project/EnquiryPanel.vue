<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Download, LockKeyhole, Phone } from '@lucide/vue';
import { computed, useId } from 'vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { buttonVariants } from '@/components/ui/button';
import { whatsappUrl } from '@/lib/contact';
import { formatBdtCompact, formatPhone } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { EnquiryKind, ProjectCopy, ProjectDetail } from '@/types/project';
import type { SitePageProps } from '@/types/site';

// CMP-ENQ-01 enquiry panel on a greige surface (an action panel, not a
// content card): heading (handed-over projects ask about resale), price
// from or "Price on request", Enquire (teal `primary`: the solid header's
// Enquire stays the one coral CTA, DP-04), Book a site visit (outline),
// the brochure (lock when gated), then WhatsApp (prefilled, FR-LEAD-013;
// green icon, foreground text) and the sales contact with the hotline
// (CMP-PERSON-01 mini: the team, never a person, DEC-06).
// `panel` = the sticky desktop column; `inline` = the tablet card under the
// title and the phone section before similar projects (LAY-PROJ-DETAIL T/M).
// Actions are links to the contact page that open the dialogs with JS.
const props = withDefaults(
    defineProps<{
        project: ProjectDetail;
        copy: ProjectCopy;
        links: Record<EnquiryKind, string>;
        layout?: 'panel' | 'inline';
    }>(),
    { layout: 'panel' },
);

const emit = defineEmits<{
    open: [kind: EnquiryKind];
}>();

const page = usePage<SitePageProps>();
const contact = computed(() => page.props.site.contact);
const headingId = useId();

const brochure = computed(
    () =>
        props.project.documents.find(
            (document) => document.kind === 'brochure',
        ) ?? null,
);

const whatsapp = computed(() => {
    const number = props.project.enquiry.whatsapp ?? contact.value.whatsapp;

    return number
        ? whatsappUrl(number, props.project.enquiry.whatsapp_text)
        : null;
});

const inline = computed(() => props.layout === 'inline');
</script>

<template>
    <aside
        :aria-labelledby="headingId"
        :class="
            cn(
                'rounded-xl bg-surface-subtle p-6 xl:p-8',
                inline && 'sm:p-8 md:grid md:grid-cols-2 md:gap-x-10',
            )
        "
    >
        <div class="flex flex-col">
            <p :id="headingId" class="font-display text-h4 text-balance">
                {{ project.enquiry.heading }}
            </p>
            <div
                class="mt-5 flex flex-col gap-1 border-t border-foreground/10 pt-5"
            >
                <template v-if="project.price_from_bdt !== null">
                    <span class="text-overline text-muted-foreground uppercase">
                        {{ copy.price_from_label }}
                    </span>
                    <span class="tabular font-display text-price">
                        {{ formatBdtCompact(project.price_from_bdt) }}
                    </span>
                </template>
                <template v-else>
                    <span class="font-display text-price">
                        {{ copy.price_on_request }}
                    </span>
                    <span
                        v-if="project.price_note"
                        class="text-sm text-muted-foreground"
                    >
                        {{ project.price_note }}
                    </span>
                </template>
            </div>
        </div>

        <div :class="cn('flex flex-col', inline ? 'mt-6 md:mt-0' : 'mt-6')">
            <div class="flex flex-col gap-3">
                <a
                    :href="links.enquire"
                    :class="buttonVariants({ variant: 'primary', size: 'lg' })"
                    aria-haspopup="dialog"
                    @click.prevent="emit('open', 'enquire')"
                >
                    Enquire
                </a>
                <a
                    :href="links.visit"
                    :class="buttonVariants({ variant: 'outline', size: 'lg' })"
                    aria-haspopup="dialog"
                    @click.prevent="emit('open', 'visit')"
                >
                    Book a site visit
                </a>
                <template v-if="brochure">
                    <a
                        v-if="brochure.is_gated"
                        :href="links.brochure"
                        :class="
                            buttonVariants({ variant: 'ghost', size: 'md' })
                        "
                        aria-haspopup="dialog"
                        @click.prevent="emit('open', 'brochure')"
                    >
                        <LockKeyhole aria-hidden="true" />
                        Download brochure
                    </a>
                    <a
                        v-else-if="brochure.url"
                        :href="brochure.url"
                        download
                        :class="
                            buttonVariants({ variant: 'ghost', size: 'md' })
                        "
                    >
                        <Download aria-hidden="true" />
                        Download brochure
                    </a>
                </template>
            </div>

            <ul
                class="mt-5 divide-y divide-foreground/10 border-y border-foreground/10"
            >
                <li v-if="whatsapp">
                    <a
                        :href="whatsapp"
                        target="_blank"
                        rel="noopener"
                        class="group/wa flex min-h-13 items-center gap-3 py-2 text-sm"
                    >
                        <SocialIcon
                            network="whatsapp"
                            class="size-5 shrink-0 text-whatsapp"
                        />
                        <span
                            class="flex-1 transition-colors duration-(--dur-fast) group-hover/wa:text-primary"
                        >
                            Chat on WhatsApp
                            <span class="sr-only">(opens in a new tab)</span>
                        </span>
                        <ArrowUpRight
                            class="size-4 text-muted-foreground transition-transform duration-(--dur-base) group-hover/wa:translate-x-0.5 group-hover/wa:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </a>
                </li>
                <li
                    v-if="contact.hotline"
                    class="flex min-h-16 items-center gap-3 py-3"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-background"
                        aria-hidden="true"
                    >
                        <img
                            :src="page.props.site.logos.mark"
                            alt=""
                            width="20"
                            height="20"
                            class="size-5"
                        />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="text-sm">{{ copy.sales_name }}</span>
                        <span
                            v-if="contact.office_hours"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ contact.office_hours }}
                        </span>
                    </span>
                    <a
                        :href="`tel:${contact.hotline}`"
                        class="tabular inline-flex min-h-11 items-center gap-1.5 text-sm whitespace-nowrap underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline"
                    >
                        <Phone class="size-4" aria-hidden="true" />
                        {{ formatPhone(contact.hotline) }}
                    </a>
                </li>
            </ul>
        </div>
    </aside>
</template>
