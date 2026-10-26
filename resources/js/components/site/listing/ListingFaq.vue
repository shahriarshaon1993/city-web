<script setup lang="ts">
import { useId } from 'vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import type { ListingFaq } from '@/types/listing';

// PG-03 optional FAQ (facet page): heading 4 columns, hairline accordion 8.
// Answers are sanitised HTML from the server (CMS rich text).
defineProps<{
    heading: string;
    items: ListingFaq[];
}>();

const headingId = useId();
</script>

<template>
    <section
        v-if="items.length > 0"
        :aria-labelledby="headingId"
        class="py-(--section-md)"
    >
        <div
            class="mx-auto grid max-w-content gap-8 px-4 sm:px-6 lg:grid-cols-12 lg:px-8 xl:gap-8 xl:px-10"
        >
            <h2
                :id="headingId"
                class="font-display text-h2 text-balance lg:col-span-4"
            >
                {{ heading }}
            </h2>
            <Accordion
                type="single"
                collapsible
                class="border-t border-border lg:col-span-8"
            >
                <AccordionItem
                    v-for="item in items"
                    :key="item.id"
                    :value="String(item.id)"
                >
                    <AccordionTrigger>{{ item.question }}</AccordionTrigger>
                    <AccordionContent>
                        <div
                            class="flex max-w-prose flex-col gap-3 pb-2 text-body text-muted-foreground"
                            v-html="item.answer"
                        />
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    </section>
</template>
