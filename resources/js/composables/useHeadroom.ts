import type { Ref } from 'vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const SOLID_AFTER = 24;
const HIDE_AFTER = 120;
const SHOW_DELTA = 8;

/**
 * CMP-HEAD-01 "headroom": `scrolled` after 24 px; `hidden` while scrolling
 * down past 120 px, shown again after an 8 px scroll up. `pinned` (focus or
 * an open menu inside the header) keeps it shown. SSR state: top of page.
 */
export function useHeadroom(options: {
    enabled: Readonly<Ref<boolean>>;
    pinned: Readonly<Ref<boolean>>;
}) {
    const scrolled = ref(false);
    const hidden = ref(false);
    let anchorY = 0;
    let frame = 0;

    function update(): void {
        frame = 0;
        const y = window.scrollY;
        scrolled.value = y > SOLID_AFTER;

        if (!options.enabled.value || options.pinned.value || y <= HIDE_AFTER) {
            hidden.value = false;
            anchorY = y;

            return;
        }

        if (y > anchorY) {
            hidden.value = true;
            anchorY = y;
        } else if (anchorY - y >= SHOW_DELTA) {
            hidden.value = false;
            anchorY = y;
        }
    }

    function onScroll(): void {
        if (frame === 0) {
            frame = requestAnimationFrame(update);
        }
    }

    onMounted(() => {
        window.addEventListener('scroll', onScroll, { passive: true });
        update();
    });

    onBeforeUnmount(() => {
        window.removeEventListener('scroll', onScroll);
        cancelAnimationFrame(frame);
    });

    return { scrolled, hidden, update };
}
