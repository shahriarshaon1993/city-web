<script setup lang="ts">
import { ArrowRight, CalendarCheck, Heart, Phone, Search } from '@lucide/vue';
import { ref } from 'vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

// CMP-BTN-01: every variant × size, icon buttons, on-media and loading.
const variants: NonNullable<ButtonVariants['variant']>[] = [
    'primary',
    'accent',
    'secondary',
    'outline',
    'ghost',
    'link',
    'glow',
    'destructive',
];

const iconButtons = [
    {
        label: 'Add to shortlist',
        icon: Heart,
        variant: 'outline',
        size: 'icon',
    },
    { label: 'Call us', icon: Phone, variant: 'primary', size: 'icon' },
    { label: 'Search', icon: Search, variant: 'ghost', size: 'icon-sm' },
] as const;

const loading = ref(false);
const sendingDemo = ref(false);

function fakeSubmit(): void {
    sendingDemo.value = true;
    setTimeout(() => (sendingDemo.value = false), 2000);
}
</script>

<template>
    <StyleguideSection id="sg-buttons" title="Buttons">
        <label class="flex w-fit items-center gap-3 text-sm">
            <Switch v-model="loading" />
            Loading state on every button
        </label>

        <div
            v-for="variant in variants"
            :key="variant"
            class="flex flex-col gap-2"
        >
            <h3 class="font-mono text-xs text-muted-foreground">
                {{ variant }}
            </h3>
            <div class="flex flex-wrap items-center gap-3">
                <Button :variant="variant" size="sm" :loading="loading">
                    Small
                </Button>
                <Button :variant="variant" :loading="loading">Medium</Button>
                <Button :variant="variant" size="lg" :loading="loading">
                    <template #icon><CalendarCheck /></template>
                    Book a visit
                </Button>
                <Button :variant="variant" :loading="loading">
                    View projects
                    <ArrowRight />
                </Button>
                <Button :variant="variant" disabled>Disabled</Button>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <h3 class="font-mono text-xs text-muted-foreground">
                icon (44 px) · icon-sm (36 px, admin only)
            </h3>
            <div class="flex flex-wrap items-center gap-3">
                <Tooltip v-for="item in iconButtons" :key="item.label">
                    <TooltipTrigger as-child>
                        <Button
                            :variant="item.variant"
                            :size="item.size"
                            :aria-label="item.label"
                        >
                            <component :is="item.icon" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{ item.label }}</TooltipContent>
                </Tooltip>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <h3 class="font-mono text-xs text-muted-foreground">
                on-media · loading keeps width
            </h3>
            <div
                class="flex flex-wrap items-center gap-3 rounded-xl bg-stone-600 bg-(image:--gradient-hero-left) p-6"
            >
                <Button variant="on-media" :loading="loading">
                    Watch the film
                </Button>
                <Button variant="on-media" size="lg" :loading="loading">
                    <template #icon><CalendarCheck /></template>
                    Book a visit
                </Button>
            </div>
            <form
                class="flex max-w-sm flex-col gap-3"
                @submit.prevent="fakeSubmit"
            >
                <Button
                    type="submit"
                    class="w-full sm:w-fit"
                    :loading="sendingDemo"
                >
                    Send enquiry (2 s loading)
                </Button>
            </form>
        </div>
    </StyleguideSection>
</template>
