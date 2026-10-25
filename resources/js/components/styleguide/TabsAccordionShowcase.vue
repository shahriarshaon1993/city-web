<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

// CMP-TABS-01 (underline + pill) and CMP-ACC-01.
const faqs = [
    {
        q: 'Is the project approved by RAJUK?',
        a: 'Yes. The approval number and the plan are listed under Documents on each project page.',
    },
    {
        q: 'Can NRB customers book from abroad?',
        a: 'Yes. Bookings can be made remotely with a power of attorney and an online payment schedule.',
    },
    {
        q: 'When is handover?',
        a: 'Each project shows its planned handover quarter and the latest construction progress.',
    },
];
</script>

<template>
    <StyleguideSection id="sg-tabs" title="Tabs & accordion">
        <Tabs default-value="overview">
            <TabsList aria-label="Project sections">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="amenities">Amenities</TabsTrigger>
                <TabsTrigger value="plans">Floor plans</TabsTrigger>
                <TabsTrigger value="location" disabled>Location</TabsTrigger>
            </TabsList>
            <TabsContent value="overview" class="py-4 text-body">
                Overview panel — arrow keys move between tabs, Home/End jump.
            </TabsContent>
            <TabsContent value="amenities" class="py-4 text-body">
                Amenities panel.
            </TabsContent>
            <TabsContent value="plans" class="py-4 text-body">
                Floor plans panel.
            </TabsContent>
        </Tabs>
        <Tabs default-value="all">
            <TabsList variant="pill" aria-label="Filter projects">
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="ongoing">Ongoing</TabsTrigger>
                <TabsTrigger value="ready">Ready</TabsTrigger>
            </TabsList>
        </Tabs>
        <Accordion
            type="single"
            collapsible
            default-value="faq-0"
            class="max-w-narrow"
        >
            <AccordionItem
                v-for="(faq, i) in faqs"
                :key="faq.q"
                :value="`faq-${i}`"
            >
                <AccordionTrigger>{{ faq.q }}</AccordionTrigger>
                <AccordionContent>{{ faq.a }}</AccordionContent>
            </AccordionItem>
        </Accordion>
    </StyleguideSection>
</template>
