<script setup lang="ts">
import type { DateValue } from '@internationalized/date'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import {
  CalendarCell,
  CalendarCellTrigger,
  CalendarGrid,
  CalendarGridBody,
  CalendarGridHead,
  CalendarGridRow,
  CalendarHeadCell,
  CalendarHeader,
  CalendarHeading,
  CalendarNext,
  CalendarPrev,
  CalendarRoot,
} from "reka-ui"
import { buttonVariants } from "@/components/ui/button"
import { cn } from "@/lib/utils"

// CMP-FORM-01 date picker body: one month, single selection, min/max days
// greyed out. Built on Reka's Calendar (keyboard: arrows, PageUp/Down, Home/End).
defineProps<{
  minValue?: DateValue
  maxValue?: DateValue
}>()

const modelValue = defineModel<DateValue | undefined>({ default: undefined })
const placeholder = defineModel<DateValue | undefined>("placeholder", { default: undefined })

const navClass = cn(
  buttonVariants({ variant: "outline", size: "icon-sm" }),
  "border-border data-[disabled]:pointer-events-none data-[disabled]:opacity-40",
)
</script>

<template>
  <CalendarRoot
    v-slot="{ grid, weekDays }"
    v-model="modelValue"
    v-model:placeholder="placeholder"
    :min-value="minValue"
    :max-value="maxValue"
    :week-starts-on="6"
    fixed-weeks
    prevent-deselect
    locale="en-GB"
    class="w-fit"
  >
    <CalendarHeader class="flex items-center justify-between gap-2 pb-3">
      <CalendarPrev :class="navClass" aria-label="Previous month">
        <ChevronLeft class="size-4" aria-hidden="true" />
      </CalendarPrev>
      <CalendarHeading class="text-sm font-medium" />
      <CalendarNext :class="navClass" aria-label="Next month">
        <ChevronRight class="size-4" aria-hidden="true" />
      </CalendarNext>
    </CalendarHeader>

    <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse">
      <CalendarGridHead>
        <CalendarGridRow class="grid grid-cols-7">
          <CalendarHeadCell
            v-for="day in weekDays"
            :key="day"
            class="text-overline h-9 text-center leading-9 text-muted-foreground"
          >
            {{ day }}
          </CalendarHeadCell>
        </CalendarGridRow>
      </CalendarGridHead>
      <CalendarGridBody>
        <CalendarGridRow
          v-for="(weekDates, index) in month.rows"
          :key="`week-${index}`"
          class="grid grid-cols-7"
        >
          <CalendarCell
            v-for="weekDate in weekDates"
            :key="weekDate.toString()"
            :date="weekDate"
            class="p-0.5 text-center"
          >
            <CalendarCellTrigger
              :day="weekDate"
              :month="month.value"
              class="tabular flex size-10 cursor-pointer items-center justify-center rounded-md text-sm transition-colors duration-(--dur-fast) hover:bg-muted data-[disabled]:pointer-events-none data-[disabled]:text-muted-foreground/40 data-[outside-view]:pointer-events-none data-[outside-view]:opacity-0 data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[today]:font-medium data-[today]:underline data-[today]:underline-offset-4 data-[unavailable]:pointer-events-none data-[unavailable]:line-through"
            />
          </CalendarCell>
        </CalendarGridRow>
      </CalendarGridBody>
    </CalendarGrid>
  </CalendarRoot>
</template>
