import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export type LifecycleValue = "upcoming" | "ongoing" | "ready" | "handed_over"
export type BookingValue = "booking_open" | "limited_units" | "sold_out"
export type PublishStatusValue = "draft" | "scheduled" | "published" | "archived" | "pending_revision"
export type BadgeVariant = "lifecycle" | "booking" | "status" | "featured" | "neutral"
export type BadgeValue = LifecycleValue | BookingValue | PublishStatusValue

// CMP-BADGE-01: -50 background + -700/-800 text (design §2.6). Text is always
// present, colour is never the only signal.
export const badgeVariants = cva(
  "inline-flex w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-full font-medium [&>svg]:pointer-events-none [&>svg]:size-3.5",
  {
    variants: {
      tone: {
        upcoming: "bg-info-50 text-info-700",
        ongoing: "bg-teal-50 text-teal-700",
        ready: "bg-success-50 text-success-700",
        handed_over: "bg-stone-100 text-stone-700",
        booking_open: "bg-coral-50 text-coral-800",
        limited_units: "bg-warning-50 text-warning-700",
        sold_out: "bg-stone-200 text-stone-800",
        draft: "bg-stone-100 text-stone-700",
        scheduled: "bg-info-50 text-info-700",
        published: "bg-success-50 text-success-700",
        archived: "bg-stone-200 text-stone-600",
        pending_revision: "bg-warning-50 text-warning-700",
        featured: "bg-coral-600 text-white",
        neutral: "bg-stone-100 text-stone-700",
      },
      size: {
        sm: "h-[22px] px-2 text-xs",
        md: "h-[26px] px-2.5 text-sm",
      },
      onMedia: {
        true: "",
        false: "",
      },
    },
    compoundVariants: [
      // On photos: white/90% background, text colour kept.
      { onMedia: true, tone: ["upcoming", "ongoing", "ready", "handed_over", "booking_open", "limited_units", "sold_out", "neutral"], class: "bg-white/90 shadow-xs" },
    ],
    defaultVariants: {
      tone: "neutral",
      size: "sm",
      onMedia: false,
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
