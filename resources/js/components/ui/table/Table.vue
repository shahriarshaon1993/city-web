<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <table
    data-slot="table"
    :class="cn('w-full caption-bottom border-separate border-spacing-0 text-sm', props.class)"
  >
    <slot />
  </table>
</template>
