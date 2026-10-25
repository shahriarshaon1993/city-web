<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

interface SkeletonProps {
  class?: HTMLAttributes["class"]
}

const props = defineProps<SkeletonProps>()
</script>

<template>
  <!-- design §5.4: --muted block, radius of the element it stands for, MOT-SKELETON shimmer -->
  <div
    data-slot="skeleton"
    aria-hidden="true"
    :class="cn('skeleton-shimmer rounded-md bg-muted', props.class)"
  />
</template>
