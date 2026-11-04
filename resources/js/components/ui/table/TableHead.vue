<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <th
    data-slot="table-head"
    :class="cn('h-11 border-b bg-card px-3 text-left align-middle text-xs font-semibold tracking-[0.06em] whitespace-nowrap text-muted-foreground uppercase', props.class)"
  >
    <slot />
  </th>
</template>
