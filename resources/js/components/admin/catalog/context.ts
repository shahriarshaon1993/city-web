import type { InjectionKey } from 'vue';
import { inject, reactive } from 'vue';
import type { AssetMap, MediaAsset } from '@/types';

// The library items a catalogue editor points at (cover, gallery, floor plans …), by id, so a
// field can show its preview without asking the server; a newly picked item is remembered here.
export type AssetMapContext = {
    assets: AssetMap;
    remember: (asset: MediaAsset) => void;
};

export const assetMapKey: InjectionKey<AssetMapContext> = Symbol('asset-map');

export function createAssetMap(initial: AssetMap): AssetMapContext {
    const assets = reactive<AssetMap>({ ...initial });

    return {
        assets,
        remember: (asset: MediaAsset): void => {
            assets[asset.id] = asset;
        },
    };
}

export function useAssetMap(): AssetMapContext {
    const context = inject(assetMapKey);

    if (!context) {
        throw new Error('The editor must provide the asset map.');
    }

    return context;
}

let counter = 0;

/** A stable key for a repeater card (never sent as meaning; the server ignores it). */
export function newKey(prefix = 'k'): string {
    counter += 1;

    return `${prefix}${Date.now().toString(36)}${counter}`;
}
