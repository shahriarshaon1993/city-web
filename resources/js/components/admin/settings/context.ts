import type { InjectionKey } from 'vue';
import type { SettingsAssets } from '@/types';

// Shared by SettingField and the Settings page: what a field needs besides its own value.
export type SettingsContext = {
    /** Server errors keyed by path, e.g. `values.thank_you.contact.title`. */
    errors: Record<string, string | undefined>;
    /** Library items already chosen, for media previews. */
    assets: SettingsAssets;
    /** Which secrets hold a saved value. */
    secrets: Record<string, boolean>;
    /** Secrets the user asked to remove. */
    isCleared: (key: string) => boolean;
    toggleClear: (key: string) => void;
};

export const settingsContextKey: InjectionKey<SettingsContext> =
    Symbol('settings-context');

/** The DOM id of a field, also used by the error summary links. */
export function settingFieldId(path: string): string {
    return `f-${path.replace(/[^A-Za-z0-9]+/g, '-')}`;
}
