<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { ButtonVariants } from "."
import { Loader2Icon } from "@lucide/vue"
import { Primitive } from "reka-ui"
import { useSlots } from "vue"
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

interface Props extends PrimitiveProps {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  class?: HTMLAttributes["class"]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: "button",
})

const slots = useSlots()

// CMP-BTN-01 loading: label kept (width locked), spinner replaces the leading
// icon (or overlays the label when there is none), clicks ignored.
function ignoreWhileLoading(event: MouseEvent) {
  if (props.loading) {
    event.preventDefault()
    event.stopImmediatePropagation()
  }
}
</script>

<template>
  <Primitive
    data-slot="button"
    :data-variant="variant"
    :data-size="size"
    :data-loading="loading || undefined"
    :aria-busy="loading || undefined"
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size }), loading && 'cursor-progress', props.class)"
    @click.capture="ignoreWhileLoading"
  >
    <template v-if="slots.icon">
      <Loader2Icon v-if="loading" class="animate-spin" aria-hidden="true" />
      <slot v-else name="icon" />
    </template>
    <template v-if="loading && !slots.icon">
      <span class="inline-flex items-center gap-2 opacity-0"><slot /></span>
      <Loader2Icon class="absolute inset-0 m-auto animate-spin" aria-hidden="true" />
    </template>
    <slot v-else />
  </Primitive>
</template>
