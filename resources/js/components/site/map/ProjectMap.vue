<script setup lang="ts">
import { LocateFixed, MapPin, Minus, Plus } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import type * as Leaflet from 'leaflet';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
} from 'vue';
import MapProjectCard from '@/components/site/map/MapProjectCard.vue';
import {
    DEFAULT_CENTRE,
    MARKER_SVG,
    OSM_ATTRIBUTION,
    OSM_TILES,
    loadLeaflet,
} from '@/lib/leaflet';
import type { LeafletModule } from '@/lib/leaflet';
import { cn } from '@/lib/utils';
import { isMotionReduced } from '@/motion/preference';
import type { LatLng, MapProject } from '@/types/location';

// CMP-MAP-01 / FR-LOC-006: Leaflet + OSM tiles, hexagon markers (active
// coral), teal count clusters, mini-card popups. Nothing loads until the
// map enters the viewport (or `eager`: map view, sheet), so no tile is
// requested before it is visible. A visually hidden list of the projects
// precedes the map; markers are focusable buttons with labels (Enter opens
// the popup and moves focus into it, Esc closes it and returns focus).
// Wheel zoom only after the map is clicked or focused; while engaged the
// box carries `data-lenis-prevent` (motion.md §8.3), leaving it disengages.
// Controls: zoom ± and Reset view, 44 px.
const props = withDefaults(
    defineProps<{
        projects: MapProject[];
        /** Accessible name, e.g. "Map of projects in Uttara". */
        label: string;
        /** Initial view; without it the map fits its markers. */
        centre?: LatLng | null;
        zoom?: number | null;
        /** Load at once instead of on entering the viewport. */
        eager?: boolean;
        /** Marker click opens a popup; off where a card strip shows the project instead. */
        popups?: boolean;
        class?: string;
    }>(),
    {
        centre: null,
        zoom: null,
        eager: false,
        popups: true,
        class: undefined,
    },
);

const active = defineModel<number | null>('active', { default: null });

const root = useTemplateRef<HTMLElement>('root');
const canvas = useTemplateRef<HTMLElement>('canvas');
const listId = useId();

const status = ref<'idle' | 'loading' | 'ready' | 'error'>('idle');
const engaged = ref(false);
/** Leaflet moves this element into its popup; Vue renders the card into it. */
const popupTarget = shallowRef<HTMLElement | null>(null);
const popupId = ref<number | null>(null);
const popupProject = computed(
    () =>
        props.projects.find((project) => project.id === popupId.value) ?? null,
);

let L: LeafletModule | null = null;
let map: Leaflet.Map | null = null;
let cluster: Leaflet.MarkerClusterGroup | null = null;
let popup: Leaflet.Popup | null = null;
let unmounted = false;
const markers = new Map<number, Leaflet.Marker>();

function markerLabel(project: MapProject): string {
    return `${project.name}, ${project.location.area}`;
}

function syncMarker(id: number): void {
    const marker = markers.get(id);
    const isActive = id === active.value;

    marker?.getElement()?.classList.toggle('is-active', isActive);
    marker?.setZIndexOffset(isActive ? 1000 : 0);
}

function labelClusters(): void {
    root.value
        ?.querySelectorAll<HTMLElement>('.map-cluster[data-count]')
        .forEach((element) => {
            element.setAttribute(
                'aria-label',
                `${element.dataset.count} projects, zoom in`,
            );
        });
}

function renderMarkers(): void {
    if (!L || !cluster) {
        return;
    }

    const leaflet = L;
    cluster.clearLayers();
    markers.clear();

    for (const project of props.projects) {
        const marker = leaflet.marker(
            [project.coordinates.lat, project.coordinates.lng],
            {
                icon: leaflet.divIcon({
                    className: 'map-marker',
                    html: MARKER_SVG,
                    iconSize: [32, 36],
                    iconAnchor: [16, 18],
                    popupAnchor: [0, -16],
                }),
                keyboard: true,
                riseOnHover: true,
            },
        );

        // Clustered markers are re-created when they leave a cluster.
        marker.on('add', () => {
            marker
                .getElement()
                ?.setAttribute('aria-label', markerLabel(project));
            syncMarker(project.id);
        });
        marker.on('click', (event: Leaflet.LeafletMouseEvent) => {
            select(project, event.originalEvent?.type === 'keypress');
        });
        markers.set(project.id, marker);
    }

    cluster.addLayers([...markers.values()]);
}

