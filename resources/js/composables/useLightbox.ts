import type PhotoSwipe from 'photoswipe';
import type { SlideData } from 'photoswipe';
import { ref } from 'vue';
import { usePauseSmoothScroll } from '@/motion/bridge';
import { isMotionReduced } from '@/motion/preference';
import type { MediaImage } from '@/types/site';

// CMP-GAL-01 lightbox: PhotoSwipe 5, loaded on the first open only (it never
// weighs on the initial route, SEO-PERF-004). Full-screen stone-950/96 %,
// zoom (pinch, double-tap, wheel), ←/→/Esc, ±1 preload, focus trapped and
// returned to the opener. Optional category jump buttons in the top bar
// (Exterior, Interior…) and a caption + credit line at the bottom.

export type LightboxSlide = {
    image: MediaImage;
    category?: { value: string; label: string };
};

type PhotoSwipeModule = typeof import('photoswipe');

let loader: Promise<PhotoSwipeModule['default']> | null = null;

function loadPhotoSwipe(): Promise<PhotoSwipeModule['default']> {
    loader ??= Promise.all([
        import('photoswipe'),
        import('photoswipe/style.css'),
    ]).then(([module]) => module.default);

    return loader;
}

function slideData(slide: LightboxSlide): SlideData {
    const { image } = slide;
    // WebP is universal; an <img> srcset cannot fall back from AVIF.
    const srcset = image.sources.webp || undefined;

    return {
        src: image.fallback,
        srcset,
        width: image.width,
        height: image.height,
        alt: image.alt,
    };
}

function captionText(slide: LightboxSlide | undefined): string {
    if (!slide) {
        return '';
    }

    return [slide.image.caption, slide.image.credit]
        .filter((part): part is string => !!part)
        .join(' · ');
}

/** Category jump buttons: the active one follows the current slide. */
function registerCategories(pswp: PhotoSwipe, slides: LightboxSlide[]): void {
    const firsts = new Map<
        string,
        { label: string; index: number; count: number }
    >();

    slides.forEach((slide, index) => {
        if (!slide.category) {
            return;
        }

        const entry = firsts.get(slide.category.value);

        if (entry) {
            entry.count++;
        } else {
            firsts.set(slide.category.value, {
                label: slide.category.label,
                index,
                count: 1,
            });
        }
    });

    if (firsts.size < 2) {
        return;
    }

    pswp.ui?.registerElement({
        name: 'categories',
        order: 6,
        isButton: false,
        appendTo: 'bar',
        onInit: (element) => {
            element.setAttribute('role', 'group');
            element.setAttribute('aria-label', 'Photo categories');
            const buttons = [...firsts].map(([value, entry]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'pswp__category';
                button.dataset.category = value;
                const count = document.createElement('span');
                count.textContent = String(entry.count);
                count.setAttribute('aria-hidden', 'true');
                button.append(entry.label, count);
                button.setAttribute(
                    'aria-label',
                    `${entry.label}, ${entry.count} photos`,
                );
                button.addEventListener('click', () => pswp.goTo(entry.index));
                element.append(button);

                return button;
            });

            const update = (): void => {
                const current = slides[pswp.currIndex]?.category?.value;

                for (const button of buttons) {
                    const active = button.dataset.category === current;
                    button.toggleAttribute('data-active', active);

                    if (active) {
                        button.setAttribute('aria-current', 'true');
                    } else {
                        button.removeAttribute('aria-current');
                    }
                }
            };

            pswp.on('change', update);
            update();
        },
    });
}

function registerCaption(pswp: PhotoSwipe, slides: LightboxSlide[]): void {
    pswp.ui?.registerElement({
        name: 'caption',
        order: 9,
        isButton: false,
        appendTo: 'root',
        onInit: (element) => {
            element.setAttribute('aria-live', 'polite');
            const update = (): void => {
                const text = captionText(slides[pswp.currIndex]);
                element.textContent = text;
                element.hidden = text === '';
            };

            pswp.on('change', update);
            update();
        },
    });
}

/**
 * Call in setup; `open()` from a click handler only (it touches the DOM).
 * Lenis is paused while the lightbox is open (design/motion.md §8.3).
 */
export function useLightbox() {
    const isOpen = ref(false);
    usePauseSmoothScroll(() => isOpen.value);

    async function open(
        slides: LightboxSlide[],
        index: number,
        label: string,
    ): Promise<void> {
        if (slides.length === 0 || isOpen.value) {
            return;
        }

        isOpen.value = true;

        let PhotoSwipeClass: PhotoSwipeModule['default'];

        try {
            PhotoSwipeClass = await loadPhotoSwipe();
        } catch (error) {
            isOpen.value = false;
            loader = null;
            console.error('Lightbox failed to load', error);

            return;
        }

        const reduced = isMotionReduced();
        const pswp = new PhotoSwipeClass({
            dataSource: slides.map(slideData),
            index,
            bgOpacity: 0.96,
            preload: [1, 1],
            showHideAnimationType: reduced ? 'none' : 'fade',
            showAnimationDuration: reduced ? 0 : 240,
            hideAnimationDuration: reduced ? 0 : 200,
            zoomAnimationDuration: reduced ? 0 : 333,
            mainClass: 'pswp--site',
            // Room for the top bar and the caption line.
            padding: { top: 64, bottom: 72, left: 0, right: 0 },
            indexIndicatorSep: ' / ',
            closeTitle: 'Close (Esc)',
            zoomTitle: 'Zoom',
            arrowPrevTitle: 'Previous photo',
            arrowNextTitle: 'Next photo',
            errorMsg: 'This photo could not be loaded.',
            returnFocus: true,
            trapFocus: true,
        });

        pswp.on('uiRegister', () => {
            registerCategories(pswp, slides);
            registerCaption(pswp, slides);
        });
        pswp.on('afterInit', () => {
            pswp.element?.setAttribute('aria-label', label);
        });
        pswp.on('destroy', () => {
            isOpen.value = false;
        });
        pswp.init();
    }

    return { open, isOpen };
}
