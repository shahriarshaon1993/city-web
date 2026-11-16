import {
    Briefcase,
    Building,
    CircleHelp,
    FileText,
    House,
    Info,
    LandPlot,
    Layers,
    MapPin,
    Newspaper,
    Phone,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';

// Menu item icons (CMS-NAV-002) by Lucide name. Only these names are bundled; the menu editor
// offers the same allowlist (App\Support\Menus\MenuIcons — keep the two in step).
export const menuIcons: Record<string, Component> = {
    house: House,
    building: Building,
    layers: Layers,
    'land-plot': LandPlot,
    'map-pin': MapPin,
    phone: Phone,
    newspaper: Newspaper,
    briefcase: Briefcase,
    users: Users,
    'file-text': FileText,
    info: Info,
    'circle-help': CircleHelp,
};
