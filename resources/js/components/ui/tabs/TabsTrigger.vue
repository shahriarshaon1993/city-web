<script setup lang="ts">
import type { TabsTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { cn } from "@/lib/utils"
import { TabsTrigger, useForwardProps } from "reka-ui"

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TabsTrigger
    data-slot="tabs-trigger"
    :class="cn(
      `inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap text-sm font-semibold transition-[color,background-color,box-shadow] duration-(--dur-fast) hover:not-disabled:text-foreground disabled:cursor-not-allowed disabled:opacity-50 data-[state=active]:text-foreground [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 group-data-[variant=underline]/tabs:h-11 group-data-[variant=underline]/tabs:px-1 group-data-[variant=pill]/tabs:h-11 group-data-[variant=pill]/tabs:rounded-full group-data-[variant=pill]/tabs:px-4 group-data-[variant=pill]/tabs:data-[state=active]:bg-background group-data-[variant=pill]/tabs:data-[state=active]:shadow-sm`,
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </TabsTrigger>
</template>
