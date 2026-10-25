<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon, XIcon } from "@lucide/vue"
import { useMediaQuery } from "@vueuse/core"
import { computed } from "vue"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
  visibleToasts: 3,
  gap: 8,
  offset: 24,
  mobileOffset: () => ({ top: 72, left: 16, right: 16 }),
  duration: 4000,
  closeButton: true,
})

// CMP-TOAST-01: bottom-right (24 px inset) on desktop, top-center below the
// header on mobile; max 3 visible, 8 px stack gap. useMediaQuery is false on
// the server, so SSR renders the desktop position.
const isMobile = useMediaQuery("(max-width: 639px)")
const position = computed(() => props.position ?? (isMobile.value ? "top-center" : "bottom-right"))
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',
      '--border-radius': 'var(--radius-lg)',
      'zIndex': 'var(--z-toast)',
    }"
    v-bind="props"
    :position="position"
    :toast-options="{
      classes: {
        toast: 'font-sans shadow-lg',
        title: 'text-sm font-semibold',
        description: 'text-sm text-muted-foreground',
        success: '[&_[data-icon]]:text-success',
        info: '[&_[data-icon]]:text-info',
        warning: '[&_[data-icon]]:text-warning',
        error: '[&_[data-icon]]:text-destructive',
      },
    }"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