function resetView(animate = true): void {
    if (!L || !map) {
        return;
    }

    if (props.centre && props.zoom) {
        map.setView([props.centre.lat, props.centre.lng], props.zoom, {
            animate,
        });
    } else if (props.projects.length > 0) {
        map.fitBounds(
            L.latLngBounds(
                props.projects.map((project) => [
                    project.coordinates.lat,
                    project.coordinates.lng,
                ]),
            ),
            { padding: [48, 48], maxZoom: 15, animate },
        );
    } else {
        map.setView([DEFAULT_CENTRE.lat, DEFAULT_CENTRE.lng], 11, { animate });
    }
}

async function openPopup(project: MapProject, focus: boolean): Promise<void> {
    const marker = markers.get(project.id);

    if (!map || !popup || !marker) {
        return;
    }

    popupId.value = project.id;
    await nextTick();
    popup.setLatLng(marker.getLatLng()).openOn(map);

    if (focus) {
        popupTarget.value?.querySelector<HTMLElement>('a')?.focus();
    }
}

function select(project: MapProject, fromKeyboard: boolean): void {
    active.value = project.id;

    if (props.popups) {
        void openPopup(project, fromKeyboard);
    }
}

/** Shows the active marker (from outside, e.g. the card strip). */
function reveal(id: number): void {
    const marker = markers.get(id);

    if (!map || !cluster || !marker) {
        return;
    }

    const visible = cluster.getVisibleParent(marker) === marker;

    if (visible) {
        map.panTo(marker.getLatLng(), { animate: !isMotionReduced() });
    } else {
        cluster.zoomToShowLayer(marker);
    }
}

function engage(): void {
    if (map && !engaged.value) {
        engaged.value = true;
        map.scrollWheelZoom.enable();
    }
}

function disengage(): void {
    if (map && engaged.value) {
        engaged.value = false;
        map.scrollWheelZoom.disable();
    }
}

function onFocusOut(event: FocusEvent): void {
    if (!root.value?.contains(event.relatedTarget as Node | null)) {
        disengage();
    }
}

async function load(): Promise<void> {
    if (status.value !== 'idle' || !canvas.value) {
        return;
    }

    status.value = 'loading';

    try {
        L = await loadLeaflet();
    } catch {
        status.value = 'error';

        return;
    }

    if (unmounted || !canvas.value) {
        return;
    }

    const animate = !isMotionReduced();

    map = L.map(canvas.value, {
        zoomControl: false,
        scrollWheelZoom: false,
        zoomAnimation: animate,
        fadeAnimation: animate,
        markerZoomAnimation: animate,
        minZoom: 10,
        maxZoom: 18,
    });
    map.attributionControl.setPrefix(
        '<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>',
    );
    L.tileLayer(OSM_TILES, {
        attribution: OSM_ATTRIBUTION,
        maxZoom: 19,
    }).addTo(map);

    const leaflet = L;
    cluster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 48,
        animate,
        iconCreateFunction: (group) => {
            const count = group.getChildCount();
            const size = count < 10 ? 40 : 48;

            return leaflet.divIcon({
                className: '',
                html: `<span class="map-cluster" style="width:${size}px;height:${size}px" data-count="${count}">${count}</span>`,
                iconSize: [size, size],
            });
        },
    });
    map.addLayer(cluster);
    cluster.on('animationend', labelClusters);
    map.on('zoomend moveend', labelClusters);

    popupTarget.value = document.createElement('div');
    popup = L.popup({
        className: 'map-popup',
        maxWidth: 320,
        autoPanPadding: [24, 24],
        closeOnEscapeKey: true,
    }).setContent(popupTarget.value);

    map.on('popupclose', () => {
        const returnTo = popupId.value;
        const focusInside = popupTarget.value?.contains(document.activeElement);
        popupId.value = null;

        if (focusInside && returnTo !== null) {
            (
                markers.get(returnTo)?.getElement() as HTMLElement | undefined
            )?.focus();
        }
    });
    map.on('click', engage);

    renderMarkers();
    resetView(false);
    labelClusters();
    status.value = 'ready';
}

const { stop } = useIntersectionObserver(root, (entries) => {
    if (!props.eager && entries.some((entry) => entry.isIntersecting)) {
        stop();
        void load();
    }
});

onMounted(() => {
    if (props.eager) {
        stop();
        void load();
    }
});

watch(
    () => props.projects,
    () => {
        map?.closePopup();
        renderMarkers();
        resetView();
    },
);

