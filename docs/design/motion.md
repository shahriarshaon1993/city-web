# Motion and animation system MOT-* (DESIGN §8)

> **Visual direction:** `direction.md` is the source of truth for the public site's look (DEC-49) and wins over this file where they differ — e.g. type weights, card, button, badge and block designs. Behaviour, accessibility and motion rules here still apply.

> Verbatim from DESIGN.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 8. Motion and animation system (`MOT`)

### 8.1 Global motion tokens

| Token | Value | GSAP equivalent | Use |
|---|---|---|---|
| `--dur-instant` | 100 ms | 0.1 | Press feedback |
| `--dur-fast` | 180 ms | 0.18 | Hover, colour |
| `--dur-base` | 280 ms | 0.28 | Menus, toasts, header |
| `--dur-slow` | 480 ms | 0.48 | Modals, sheets, tab content |
| `--dur-reveal` | 900 ms | 0.9 | Scroll reveals |
| `--dur-mask` | 1200 ms | 1.2 | Image mask reveals |
| `--ease-standard` | `cubic-bezier(0.2, 0, 0, 1)` | `"power2.out"` | Default UI |
| `--ease-out-expo` | `cubic-bezier(0.16, 1, 0.3, 1)` | `"expo.out"` | Reveals, masks |
| `--ease-in-out` | `cubic-bezier(0.65, 0, 0.35, 1)` | `"power3.inOut"` | Page transition, crossfades |
| `--ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | `"back.out(1.6)"` | Magnetic release, heart toggle |
| `--stagger-tight` | 60 ms | `stagger: 0.06` | Lists of chips, links |
| `--stagger-base` | 90 ms | `stagger: 0.09` | Cards |
| `--stagger-text` | 80 ms per line | `stagger: 0.08` | Split-line headings |
| `--reveal-distance` | 32 px (24 px on M) | `y: 32` | Reveal offset |

### 8.2 Global rules

| ID | Rule |
|---|---|
| MOT-R01 | Animate only `transform`, `opacity`, `clip-path`, `filter` (opacity/blur on ≤ 2 elements at once). Never `width/height/top/left/margin`. |
| MOT-R02 | Content is visible by default in HTML/SSR/snapshot output. JS sets "from" states immediately before animating, only for elements below the initial viewport (`ScrollTrigger` `start` not yet passed). Above-the-fold elements are not hidden (REQUIREMENT SEO-PERF-012, HERO-032). |
| MOT-R03 | `prefers-reduced-motion: reduce` **or** the site "Reduce motion" toggle **or** `settings.motion.enabled=false` → motion runtime not loaded; CSS transitions limited to colour/opacity ≤ 180 ms; marquee static (wraps to grid); counters show final value; parallax/tilt/magnetic off; Lenis off (native scroll); video autoplay off. |
| MOT-R04 | Touch devices (`(hover: none) or (pointer: coarse)`): magnetic, tilt, cursor-follow effects off; parallax intensity halved; reveals shortened (`--dur-reveal` × 0.7). |
| MOT-R05 | Cleanup on Inertia navigation: each page creates its animations inside `gsap.context(() => …, root)` in `onMounted`; `onUnmounted` calls `ctx.revert()`; `router.on('navigate')` → `ScrollTrigger.refresh()` after the new page's images with explicit dimensions mount (no waiting for load). Directives (`v-reveal`, `v-magnetic`, `v-tilt`, `v-parallax`) register with the page context and remove listeners in `unmounted`. |
| MOT-R06 | Budget: ≤ 12 active ScrollTriggers per viewport; animations target ≥ 55 fps on a Moto G Power–class device (checked in M5 with Chrome performance profiling); no long tasks > 50 ms caused by motion setup (setup batched in `requestIdleCallback`). |
| MOT-R07 | Where motion conflicts with SEO, accessibility or Core Web Vitals, motion is removed for that case (e.g. hero text never starts hidden; marquee never hides duplicate content from AT incorrectly). |

### 8.3 Smooth scroll coexistence (Lenis + ScrollTrigger + anchors + modals)

- One Lenis instance created in `SiteLayout` (persistent layout) after the motion runtime loads; options `lerp: 0.1`, `wheelMultiplier: 1`, `smoothWheel: true`, `syncTouch: false` (native touch scrolling on mobile).
- Driven by GSAP's ticker: `gsap.ticker.add((t) => lenis.raf(t * 1000)); gsap.ticker.lagSmoothing(0)`; `lenis.on('scroll', ScrollTrigger.update)`.
- Anchors: a delegated click handler intercepts same-page `href="#…"` links and calls `lenis.scrollTo(target, { offset: -(headerHeight + tabBarHeight + 16), duration: 1.0 })`, then moves focus to the target (`tabindex="-1"`) and updates `history.replaceState`. On page load with a hash, scroll after mount with `immediate: true`.
- Modals, sheets, mega menu, lightbox, mobile menu: `lenis.stop()` on open, `lenis.start()` on close; scrollable inner panels carry `data-lenis-prevent`.
- Inertia navigation: new visits call `lenis.scrollTo(0, { immediate: true })` unless `preserveScroll`; back/forward restoration is left to Inertia (Lenis reads the native position; `lenis.resize()` after mount).
- Maps and horizontal carousels: `data-lenis-prevent` while interacting.
- Keyboard scrolling (Space, PageDown, arrows) remains native.

### 8.4 Effect specifications

| ID | Effect | Technique | Trigger | Duration / easing | Delay / stagger | Used by | Mobile | Reduced motion | Performance safeguards |
|---|---|---|---|---|---|---|---|---|---|
| MOT-SMOOTH | Smooth scrolling | Lenis 1.3.27 | Wheel/trackpad | lerp 0.1 | — | Whole public site (not admin) | Off for touch (`syncTouch:false`) | Off | Native scroll position kept; no transform-scrolled wrapper; stopped during modals |
| MOT-REVEAL | Scroll reveal (fade-up) | GSAP + ScrollTrigger via `v-reveal` | Element top reaches 85% of viewport (`start: "top 85%"`), once | `--dur-reveal`, `expo.out`; `y: 32 → 0`, `opacity 0 → 1` | `data-delay` 0–300 ms | Section headings, cards, text blocks, CTA bands | `y: 24`, duration × 0.7 | Visible immediately | `once: true` → trigger killed after play; batch with `ScrollTrigger.batch` for grids |
| MOT-REVEAL-HERO | Hero text reveal | GSAP SplitText (lines) + clip-path | On mount | 900 ms `expo.out`, `clip-path: inset(0 0 100% 0) → inset(0)` per line, **opacity stays 1** | lines 80 ms | Hero eyebrow, H1, sub, CTAs (CTAs fade-up 200 ms after) | Same | No animation | Runs after first paint; H1 text present in HTML; does not delay LCP (image is LCP) |
| MOT-SPLIT | Split-line heading reveal | SplitText lines + `yPercent: 100 → 0` inside overflow-hidden line wrappers | `top 80%`, once | 900 ms `expo.out` | `--stagger-text` | intro_statement, H2s marked `data-split` | Same, shorter | Plain text | `SplitText.revert()` on unmount; re-split on resize debounced 250 ms; `aria-label` kept on the original element (SplitText `aria: "auto"`) **[Polpharma]** |
| MOT-STAGGER | Stagger | `ScrollTrigger.batch` | Batch enters at 85% | Each item MOT-REVEAL | `--stagger-base` (cards), `--stagger-tight` (chips/links), max total 600 ms | Card grids, amenity grids, stats, footer links on first view, mobile menu links | Same | All visible | Batched (one trigger per grid) |
| MOT-GRAD | Animated gradient background | CSS: `--gradient-mesh` with `@property`-registered positions animated via keyframes (`background-position` on a 200% layer) — no JS | Always while in view (IntersectionObserver toggles `animation-play-state`) | 18 s linear infinite alternate | — | CTA band `gradient`, about hero accents | Same (paused when off-screen) | Static gradient | Paused off-screen; single layer; static gradient variant is the default for `brand` style |
| MOT-GLOW | Glowing button | CSS `box-shadow` (`--glow-primary/accent`) + pseudo-element conic-gradient border rotating | Hover/focus (pointer) ; idle pulse once on first view for hero CTA | Hover 280 ms; rotation 4 s linear infinite while hovered | — | `glow` button variant (hero primary CTA, CTA bands) | Static glow (no rotation) | Static shadow | Pseudo-element with `transform: rotate` only; animation removed when not hovered |
| MOT-MAGNETIC | Magnetic button | `v-magnetic`: pointermove within 1.4× button bounds → `gsap.quickTo` x/y (max 10 px), label moves 1.3× | Pointer proximity | quickTo 0.4 s `power3.out`; release `--ease-spring` 0.6 s | — | Hero CTAs, CTA band buttons, footer "Enquire" | **Disabled** on touch (MOT-R04) | Disabled | `quickTo` (no new tweens per move); listeners attached only when pointer is fine; rAF-throttled |
| MOT-TILT | 3D tilt card | `v-tilt`: pointermove → `rotateX/rotateY` max 6°, `transform-style: preserve-3d`, inner image translateZ 20 px, glare layer opacity 0–0.15 | Pointer over card | quickTo 0.3 s; reset 0.6 s `power2.out` | — | feature_cards (when tilt on), featured project cards | **Disabled** on touch | Disabled | `will-change: transform` only while hovered; perspective on parent (1000 px) |
| MOT-MASK | Image mask reveal | GSAP `clip-path` polygon: hexagon-aligned diagonal wipe `polygon(0 100%, 0 100%, 0 100%) → full rect` + inner image `scale 1.15 → 1` | `top 80%`, once | `--dur-mask`, `expo.out` | 0–150 ms | image_text, intro side image, segment tiles, about images | Duration × 0.7, simple vertical wipe | Image visible | clip-path + transform only; image has dimensions (no CLS) |
| MOT-PARALLAX | Parallax | `v-parallax`: ScrollTrigger `scrub: true`, image `yPercent: -10 → 10` inside overflow-hidden frame (image 120% height) | Scroll through element | Scrub (linear) | — | parallax_band, page_header bg, hero (≤ 8%) | Intensity halved (±5%) | Static | `scrub` with GPU transform; disabled when element height < 200 px |
| MOT-MARQUEE | Infinite marquee | CSS keyframes `translateX(0 → -50%)` on a duplicated track; speed from block setting (60/40 s) | Always while in view | Linear infinite | — | partners_marquee | Same speed, touch drag not supported | Static wrapped grid | Paused off-screen and on hover/focus-within (`animation-play-state`); duplicate track `aria-hidden` + `inert`; pause button |
| MOT-COUNTER | Animated counters | GSAP tween on a proxy object updating `textContent` with `Intl.NumberFormat('en-IN')` | `top 85%`, **once** | 1.6 s `power2.out` | `--stagger-base` | stats_counters, progress % | Same | Final value shown | Final value in HTML (SEO); width reserved with `tnum` + `min-width: {final digits}ch` (no CLS) |
| MOT-PAGE | Page transitions | Inertia router events + CSS: on `start` add `.is-navigating` to `<main>` after 150 ms (opacity 1 → 0.6, `--dur-base`) + top progress bar (Inertia `progress`, colour coral-500, delay 150 ms); on `navigate` new page main content fades/rises in (`opacity 0 → 1`, `y 12 → 0`, 240 ms `--ease-standard`) | Inertia visit | 240 ms | — | All public pages | Same | No transition (instant) | No exit animation blocking the visit; initial SSR/snapshot render never animates (class added only for client visits); scroll handled per §8.3; focus moved to main H1 container + live announcement (§11) |
| MOT-SKELETON | Skeleton loading | CSS shimmer gradient | Shown when a partial reload or lazy component exceeds 300 ms | 1.4 s linear infinite | — | Listings filter reloads, map/gallery lazy load, admin tables first load, KPI cards | Same | Static blocks | Never shown for initial server/snapshot HTML; minimum display 400 ms once shown (prevents flash) |
| MOT-TOAST | Toast animation | vue-sonner CSS | Toast added/removed | Enter 280 ms `--ease-out-expo` (y 16 → 0, opacity), exit 180 ms; stack shift 280 ms | — | All toasts | Enter from top on mobile | Opacity only | CSS transitions; max 3 visible |
| MOT-HERO-VIDEO | Hero video fade-in | CSS opacity transition on `<video>` | `canplaythrough` | 600 ms `--ease-standard` | — | CMP-HERO-02 | Not applicable (no video < 1024) | No video | Poster stays underneath; no layout change |
| MOT-MODAL | Dialog/sheet | Reka data-state + `tw-animate-css` | Open/close | Dialog: 280 ms scale 0.96 → 1 + fade; Sheet: 320 ms translate; overlay fade 200 ms | — | All dialogs/sheets | Bottom sheets on M | Fade only, 120 ms | — |
| MOT-MEGA | Mega menu | Reka NavigationMenu viewport | Open/switch | 280 ms height/opacity (viewport uses `--reka-navigation-menu-viewport-height`, transform scale-y on content) | Columns 40 ms | CMP-NAV-02 | n/a | Fade only | — |
| MOT-MENU | Mobile menu | GSAP timeline | Open | Sheet 420 ms `expo.out`; links `y 24 → 0` | `--stagger-tight` | CMP-NAV-03 | — | Instant | Timeline reversed on close (280 ms) |
| MOT-CARD-IMG | Card image zoom | CSS transform | Hover (fine pointer) | 600 ms `--ease-standard`, scale 1 → 1.04 | — | CMP-CARD-01, news cards, tiles | Off | Off | `overflow: hidden` frame |
| MOT-UNDERLINE | Link underline | CSS `background-size` 0 → 100% | Hover/focus | 280 ms | — | Nav, footer, inline links | n/a | Instant | — |
| MOT-LINE | Process line draw | SVG `stroke-dashoffset` scrubbed | Scroll through steps | Scrub | — | process_steps | Same | Full line | One trigger |
| MOT-TAB | Tab indicator | CSS transform on indicator | Tab change | 280 ms | — | CMP-TABS-01 | Same | Instant | — |
| MOT-ACC | Accordion | Reka height var | Toggle | 280 ms | — | CMP-ACC-01 | Same | Instant | — |
| MOT-CUE | Scroll cue | CSS keyframes translateY 0 → 6 px | Idle | 1.6 s ease-in-out infinite | — | Hero | Hidden < 768 | Hidden | Stops after first scroll |
| MOT-PULSE | Progress pulse | CSS opacity 1 → 0.6 | In view | 2 s ease-in-out infinite | — | CMP-PROG-01 current segment | Same | Off | Paused off-screen |
| MOT-SLIDE / MOT-SLIDE-UP | Slider crossfade / bar slide-up | Embla + CSS / CSS transform | Slide change / selection | 700 ms / 280 ms | — | CMP-HERO-03 / compare bar, bulk bar | Same | Instant | — |
| MOT-PORTRAIT | Portrait colour | CSS `filter: grayscale(1) → 0` | Hover | 480 ms | — | CMP-PERSON-01 | Colour by default on touch | Colour by default | `filter` on ≤ 1 element at a time |

### 8.5 Loading strategy

`resources/js/motion/index.ts` is a dynamic import (separate chunk ~45 KB gzip: gsap core, ScrollTrigger, SplitText, lenis). Loaded per REQUIREMENT SEO-PERF-012. Until loaded, `v-reveal` and friends are no-ops (content visible). CSS-only effects (marquee, gradient, glow, hover zooms, underline, skeleton, toast, modal) do not depend on the chunk.
