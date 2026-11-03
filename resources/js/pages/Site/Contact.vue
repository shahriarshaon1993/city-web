<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Phone } from '@lucide/vue';
import { computed } from 'vue';
import OfficesBlock from '@/components/blocks/OfficesBlock.vue';
import PageHeaderBlock from '@/components/blocks/PageHeaderBlock.vue';
import ContactDetails from '@/components/site/contact/ContactDetails.vue';
import ContactForm from '@/components/site/forms/ContactForm.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { whatsappUrl } from '@/lib/contact';
import type { ContactPageProps } from '@/types/contact';
import type { SiteShared } from '@/types/site';

// PG-17 / LAY-CONTACT: page header, then the form on a greige action panel
// (7 columns) beside the contact details (hairline rows, 4 columns), then
// the offices block. On phones the hotline and WhatsApp are full-width
// buttons first, then the form, then the details and offices (LAY-CONTACT M).
// `?project=` prefills the project (hidden field) and the Sales subject.
const props = defineProps<ContactPageProps>();

const site = computed(() => usePage<SiteShared>().props.site);
const whatsapp = computed(() =>
    site.value.contact.whatsapp
        ? whatsappUrl(site.value.contact.whatsapp, props.copy.whatsapp_text)
        : null,
);
</script>

<template>
    <div>
        <PageHeaderBlock :block="header" :first="true" />

        <section
            aria-labelledby="contact-form-heading"
            class="pb-(--section-md)"
        >
            <div
                class="mx-auto flex max-w-content flex-col gap-8 px-4 sm:px-6 lg:px-8 xl:px-10"
            >
                <ul class="grid gap-3 sm:grid-cols-2 lg:hidden">
                    <li v-if="site.contact.hotline">
                        <Button
                            as="a"
                            :href="`tel:${site.contact.hotline}`"
                            size="lg"
                            class="w-full"
                        >
                            <Phone aria-hidden="true" />
                            Call the hotline
                        </Button>
                    </li>
                    <li v-if="whatsapp">
                        <Button
                            as="a"
                            :href="whatsapp"
                            target="_blank"
                            rel="noopener"
                            variant="outline"
                            size="lg"
                            class="w-full"
                        >
                            <SocialIcon
                                network="whatsapp"
                                class="size-5 text-whatsapp"
                            />
                            WhatsApp
                            <span class="sr-only">(opens in a new tab)</span>
                        </Button>
                    </li>
                </ul>

                <div
                    class="grid gap-10 lg:grid-cols-12 lg:items-start lg:gap-x-8 xl:gap-x-10"
                >
                    <div
                        v-reveal
                        class="flex flex-col gap-6 rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-7"
                    >
                        <div class="flex flex-col gap-3">
                            <p
                                v-if="project"
                                class="text-overline text-primary uppercase"
                            >
                                About {{ project.name }}
                            </p>
                            <h2
                                id="contact-form-heading"
                                class="font-display text-h3 text-balance"
                            >
                                {{ copy.form_heading }}
                            </h2>
                            <p
                                class="max-w-prose text-pretty text-muted-foreground"
                            >
                                {{ copy.form_intro }}
                            </p>
                        </div>
                        <ContactForm
                            location="contact_page"
                            :subject="subject ?? undefined"
                            :project="project?.slug ?? null"
                        />
                    </div>

                    <div v-reveal class="lg:col-span-4 lg:col-start-9">
                        <ContactDetails
                            :heading="copy.details_heading"
                            :social-heading="copy.social_heading"
                            :whatsapp-text="copy.whatsapp_text"
                        />
                    </div>
                </div>
            </div>
        </section>

        <OfficesBlock :block="offices" :first="false" />
    </div>
</template>
