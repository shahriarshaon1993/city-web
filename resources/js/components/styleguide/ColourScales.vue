<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { gradients, scales, steps } from '@/components/styleguide/tokens';
</script>

<template>
    <StyleguideSection id="sg-colours" title="Colour scales">
        <div
            v-for="scale in scales"
            :key="scale.name"
            class="flex flex-col gap-2"
        >
            <h3 class="font-display text-h4 capitalize">{{ scale.name }}</h3>
            <ul class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                <li
                    v-for="(hex, i) in scale.hex"
                    :key="hex + i"
                    class="flex flex-col gap-1"
                >
                    <span
                        class="h-12 rounded-md border"
                        :style="{
                            background: `var(--${scale.name}-${steps[i]})`,
                        }"
                    />
                    <span class="text-xs font-medium">{{ steps[i] }}</span>
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ hex }}
                    </span>
                </li>
            </ul>
        </div>
        <div class="flex flex-col gap-2">
            <h3 class="font-display text-h4">Gradients</h3>
            <ul class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                <li
                    v-for="gradient in gradients"
                    :key="gradient"
                    class="flex h-24 items-end rounded-lg p-3 text-sm font-semibold text-white"
                    :style="{ background: `var(--gradient-${gradient})` }"
                >
                    --gradient-{{ gradient }}
                </li>
            </ul>
        </div>
    </StyleguideSection>
</template>
