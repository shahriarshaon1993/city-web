<script setup lang="ts">
import type { LabelProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { Label } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<LabelProps & {
  class?: HTMLAttributes["class"]
  /** CMP-FORM-01: visible "*" (hidden from AT) plus visually hidden "(required)". */
  required?: boolean
}>()

const delegatedProps = reactiveOmit(props, "class", "required")
</script>

<template>
  <Label
    data-slot="label"
    v-bind="delegatedProps"
    :class="
      cn(
        'flex items-center gap-1 text-sm leading-snug font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
        props.class,
      )
    "
  >
    <slot />
    <template v-if="required">
      <span class="text-destructive" aria-hidden="true">*</span>
      <span class="sr-only">(required)</span>
    </template>
  </Label>
</template>
