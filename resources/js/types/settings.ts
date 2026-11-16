// Settings screen (ADM-SCR-15). Shapes of App\Support\Settings\{Field, SettingsGroup}::schema().

import type { MediaAsset, MediaKind } from '@/types/media';

export type SettingType =
    | 'text'
    | 'textarea'
    | 'url'
    | 'link'
    | 'email'
    | 'phone'
    | 'bool'
    | 'int'
    | 'select'
    | 'media'
    | 'secret'
    | 'object'
    | 'list';

export type SettingField = {
    key: string;
    type: SettingType;
    label: string;
    required: boolean;
    help?: string;
    placeholder?: string;
    /** Character limit for text, largest value for numbers. */
    max?: number;
    min?: number;
    rows?: number;
    mono?: boolean;
    options?: { value: string; label: string }[];
    kinds?: MediaKind[];
    media_hint?: string;
    max_items?: number;
    min_items?: number;
    /** `{tokens}` the text may use. */
    tokens?: string[];
    /** Children of an `object`, or of a list of objects. */
    fields?: SettingField[];
    /** The one scalar kind of a list. */
    item?: SettingField;
    /** A new empty row of a list. */
    blank?: unknown;
};

export type SettingSection = {
    title: string;
    description: string | null;
    fields: SettingField[];
};

export type SettingsTab = {
    key: string;
    label: string;
    /** Which group of tabs it belongs to in the navigation. */
    nav: string;
    href: string;
};

export type SettingsGroupInfo = {
    key: string;
    label: string;
    description: string;
    sections: SettingSection[];
};

export type SettingsAssets = Record<number, MediaAsset>;
