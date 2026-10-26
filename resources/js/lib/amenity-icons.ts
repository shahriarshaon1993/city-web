import {
    ArrowUpDown,
    Baby,
    Cctv,
    ConciergeBell,
    Droplets,
    Dumbbell,
    Fence,
    FireExtinguisher,
    Flame,
    Moon,
    Phone,
    ShieldCheck,
    SquareParking,
    Store,
    Sun,
    Trees,
    Users,
    WavesLadder,
    Wifi,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';

// DM-AMEN `icon` allowlist by Lucide name (4.2); the amenities admin (13.2)
// offers the same names. Kept apart from lib/icons.ts so these icons stay in
// the project page chunk. An unknown name renders no icon.
export const amenityIcons: Record<string, Component> = {
    'arrow-up-down': ArrowUpDown,
    baby: Baby,
    cctv: Cctv,
    'concierge-bell': ConciergeBell,
    droplets: Droplets,
    dumbbell: Dumbbell,
    fence: Fence,
    'fire-extinguisher': FireExtinguisher,
    flame: Flame,
    moon: Moon,
    phone: Phone,
    'shield-check': ShieldCheck,
    'square-parking': SquareParking,
    store: Store,
    sun: Sun,
    trees: Trees,
    users: Users,
    'waves-ladder': WavesLadder,
    wifi: Wifi,
    zap: Zap,
};
