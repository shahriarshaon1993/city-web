<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { fieldControlClass, fieldReadOnlyClass } from "@/components/ui/input"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  defaultValue?: string | number
  modelValue?: string | number
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <!-- CMP-FORM-01: min 4 rows, grows with content up to 12 rows -->
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    rows="4"
    :class="cn(fieldControlClass, fieldReadOnlyClass, 'flex field-sizing-content min-h-[calc(4lh+1.25rem)] max-h-[calc(12lh+1.25rem)] resize-y py-2.5 leading-normal', props.class)"
  />
</template>
