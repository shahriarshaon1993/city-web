import { Building, House, LandPlot, Layers } from '@lucide/vue';
import type { Component } from 'vue';

// Menu item icons (CMS-NAV-002) by Lucide name. Only these names are
// bundled; the menu editor (12.2) offers the same allowlist.
export const menuIcons: Record<string, Component> = {
    house: House,
    building: Building,
    layers: Layers,
    'land-plot': LandPlot,
};
