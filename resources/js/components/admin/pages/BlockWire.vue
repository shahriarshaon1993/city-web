<script setup lang="ts">
import type { Wire } from './blocks';

// The thumbnail of a block type in the add-block gallery: a few wireframe shapes in the colours of the
// admin (decorative, aria-hidden). 12 shapes cover the 28 types.
defineProps<{ wire: Wire }>();
</script>

<template>
    <div
        class="relative grid aspect-16/10 w-full place-items-stretch overflow-hidden rounded-md border bg-background p-2 text-muted-foreground/40"
        aria-hidden="true"
    >
        <!-- hero: a dark photo with a headline and a button -->
        <div
            v-if="wire === 'hero'"
            class="grid content-end gap-1 rounded bg-foreground/80 p-2"
        >
            <span class="h-1 w-1/3 rounded-full bg-background/50" />
            <span class="h-2 w-2/3 rounded-full bg-background/90" />
            <span class="h-1.5 w-1/5 rounded-sm bg-primary" />
        </div>

        <!-- header: a title and an intro on a hairline -->
        <div v-else-if="wire === 'header'" class="grid content-center gap-1.5">
            <span class="h-1 w-1/4 rounded-full bg-current" />
            <span class="h-2.5 w-3/5 rounded-full bg-foreground/70" />
            <span class="h-1 w-4/5 rounded-full bg-current" />
            <span class="mt-1 h-px w-full bg-border" />
        </div>

        <!-- split: text beside a picture -->
        <div
            v-else-if="wire === 'split'"
            class="grid grid-cols-2 items-center gap-2"
        >
            <div class="grid gap-1">
                <span class="h-2 w-4/5 rounded-full bg-foreground/70" />
                <span class="h-1 w-full rounded-full bg-current" />
                <span class="h-1 w-5/6 rounded-full bg-current" />
                <span class="h-1.5 w-1/3 rounded-sm bg-primary/70" />
            </div>
            <span class="h-full rounded bg-muted" />
        </div>

        <!-- cards: a row of cards -->
        <div v-else-if="wire === 'cards'" class="grid content-center gap-1.5">
            <span class="h-1.5 w-1/3 rounded-full bg-foreground/70" />
            <div class="grid grid-cols-3 gap-1.5">
                <span v-for="n in 3" :key="n" class="grid gap-1">
                    <span class="aspect-4/3 rounded bg-muted" />
                    <span class="h-1 w-3/4 rounded-full bg-current" />
                </span>
            </div>
        </div>

        <!-- text: paragraphs -->
        <div
            v-else-if="wire === 'text'"
            class="grid content-center gap-1.5 px-3"
        >
            <span class="h-2 w-1/2 rounded-full bg-foreground/70" />
            <span
                v-for="n in 5"
                :key="n"
                class="h-1 rounded-full bg-current"
                :class="n === 5 ? 'w-2/3' : 'w-full'"
            />
        </div>

        <!-- band: one line and a button -->
        <div
            v-else-if="wire === 'band'"
            class="grid place-content-center justify-items-center gap-1.5 rounded bg-primary/15"
        >
            <span class="h-2 w-24 rounded-full bg-foreground/70" />
            <span class="h-1.5 w-10 rounded-sm bg-primary" />
        </div>

        <!-- list: numbered rows -->
        <div v-else-if="wire === 'list'" class="grid content-center gap-1.5">
            <span v-for="n in 3" :key="n" class="flex items-center gap-1.5">
                <span class="size-2.5 shrink-0 rounded-full bg-primary/60" />
                <span
                    class="h-1.5 rounded-full bg-current"
                    :class="n === 2 ? 'w-3/4' : 'w-full'"
                />
            </span>
        </div>

        <!-- stats: big numbers -->
        <div
            v-else-if="wire === 'stats'"
            class="grid grid-cols-4 content-center gap-2"
        >
            <span
                v-for="n in 4"
                :key="n"
                class="grid justify-items-center gap-1"
            >
                <span class="h-3 w-6 rounded bg-foreground/70" />
                <span class="h-1 w-8 rounded-full bg-current" />
            </span>
        </div>

        <!-- media: a large picture with thumbnails -->
        <div
            v-else-if="wire === 'media'"
            class="grid grid-cols-3 grid-rows-2 gap-1"
        >
            <span class="col-span-2 row-span-2 rounded bg-muted" />
            <span class="rounded bg-muted/70" />
            <span class="rounded bg-muted/70" />
        </div>

        <!-- form: fields and a button -->
        <div
            v-else-if="wire === 'form'"
            class="grid grid-cols-2 items-center gap-2"
        >
            <div class="grid gap-1">
                <span class="h-2 w-4/5 rounded-full bg-foreground/70" />
                <span class="h-1 w-full rounded-full bg-current" />
            </div>
            <div class="grid gap-1">
                <span class="h-2.5 rounded border bg-background" />
                <span class="h-2.5 rounded border bg-background" />
                <span class="h-2 w-1/2 rounded-sm bg-primary" />
            </div>
        </div>

        <!-- quote -->
        <div
            v-else-if="wire === 'quote'"
            class="grid place-content-center gap-1.5 px-2"
        >
            <span class="text-3xl/6 font-semibold text-primary/50">“</span>
            <span class="h-1.5 w-full rounded-full bg-foreground/60" />
            <span class="h-1.5 w-3/4 rounded-full bg-foreground/60" />
            <span class="h-1 w-1/4 rounded-full bg-current" />
        </div>

        <!-- tabs -->
        <div v-else class="grid content-center gap-1.5">
            <span class="flex gap-1">
                <span class="h-2 w-8 rounded-sm bg-primary/70" />
                <span class="h-2 w-8 rounded-sm bg-muted" />
                <span class="h-2 w-8 rounded-sm bg-muted" />
            </span>
            <span class="grid grid-cols-2 gap-2">
                <span class="grid gap-1">
                    <span class="h-1.5 w-4/5 rounded-full bg-foreground/60" />
                    <span class="h-1 rounded-full bg-current" />
                </span>
                <span class="h-8 rounded bg-muted" />
            </span>
        </div>
    </div>
</template>
