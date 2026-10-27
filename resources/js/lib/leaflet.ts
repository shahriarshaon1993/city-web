import type * as Leaflet from 'leaflet';

// FR-LOC-006 / SEO-PERF-008: Leaflet 1.9.4 + leaflet.markercluster 1.5.3 and
// their styles load only here, the first time a map is shown (client only,
// never during SSR). The cluster plugin is a UMD build that extends the
// global `L`, so it is imported after `window.L` is set.

export type LeafletModule = typeof Leaflet;

let loading: Promise<LeafletModule> | null = null;

export function loadLeaflet(): Promise<LeafletModule> {
    loading ??= (async () => {
        const L = (await import('leaflet')).default;
        (window as unknown as { L: LeafletModule }).L = L;

        await Promise.all([
            import('leaflet.markercluster'),
            import('leaflet/dist/leaflet.css'),
            import('leaflet.markercluster/dist/MarkerCluster.css'),
            import('../../css/map.css'),
        ]);

        return L;
    })().catch((error: unknown) => {
        loading = null;

        throw error;
    });

    return loading;
}

/** OSM standard tiles with the attribution their policy requires (FR-LOC-006). */
export const OSM_TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

export const OSM_ATTRIBUTION =
    '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';

/** Where an empty map looks (Dhaka). */
export const DEFAULT_CENTRE = { lat: 23.7808, lng: 90.4074 };

/** CMP-MAP-01 marker: a pointy-top hexagon (DP-03) with a white border. */
export const MARKER_SVG =
    '<svg viewBox="0 0 32 36" aria-hidden="true" focusable="false"><path d="M16 1.5 30 9.5v17L16 34.5 2 26.5v-17z" fill="currentColor" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/><circle cx="16" cy="18" r="4" fill="#fff"/></svg>';
