<script setup lang="ts">
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import type { ProjectFaq } from '@/types/project';

// PG-04 project FAQ (CMP-ACC-01): single-open hairline accordion in the
// content column. Answers are sanitised HTML from the server.
defineProps<{
    items: ProjectFaq[];
}>();
</script>

<template>
    <Accordion type="single" collapsible class="border-t border-border">
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
</template>
