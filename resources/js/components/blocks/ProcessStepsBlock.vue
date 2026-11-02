<script setup lang="ts">
import BlockSection from '@/components/blocks/BlockSection.vue';
import type { ProcessStepsBlock } from '@/types/blocks';

// CMP-BLK-STEPS: heading 4 / steps 8 (sticky heading from 1024 px). A hairline
// runs down the steps and is drawn in teal as they scroll through the
// viewport (MOT-LINE, scrubbed; complete without JS and under reduced
// motion). Numbers "01–08" are light teal Manrope — decorative, not
// headings; step titles are H3 (CMS-PB-004). Each step has a small hexagon
// node on the line (DP-03 geometry).
defineProps<{
    block: ProcessStepsBlock;
    first: boolean;
}>();

function stepNumber(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-x-8 xl:gap-x-10">
            <div class="lg:col-span-4">
                <component
                    :is="headingTag"
                    :id="headingId"
                    v-reveal
                    class="text-h2 text-balance lg:sticky lg:top-[calc(var(--header-height)+2rem)]"
                >
                    {{ block.data.heading }}
                </component>
            </div>

            <div v-line class="relative lg:col-span-8">
                <span
                    class="absolute top-2 bottom-2 left-1.5 w-px bg-border"
                    aria-hidden="true"
                />
                <span
                    data-line-path
                    data-line-axis="y"
                    class="absolute top-2 bottom-2 left-1.5 w-px bg-primary"
                    aria-hidden="true"
                />
                <ol class="flex flex-col">
                    <li
                        v-for="(step, index) in block.data.steps"
                        :key="index"
                        v-reveal
                        class="relative pb-12 pl-10 last:pb-0 sm:grid sm:grid-cols-[5rem_minmax(0,1fr)] sm:gap-x-6 sm:pl-12 lg:pb-14"
                    >
                        <span
                            class="absolute top-1.5 left-0 size-3 bg-primary [clip-path:polygon(25%_6.7%,75%_6.7%,100%_50%,75%_93.3%,25%_93.3%,0_50%)]"
                            aria-hidden="true"
                        />
                        <p
                            class="tabular text-h2 text-primary max-sm:mb-1"
                            aria-hidden="true"
                        >
                            {{ stepNumber(index) }}
                        </p>
                        <div class="flex flex-col gap-3">
                            <h3 class="text-h3 text-balance">
                                {{ step.title }}
                            </h3>
                            <p
                                class="max-w-prose text-pretty text-muted-foreground"
                            >
                                {{ step.text }}
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </BlockSection>
</template>
