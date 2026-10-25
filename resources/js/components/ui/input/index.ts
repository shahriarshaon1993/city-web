export { default as Input } from "./Input.vue"

// CMP-FORM-01 text-control states, shared by Input, Textarea and SelectTrigger:
// border --input, hover stone-600 (muted-foreground), focus border + 3 px ring/25%,
// invalid destructive, disabled muted bg + 60% text, read-only borderless muted.
export const fieldControlClass =
  "w-full min-w-0 rounded-sm border border-input bg-background px-3.5 text-base text-foreground shadow-xs outline-none transition-[color,background-color,border-color,box-shadow] duration-(--dur-fast) placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground hover:not-disabled:border-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/25 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:bg-muted disabled:text-foreground/60"

// :read-only also matches buttons, so only real text fields get the read-only look.
export const fieldReadOnlyClass = "read-only:border-transparent read-only:bg-muted read-only:shadow-none"
