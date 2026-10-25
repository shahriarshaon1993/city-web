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

// The dot keeps the status colour when the on-media chip text is neutral (DEC-46).
const dotTones: Partial<Record<NonNullable<BadgeVariants["tone"]>, string>> = {
  upcoming: "bg-info-700",
  ongoing: "bg-teal-600",
  ready: "bg-success-700",
  handed_over: "bg-stone-500",
  booking_open: "bg-coral-600",
  limited_units: "bg-warning-700",
  sold_out: "bg-stone-500",
}
const dotClass = computed(() => (props.onMedia && tone.value ? dotTones[tone.value] : undefined) ?? "bg-current")
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
    <span v-if="dot" :class="cn('size-1.5 rounded-full', dotClass)" aria-hidden="true" />
    <slot />
  </Primitive>
</template>
