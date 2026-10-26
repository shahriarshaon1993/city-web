// FR-LOC-007 keyless Google Maps URLs (no API key, no paid API, BG-06): the
// embed loads only after a click (CMP-MAP-02), the search link opens Maps.

type LatLng = { lat: number; lng: number };

export function googleMapsEmbedUrl({ lat, lng }: LatLng): string {
    return `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
}

export function googleMapsUrl({ lat, lng }: LatLng): string {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
}
