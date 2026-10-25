<script setup lang="ts">
import type { AccordionTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import { cn } from "@/lib/utils"
import {
  AccordionHeader,
  AccordionTrigger,
} from "reka-ui"

const props = defineProps<AccordionTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <AccordionHeader class="flex">
    <AccordionTrigger
      data-slot="accordion-trigger"
      v-bind="delegatedProps"
      :class="
        cn(
          'flex flex-1 items-start justify-between gap-4 rounded-xs py-5 text-left font-display text-h4 transition-colors duration-(--dur-fast) hover:not-disabled:text-primary disabled:cursor-not-allowed disabled:opacity-50 [&[data-state=open]>svg]:rotate-180',
          props.class,
        )
      "
    >
      <slot />
      <slot name="icon">
        <ChevronDown
          class="pointer-events-none mt-1 size-5 shrink-0 text-muted-foreground transition-transform duration-(--dur-base) ease-(--ease-standard)"
        />
      </slot>
    </AccordionTrigger>
  </AccordionHeader>
</template>
