<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import {
    lineTokens,
    semanticTokens,
    themes,
} from '@/components/styleguide/tokens';
</script>

<template>
    <StyleguideSection id="sg-semantic" title="Semantic tokens">
        <div class="grid gap-6 lg:grid-cols-2">
            <div
                v-for="theme in themes"
                :key="theme"
                :class="[
                    'flex flex-col gap-3 rounded-xl border bg-background p-4 text-foreground sm:p-6',
                    theme === 'dark' && 'theme-dark',
                ]"
            >
                <h3 class="font-display text-h4">
                    {{ theme === 'light' ? 'Light (:root)' : '.theme-dark' }}
                </h3>
                <ul class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="[bg, fg] in semanticTokens"
                        :key="bg"
                        class="flex min-h-11 items-center rounded-md border px-3 text-sm font-medium"
                        :style="{
                            background: `var(--${bg})`,
                            color: `var(--${fg})`,
                        }"
                    >
                        --{{ bg }}
                    </li>
                </ul>
                <ul class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <li
                        v-for="token in lineTokens"
                        :key="token"
                        class="flex flex-col gap-1 text-xs"
                    >
                        <span
                            class="h-8 rounded-md border-2"
                            :style="{ borderColor: `var(--${token})` }"
                        />
                        --{{ token }}
                    </li>
                </ul>
            </div>
        </div>
    </StyleguideSection>
</template>