watch(active, (id, previous) => {
    if (previous !== null && previous !== undefined) {
        syncMarker(previous);
    }

    if (id !== null) {
        syncMarker(id);

        if (!props.popups) {
            reveal(id);
        }
    }
});

function zoomBy(delta: number): void {
    if (delta > 0) {
        map?.zoomIn();
    } else {
        map?.zoomOut();
    }
}

onBeforeUnmount(() => {
    unmounted = true;
    map?.remove();
    map = null;
    markers.clear();
});

const controlClass =
    'flex size-11 items-center justify-center text-foreground transition-colors duration-(--dur-fast) hover:bg-muted hover:text-primary disabled:opacity-40';
</script>

<template>
    <div
        ref="root"
        :class="cn('project-map relative isolate', props.class)"
        :data-lenis-prevent="engaged ? '' : undefined"
        @focusin="engage"
        @focusout="onFocusOut"
        @mouseleave="disengage"
    >
        <div
            class="sr-only focus-within:not-sr-only focus-within:absolute focus-within:top-3 focus-within:left-3 focus-within:z-(--z-raised) focus-within:max-h-[calc(100%-1.5rem)] focus-within:overflow-auto focus-within:rounded-md focus-within:bg-background focus-within:p-4 focus-within:shadow-md"
        >
            <p
                :id="listId"
                class="text-overline text-muted-foreground uppercase"
            >
                Projects on this map
            </p>
            <ul :aria-labelledby="listId" class="mt-2 flex flex-col gap-1">
                <li v-for="project in projects" :key="project.id">
                    <a
                        :href="project.url"
                        class="inline-flex min-h-11 items-center text-sm hover:text-primary"
                    >
                        {{ project.name }}, {{ project.location.area }}
                    </a>
                </li>
            </ul>
        </div>

        <div
            role="region"
            :aria-label="label"
            class="relative size-full overflow-hidden rounded-[inherit] bg-surface-subtle"
        >
            <!-- `isolate` keeps Leaflet's pane z-indexes below the controls. -->
            <div ref="canvas" class="absolute inset-0 isolate" />

            <!-- Facade until Leaflet is ready (CMP-MAP-02 pattern). -->
            <div
                v-if="status !== 'ready'"
                class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-surface-subtle text-center"
            >
                <svg
                    class="absolute inset-0 size-full text-primary"
                    aria-hidden="true"
                >
                    <defs>
                        <pattern
                            :id="`${listId}-grid`"
                            width="40"
                            height="69.28"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M0 0H40M0 34.64H40M0 69.28L40 0M0 0L40 69.28"
                                fill="none"
                                stroke="currentColor"
                                stroke-opacity="0.1"
                                stroke-width="1"
                            />
                        </pattern>
                    </defs>
                    <rect
                        width="100%"
                        height="100%"
                        :fill="`url(#${listId}-grid)`"
                    />
                </svg>
                <MapPin
                    :class="
                        cn(
                            'relative size-10 fill-background text-primary',
                            status === 'loading' && 'motion-safe:animate-pulse',
                        )
                    "
                    stroke-width="1.25"
                    aria-hidden="true"
                />
                <p
                    class="relative text-sm text-muted-foreground"
                    :role="status === 'error' ? 'alert' : undefined"
                >
                    {{
                        status === 'error'
                            ? 'The map could not be loaded. Please try again later.'
                            : 'Loading map…'
                    }}
                </p>
            </div>

            <div
                class="absolute top-3 right-3 z-(--z-raised) flex flex-col gap-2"
            >
                <div
                    class="flex flex-col divide-y divide-border overflow-hidden rounded-md border border-border bg-background shadow-sm"
                >
                    <button
                        type="button"
                        :class="controlClass"
                        aria-label="Zoom in"
                        :disabled="status !== 'ready'"
                        @click="zoomBy(1)"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        :class="controlClass"
                        aria-label="Zoom out"
                        :disabled="status !== 'ready'"
                        @click="zoomBy(-1)"
                    >
                        <Minus class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <button
                    type="button"
                    :class="
                        cn(
                            controlClass,
                            'rounded-md border border-border bg-background shadow-sm',
                        )
                    "
                    aria-label="Reset view"
                    :disabled="status !== 'ready'"
                    @click="resetView()"
                >
                    <LocateFixed class="size-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <Teleport v-if="popupTarget" :to="popupTarget">
            <MapProjectCard v-if="popupProject" :project="popupProject" />
        </Teleport>
    </div>
</template>
