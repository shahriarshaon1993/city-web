import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

// CMP-BTN-01. Focus ring comes from the global :focus-visible rule (A11Y-D01).
// Hover only applies to enabled buttons; disabled = 50% + not-allowed.
// DEC-46: 8 px radius (owner: pills too round) and medium weight; `light` = solid white on photos.
export const buttonVariants = cva(
  "relative inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-[color,background-color,border-color,box-shadow,transform] duration-(--dur-fast) ease-(--ease-standard) active:not-disabled:translate-y-px active:not-disabled:shadow-none disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        primary: "bg-primary text-primary-foreground hover:not-disabled:bg-primary-hover",
        accent: "bg-brand-accent text-brand-accent-foreground hover:not-disabled:bg-brand-accent-hover",
        secondary: "bg-secondary text-secondary-foreground hover:not-disabled:bg-primary/15",
        outline: "border border-foreground/30 bg-transparent text-foreground hover:not-disabled:border-foreground hover:not-disabled:bg-transparent",
        ghost: "text-foreground hover:not-disabled:bg-accent hover:not-disabled:text-accent-foreground",
        link: "text-primary underline-offset-4 hover:not-disabled:underline",
        glow: "bg-primary text-primary-foreground shadow-(--glow-primary) hover:not-disabled:bg-primary-hover",
        "on-media": "bg-white/10 text-white ring-1 ring-white/35 ring-inset backdrop-blur-[8px] hover:not-disabled:bg-white/20",
        light: "bg-white text-stone-900 hover:not-disabled:bg-stone-100",
        destructive: "bg-destructive text-destructive-foreground hover:not-disabled:bg-destructive/90",
      },
      size: {
        sm: "h-9 px-3 text-sm",
        md: "h-11 px-6 text-sm",
        lg: "h-13 px-8 text-[0.9375rem]",
        icon: "size-11",
        "icon-sm": "size-9",
      },
    },
    defaultVariants: {
      variant: "primary",
      size: "md",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
