<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { onBeforeUnmount, useId, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useConsent } from '@/composables/useConsent';

// CMP-COOKIE-01: a bottom card (560 px, left on desktop; full width on
// phones) with Accept all and Reject non-essential at equal weight, and
// Preferences opening the dialog. It never blocks the page (no overlay, no
// focus trap) and does not take focus on load; it sits right after the skip
// link in the DOM, so keyboard users reach it first. Drawn only after mount:
// without JavaScript there is nothing to choose and nothing that tracks.
const { bannerOpen, cookies, acceptAll, rejectNonEssential, openPreferences } =
    useConsent();

const titleId = useId();

// WCAG 2.4.11: the card floats over content, so keyboard focus must scroll
// clear of it. The root's scroll-padding-bottom reads this variable (app.css).
const card = useTemplateRef<HTMLElement>('card');
const { height } = useElementSize(card, undefined, { box: 'border-box' });

watch(height, (value) => {
    document.documentElement.style.setProperty(
        '--bottom-obstruction',
        value > 0 ? `${value}px` : '0px',
    );
});

onBeforeUnmount(() => {
    document.documentElement.style.removeProperty('--bottom-obstruction');
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-(--dur-base) ease-(--ease-standard)"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-(--dur-fast) ease-(--ease-standard)"
        leave-to-class="translate-y-4 opacity-0"
    >
        <section
            v-if="bannerOpen"
            ref="card"
            role="region"
            :aria-labelledby="titleId"
            class="fixed inset-x-0 bottom-0 z-(--z-dropdown) flex flex-col gap-4 border-t border-border bg-background px-4 pt-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-xl sm:inset-x-auto sm:bottom-6 sm:left-6 sm:w-[min(35rem,calc(100%-3rem))] sm:rounded-xl sm:border sm:px-6 sm:pt-6 sm:pb-6"
        >
            <div class="flex flex-col gap-2">
                <h2 :id="titleId" class="font-display text-h4">
                    {{ cookies.title }}
                </h2>
                <p class="text-sm text-pretty text-muted-foreground">
                    {{ cookies.text }}
                    <a
                        :href="cookies.policy_url"
                        class="whitespace-nowrap text-foreground underline underline-offset-4 hover:text-primary"
                    >
                        Privacy policy
                    </a>
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <Button type="button" class="sm:flex-1" @click="acceptAll()">
                    Accept all
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="sm:flex-1"
                    @click="rejectNonEssential()"
                >
                    Reject non-essential
                </Button>
                <Button
                    type="button"
                    variant="link"
                    class="text-foreground sm:w-full"
                    aria-haspopup="dialog"
                    @click="openPreferences()"
                >
                    Preferences
                </Button>
            </div>
        </section>
    </Transition>
</template>
