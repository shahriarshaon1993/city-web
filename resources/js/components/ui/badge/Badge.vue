<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { BadgeValue, BadgeVariant, BadgeVariants } from "."
import { Primitive } from "reka-ui"
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { badgeVariants } from "."

const props = withDefaults(defineProps<PrimitiveProps & {
  variant?: BadgeVariant
  /** Enum value for lifecycle / booking / status badges. */
  value?: BadgeValue
  size?: BadgeVariants["size"]
  onMedia?: boolean
  dot?: boolean
  class?: HTMLAttributes["class"]
}>(), {
  as: "span",
  variant: "neutral",
})

const tone = computed<BadgeVariants["tone"]>(() => {
  if (props.variant === "featured" || props.variant === "neutral") {
    return props.variant
  }

  return props.value ?? "neutral"
})
</script>

<template>
  <Primitive
    data-slot="badge"
    :data-variant="variant"
    :data-value="value"
    :as="as"
    :as-child="asChild"
    :class="cn(badgeVariants({ tone, size, onMedia }), props.class)"
  >
    <span v-if="dot" class="size-1.5 rounded-full bg-current" aria-hidden="true" />
    <slot />
  </Primitive>
</template>
