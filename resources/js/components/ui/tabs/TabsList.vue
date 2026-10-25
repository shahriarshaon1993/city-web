<script setup lang="ts">
import type { TabsListProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsIndicator, TabsList } from "reka-ui"
import { cn } from "@/lib/utils"

// CMP-TABS-01: `underline` (content tabs, 2 px primary indicator sliding, MOT-TAB)
// or `pill` (segment filter). Triggers style themselves from data-variant.
const props = withDefaults(defineProps<TabsListProps & {
  class?: HTMLAttributes["class"]
  variant?: "underline" | "pill"
}>(), {
  variant: "underline",
})

const delegatedProps = reactiveOmit(props, "class", "variant")
</script>

<template>
  <TabsList
    data-slot="tabs-list"
    :data-variant="variant"
    v-bind="delegatedProps"
    :class="cn(
      'group/tabs relative flex w-full items-center text-muted-foreground',
      variant === 'underline' && 'gap-6 border-b border-border',
      variant === 'pill' && 'w-fit gap-1 rounded-full bg-muted p-1',
      props.class,
    )"
  >
    <slot />
    <TabsIndicator
      v-if="variant === 'underline'"
      class="absolute bottom-0 left-0 h-0.5 w-(--reka-tabs-indicator-size) translate-x-(--reka-tabs-indicator-position) rounded-full bg-primary transition-transform duration-(--dur-base) ease-(--ease-standard)"
    />
  </TabsList>
</template>
