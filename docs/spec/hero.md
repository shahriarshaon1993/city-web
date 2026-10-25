# Hero section (REQUIREMENT §6)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 6. Hero section requirements

The hero appears on PG-01 (always) and optionally on builder pages via the `hero` block. Component spec: `DESIGN.md` CMP-HERO-01/02; motion: MOT-REVEAL, MOT-PARALLAX.

### 6.1 Modes

| ID | Requirement |
|---|---|
| HERO-001 | A hero has `mode` = `single` or `slider`. Default `single`. |
| HERO-002 | `single` supports `media_type` = `image` or `video`. |
| HERO-003 | `slider` supports 2–5 image slides only (no video). Autoplay is off by default; when enabled the interval is 7 s, it pauses on hover, focus and when the tab is hidden, and a visible pause/play button is always rendered (WCAG 2.2.2). |
| HERO-004 | Rationale (DEC-09): a single hero gives one stable LCP element and one message; sliders split attention and only slide 1 is ever LCP. Slider is retained for campaigns. |

### 6.2 Content fields (admin)

| Field | Type | Rules |
|---|---|---|
| eyebrow | string | ≤ 40 chars, optional |
| headline | string | Required, ≤ 80 chars. Rendered as the page H1 on PG-01; as H2 when the block is not first on a page |
| subheadline | string | ≤ 160 chars |
| primary_cta | link (label ≤ 24 chars + target: page / project / location / URL) | Optional |
| secondary_cta | link | Optional |
| overlay_opacity | integer 0–80, step 5 | Default 45. Enforced minimum 35 when headline is present (keeps white text ≥ 4.5:1 on any image, see DESIGN TOK contrast table) |
| overlay_style | enum `solid`, `gradient-bottom`, `gradient-left` | Default `gradient-bottom` |
| text_align | enum `left`, `center` | Default `left` |
| show_search | boolean | Default true on PG-01. Renders CMP-SEARCH-01 |
| height | enum `full` (100svh, min 560 px), `large` (80svh), `medium` (60svh) | Default `full` on PG-01 |

### 6.3 Image background

| ID | Requirement |
|---|---|
| HERO-010 | Source image required: JPEG/PNG/WebP/AVIF, ≥ 1920×1080 px, ≤ 12 MB. Upload rejected below 1920 px width. |
| HERO-011 | Conversions generated (queued) at widths 640, 960, 1280, 1600, 1920, 2560 in AVIF (when the server's GD supports `imageavif`), WebP and JPEG. Quality: AVIF 50, WebP 72, JPEG 78. |
| HERO-012 | Rendered as `<picture>` with AVIF and WebP `<source>`s, `sizes="100vw"`, explicit `width`/`height`, `fetchpriority="high"`, `decoding="async"`, **no** `loading="lazy"`. |
| HERO-013 | Focal point (x%, y%) set by clicking the image in admin; applied as `object-position`. Separate optional mobile crop image (portrait, ≥ 1080×1350) served via `<source media="(max-width: 767px)">`. |
| HERO-014 | `alt` required (≤ 125 chars) unless marked decorative; decorative images render `alt=""`. |

### 6.4 Video background

| ID | Requirement |
|---|---|
| HERO-020 | Sources: MP4 (H.264, required) and WebM (VP9, optional). No audio track. |
| HERO-021 | Poster image is mandatory and follows HERO-010…014; it is the LCP element. |
| HERO-022 | Limits enforced on upload: MP4 ≤ 6 MB, WebM ≤ 5 MB, ≤ 1920×1080, ≤ 20 s, ≤ 30 fps. Server reads duration/size from the file header with `getID3` (pure PHP; no ffmpeg needed on shared hosting). Rejected files show the exceeded limit. |
| HERO-023 | Compression guidance shown in the upload dialog: `ffmpeg -i in.mov -an -vf "scale=1920:-2,fps=25" -c:v libx264 -profile:v high -preset slow -crf 26 -maxrate 3.5M -bufsize 7M -movflags +faststart -pix_fmt yuv420p hero.mp4` and `ffmpeg -i in.mov -an -vf "scale=1920:-2,fps=25" -c:v libvpx-vp9 -b:v 0 -crf 36 -row-mt 1 hero.webm`. (Compression happens on the editor's machine; the host has no ffmpeg.) |
| HERO-024 | Attributes: `muted autoplay loop playsinline disablepictureinpicture preload="none"`, `aria-hidden="true"` (decorative), `tabindex="-1"`. |
| HERO-025 | **Video is loaded only when all are true**: viewport width ≥ 1024 px; `prefers-reduced-motion: no-preference`; `navigator.connection?.saveData !== true`; `navigator.connection?.effectiveType` not in `slow-2g`, `2g`, `3g`; document visible. Otherwise the poster image stays. Rationale (DEC-10): Bangladesh mobile data is metered and 4G throughput varies; a 6 MB video on mobile costs users money and competes with LCP. |
| HERO-026 | Loading sequence: (1) server HTML contains the poster `<picture>` only, no `<video>`; (2) after `window.load` + `requestIdleCallback` (timeout 2000 ms) and HERO-025 checks, a `<video>` is inserted under the poster with `src` set; (3) on `canplaythrough` it fades in over 600 ms (`MOT-HERO-VIDEO`); (4) an IntersectionObserver pauses the video when < 10% visible and resumes when visible. |
| HERO-027 | A visible pause/play toggle (CMP-HERO-02 control) is rendered whenever video plays; state persists in `sessionStorage` (`hero-video=paused`). Required by WCAG 2.2.2 for motion > 5 s. |
| HERO-028 | If the video errors, the poster remains and the error is logged once per session to the client error endpoint (`/internal/client-errors`, rate-limited). |

### 6.5 LCP protection

| ID | Requirement |
|---|---|
| HERO-030 | The root Blade template emits `<link rel="preload" as="image" imagesrcset="…" imagesizes="100vw" type="image/avif" fetchpriority="high">` for the hero image (or poster) of the current page, generated server-side from the page's `lcp_image` prop. Only one preload per page. |
| HERO-031 | When a separate mobile crop exists, two preloads are emitted with `media="(max-width: 767px)"` and `media="(min-width: 768px)"`. |
| HERO-032 | No hero text animation may hide the H1 at first paint: reveal animations on hero text start from `opacity: 1` on first load (they animate `clip-path`/`transform` only, MOT-REVEAL-HERO) so LCP is not delayed by opacity. |
| HERO-033 | Hero height is reserved by CSS (`min-height` per §6.2 `height`) so CLS from the hero = 0. |
| HERO-034 | Acceptance: on a Moto G Power–class Lighthouse mobile profile, PG-01 LCP ≤ 2.5 s with a video hero configured, and the LCP element is the poster `<img>`. |

### 6.6 Embedded search widget

| ID | Requirement |
|---|---|
| HERO-040 | Fields: Segment (select), Location (combobox, areas grouped by city), Status (select), Size (select of bands from Settings, default: < 1,200 sft; 1,200–1,600; 1,600–2,000; 2,000–2,500; 2,500+), Bedrooms (1–5+). Submit button "Search projects". |
| HERO-041 | Submit performs a GET navigation to `/projects` with query parameters (FR-SRCH-002). If only one facet is chosen and it maps to a landing page (segment, status or location), navigate to that clean URL instead (e.g. Location = Bashundhara → `/locations/bashundhara-ra`). |
| HERO-042 | Works without JavaScript: it is a real `<form method="get" action="/projects">`. |
| HERO-043 | On < 768 px the widget collapses to a single "Search projects" button that opens a bottom sheet with the same fields (CMP-SEARCH-01 mobile). |
