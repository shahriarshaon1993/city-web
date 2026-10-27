<script setup lang="ts">
import { useId } from 'vue';
import type { AreaDetail } from '@/types/location';

// LAY-LOC-DETAIL "INTRO 8 cols | AREA FACTS 4 cols": the editable rich text
// (sanitised HTML; first paragraph as a lead, ≤ prose width) beside a
// typographic hairline <dl> of the area facts (overline label, text-fact
// value) — no cards (direction.md §4). Tablet: intro full width, facts in
// two columns below; phones: two columns too. Facts are not headings.
defineProps<{
    area: AreaDetail;
    factsHeading: string;
}>();

const factsId = useId();
</script>

<template>
    <section class="py-(--section-md)">
        <div
            class="mx-auto grid max-w-content gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-8 lg:px-8 xl:px-10"
        >
            <div
                v-reveal
                class="flex max-w-prose flex-col gap-4 text-body-lg text-pretty text-muted-foreground lg:col-span-7 [&>p:first-child]:text-lead [&>p:first-child]:text-foreground"
                v-html="area.intro"
            />

            <div
                v-if="area.facts.length > 0"
                class="lg:col-span-4 lg:col-start-9"
            >
                <h2
                    :id="factsId"
                    class="border-b border-border pb-4 text-overline text-muted-foreground uppercase"
                >
                    {{ factsHeading }}
                </h2>
                <dl
                    :aria-labelledby="factsId"
                    class="grid grid-cols-2 gap-x-6 lg:grid-cols-1"
                >
                    <div
                        v-for="fact in area.facts"
                        :key="fact.label"
                        class="flex flex-col gap-1 border-b border-border py-4 lg:flex-row lg:items-baseline lg:justify-between lg:gap-6"
                    >
                        <dt
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ fact.label }}
                        </dt>
                        <dd class="text-fact text-foreground lg:text-right">
                            {{ fact.value }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
