import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Ref } from 'vue';
import { store as clientErrorsStore } from '@/routes/client-errors';
import type { MediaVideo } from '@/types/site';

/** `navigator.connection` (Network Information API; not in lib.dom). */
type NetworkInformation = { saveData?: boolean; effectiveType?: string };

const PAUSE_KEY = 'hero-video';
const ERROR_LOGGED_KEY = 'hero-video-error-logged';
const SLOW_CONNECTIONS = ['slow-2g', '2g', '3g'];

function readSession(key: string): string | null {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function writeSession(key: string, value: string | null): void {
    try {
        if (value === null) {
            window.sessionStorage.removeItem(key);
        } else {
            window.sessionStorage.setItem(key, value);
        }
    } catch {
        // Storage blocked: the state just lasts for this page view.
    }
}

/** HERO-028: once per session, through the rate-limited endpoint. */
function logVideoError(src: string): void {
    if (readSession(ERROR_LOGGED_KEY)) {
        return;
    }

    writeSession(ERROR_LOGGED_KEY, '1');
    const body = JSON.stringify({
        source: 'hero-video',
        message: `Hero video failed to load: ${src}`,
        url: window.location.href,
    });
    navigator.sendBeacon?.(
        clientErrorsStore.url(),
        new Blob([body], { type: 'application/json' }),
    );
}

/**
 * CMP-HERO-02. The server HTML holds only the poster; the `<video>` is
 * inserted after `load` + idle when every HERO-025 condition holds, fades in
 * on `canplaythrough` (MOT-HERO-VIDEO), pauses below 10 % visibility and
 * remembers a user pause for the session (HERO-026/027). On error the
 * poster stays and the failure is reported once (HERO-028).
 */
export function useHeroVideo(options: {
    video: () => MediaVideo | null;
    root: Readonly<Ref<HTMLElement | null>>;
    element: Readonly<Ref<HTMLVideoElement | null>>;
    /** Settings → Motion: animations (MOT-R03) and hero video on mobile. */
    animations: () => boolean;
    allowMobile: () => boolean;
}) {
    /** The `<video>` is in the DOM. */
    const inserted = ref(false);
    /** `canplaythrough` fired: fade the video in. */
    const ready = ref(false);
    const paused = ref(false);
    const src = ref<string | null>(null);

    let observer: IntersectionObserver | undefined;
    let visible = true;
    let cancelIdle: (() => void) | undefined;
    let cancelled = false;

    // HERO-025: all must hold, otherwise the poster stays.
    function allowed(): boolean {
        const connection = (
            navigator as Navigator & { connection?: NetworkInformation }
        ).connection;

        return (
            options.animations() &&
            window.matchMedia('(prefers-reduced-motion: no-preference)')
                .matches &&
            (options.allowMobile() ||
                window.matchMedia('(min-width: 1024px)').matches) &&
            connection?.saveData !== true &&
            !SLOW_CONNECTIONS.includes(connection?.effectiveType ?? '')
        );
    }

    function pickSource(video: MediaVideo): string {
        const probe = document.createElement('video');

        return video.webm && probe.canPlayType('video/webm; codecs="vp9"')
            ? video.webm
            : video.mp4;
    }

    function insert(): void {
        const video = options.video();

        if (cancelled || !video || !allowed()) {
            return;
        }

        if (document.visibilityState !== 'visible') {
            document.addEventListener('visibilitychange', insert, {
                once: true,
            });

            return;
        }

        paused.value = readSession(PAUSE_KEY) === 'paused';
        src.value = pickSource(video);
        inserted.value = true;
    }

    function scheduleInsert(): void {
        // Safari < 18 has no requestIdleCallback.
        if (typeof window.requestIdleCallback === 'function') {
            const handle = window.requestIdleCallback(insert, {
                timeout: 2000,
            });
            cancelIdle = () => window.cancelIdleCallback(handle);
        } else {
            const handle = setTimeout(insert, 200);
            cancelIdle = () => clearTimeout(handle);
        }
    }

    function play(): void {
        void options.element.value?.play().catch(() => {
            // Autoplay refused (e.g. power saving): the poster stays.
        });
    }

    function toggle(): void {
        paused.value = !paused.value;
        writeSession(PAUSE_KEY, paused.value ? 'paused' : null);

        if (paused.value) {
            options.element.value?.pause();
        } else if (visible) {
            play();
        }
    }

    function onCanPlayThrough(): void {
        ready.value = true;
    }

    function onError(): void {
        if (src.value) {
            logVideoError(src.value);
        }

        inserted.value = false;
        ready.value = false;
        src.value = null;
    }

    // HERO-026 (4): pause when less than 10 % is visible.
    watch(
        () => options.element.value,
        (element) => {
            observer?.disconnect();

            if (!element || !options.root.value) {
                return;
            }

            observer = new IntersectionObserver(
                ([entry]) => {
                    visible = entry.intersectionRatio >= 0.1;

                    if (!visible) {
                        element.pause();
                    } else if (!paused.value) {
                        play();
                    }
                },
                { threshold: [0, 0.1] },
            );
            observer.observe(options.root.value);
        },
        { flush: 'post' },
    );

    onMounted(() => {
        if (!options.video()) {
            return;
        }

        if (document.readyState === 'complete') {
            scheduleInsert();
        } else {
            window.addEventListener('load', scheduleInsert, { once: true });
        }
    });

    onBeforeUnmount(() => {
        cancelled = true;
        observer?.disconnect();
        window.removeEventListener('load', scheduleInsert);
        document.removeEventListener('visibilitychange', insert);
        cancelIdle?.();
    });

    return { inserted, ready, paused, src, toggle, onCanPlayThrough, onError };
}
