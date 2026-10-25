<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { radii, shadows, typeScale } from '@/components/styleguide/tokens';
</script>

<template>
    <StyleguideSection id="sg-type" title="Type scale">
        <dl class="flex flex-col divide-y">
            <div
                v-for="item in typeScale"
                :key="item.token"
                class="grid gap-1 py-4 md:grid-cols-[10rem_1fr] md:items-baseline md:gap-6"
            >
                <dt class="font-mono text-xs text-muted-foreground">
                    {{ item.token }}
                </dt>
                <dd :class="item.cls">{{ item.sample }}</dd>
            </div>
        </dl>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="flex flex-col gap-3">
                <h3 class="font-display text-h4">Radius</h3>
                <ul class="flex flex-wrap gap-3">
                    <li
                        v-for="r in radii"
                        :key="r"
                        class="flex size-16 items-center justify-center border-2 border-primary bg-secondary text-xs"
                        :style="{
                            borderRadius:
                                r === 'full' ? '9999px' : `var(--radius-${r})`,
                        }"
                    >
                        {{ r }}
                    </li>
                </ul>
            </div>
            <div class="flex flex-col gap-3">
                <h3 class="font-display text-h4">Shadows</h3>
                <ul class="flex flex-wrap gap-4">
                    <li
                        v-for="s in shadows"
                        :key="s"
                        class="flex size-16 items-center justify-center rounded-lg bg-card text-xs"
                        :style="{ boxShadow: `var(--shadow-${s})` }"
                    >
                        {{ s }}
                    </li>
                </ul>
            </div>
        </div>
    </StyleguideSection>
</template>
