<script setup lang="ts">
import { computed, ref } from 'vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { SeoForm } from '@/types';
import AssetField from './AssetField.vue';
import AdminSelect from './AdminSelect.vue';
import { useAssetMap } from './context';
import TextField from './TextField.vue';

// CMP-ADM-SEO (spec §8.5): meta title and description with counters coloured per SEO-ONP-001/002
// (title 30–60 characters, description 70–160), a Google result preview (desktop / mobile), the
// share card preview, robots, canonical, share title / description / image and the schema switch.
// An empty meta field means "use the template" (§9.3); the preview then shows the fallback text.
const props = defineProps<{
    /** The public path, e.g. `/projects/gemcon-orchid`. */
    path: string;
    /** What the template produces, shown while a field is empty. */
    fallbackTitle: string;
    fallbackDescription: string;
    /** Errors of the form, keyed `seo.meta_title` … */
    errors: Record<string, string | undefined>;
}>();

const seo = defineModel<SeoForm>({ required: true });
const { assets } = useAssetMap();
const view = ref<'desktop' | 'mobile'>('desktop');

const host = computed(() =>
    typeof window === 'undefined' ? 'gemcon.example' : window.location.host,
);
const title = computed(
    () => seo.value.meta_title.trim() || props.fallbackTitle,
);
const description = computed(
    () => seo.value.meta_description.trim() || props.fallbackDescription,
);
const shareImage = computed(() => {
    const id = seo.value.og_image_asset_id;

    return id === null ? null : (assets[id]?.thumb_url ?? null);
});

const robots = [
    { value: 'index,follow', label: 'Index and follow links (default)' },
    { value: 'noindex,follow', label: 'Keep out of search, follow links' },
    { value: 'noindex,nofollow', label: 'Keep out of search, do not follow' },
];

const cards = [
    { value: 'summary_large_image', label: 'Large image' },
    { value: 'summary', label: 'Small image' },
];

const err = (field: string): string | undefined => props.errors[`seo.${field}`];
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-3">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-medium">Search result preview</h3>
                <div
                    class="inline-flex rounded-md border p-0.5 text-xs"
                    role="group"
                    aria-label="Preview size"
                >
                    <button
                        v-for="size in ['desktop', 'mobile'] as const"
                        :key="size"
                        type="button"
                        :aria-pressed="view === size"
                        :class="
                            cn(
                                'rounded px-2.5 py-1 capitalize',
                                view === size
                                    ? 'bg-foreground text-background'
                                    : 'text-muted-foreground hover:text-foreground',
                            )
                        "
                        @click="view = size"
                    >
                        {{ size }}
                    </button>
                </div>
            </div>
            <div
                :class="
                    cn(
                        'rounded-lg border bg-white p-4 text-stone-900',
                        view === 'mobile' ? 'max-w-[22rem]' : 'max-w-2xl',
                    )
                "
            >
                <p class="truncate text-xs text-stone-600">
                    {{ host }} ›
                    {{ path.replace(/^\//, '').split('/').join(' › ') }}
                </p>
                <p class="mt-1 line-clamp-2 text-lg/6 text-[#1a0dab]">
                    {{ title }}
                </p>
                <p class="mt-1 line-clamp-3 text-sm/5 text-stone-700">
                    {{ description }}
                </p>
            </div>
        </div>

        <div class="grid gap-5">
            <FormField
                id="seo-meta-title"
                label="Meta title"
                helper="The title in search results. 30–60 characters read best; empty uses the automatic title."
                :error="err('meta_title')"
            >
                <template #default="{ describedby, invalid }">
                    <Input
                        id="seo-meta-title"
                        v-model="seo.meta_title"
                        maxlength="70"
                        :placeholder="fallbackTitle"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                    />
                    <CharCounter
                        :value="seo.meta_title"
                        :max="70"
                        always
                        :ideal="{ min: 30, max: 60 }"
                    />
                </template>
            </FormField>

            <FormField
                id="seo-meta-description"
                label="Meta description"
                helper="The text under the title in search results. 70–160 characters; empty uses the automatic description."
                :error="err('meta_description')"
            >
                <template #default="{ describedby, invalid }">
                    <Textarea
                        id="seo-meta-description"
                        v-model="seo.meta_description"
                        rows="3"
                        maxlength="170"
                        :placeholder="fallbackDescription"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                    />
                    <CharCounter
                        :value="seo.meta_description"
                        :max="170"
                        always
                        :ideal="{ min: 70, max: 160 }"
                    />
                </template>
            </FormField>

            <div class="grid gap-5 md:grid-cols-2">
                <FormField
                    id="seo-robots"
                    label="Search engines"
                    helper="Keep a page out of search results only on purpose."
                    :error="err('robots')"
                >
                    <template #default="{ describedby, invalid }">
                        <AdminSelect
                            id="seo-robots"
                            v-model="seo.robots"
                            :options="robots"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>
                <TextField
                    id="seo-canonical"
                    v-model="seo.canonical_url"
                    type="url"
                    label="Canonical address"
                    :maxlength="500"
                    placeholder="https://"
                    helper="Only to point at another page that holds the same content. Leave empty normally."
                    :error="err('canonical_url')"
                />
            </div>
        </div>

        <div class="grid gap-5 border-t pt-6">
            <h3 class="text-sm font-medium">When the page is shared</h3>

            <div
                class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_16rem] lg:items-start"
            >
                <div class="grid gap-5">
                    <FormField
                        id="seo-og-title"
                        label="Share title"
                        helper="Facebook, WhatsApp and LinkedIn. Empty uses the meta title."
                        :error="err('og_title')"
                    >
                        <template #default="{ describedby, invalid }">
                            <Input
                                id="seo-og-title"
                                v-model="seo.og_title"
                                maxlength="70"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="seo-og-description"
                        label="Share description"
                        :error="err('og_description')"
                    >
                        <template #default="{ describedby, invalid }">
                            <Textarea
                                id="seo-og-description"
                                v-model="seo.og_description"
                                rows="2"
                                maxlength="200"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                            <CharCounter
                                :value="seo.og_description"
                                :max="200"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="seo-og-image"
                        label="Share image"
                        helper="Cropped to 1200 × 630 around its focal point. Empty uses the cover or hero image."
                        :error="err('og_image_asset_id')"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="seo-og-image"
                                v-model="seo.og_image_asset_id"
                                :kinds="['image']"
                                choose-label="Choose image"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="seo-twitter-card"
                        label="Card on X (Twitter)"
                        :error="err('twitter_card')"
                    >
                        <template #default="{ describedby, invalid }">
                            <AdminSelect
                                id="seo-twitter-card"
                                v-model="seo.twitter_card"
                                :options="cards"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>

                <div
                    class="overflow-hidden rounded-lg border bg-white text-stone-900"
                    aria-hidden="true"
                >
                    <div
                        class="grid aspect-[1200/630] place-items-center bg-stone-200 text-xs text-stone-500"
                    >
                        <img
                            v-if="shareImage"
                            :src="shareImage"
                            alt=""
                            class="size-full object-cover"
                        />
                        <span v-else>1200 × 630</span>
                    </div>
                    <div class="grid gap-0.5 border-t p-3">
                        <p
                            class="truncate text-[11px] text-stone-500 uppercase"
                        >
                            {{ host }}
                        </p>
                        <p class="line-clamp-2 text-sm font-semibold">
                            {{ seo.og_title.trim() || title }}
                        </p>
                        <p class="line-clamp-2 text-xs text-stone-600">
                            {{ seo.og_description.trim() || description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 border-t pt-6 md:grid-cols-2">
            <TextField
                id="seo-focus"
                v-model="seo.focus_keyword"
                label="Focus keyword"
                helper="A note for you: the phrase this page should be found for. Visitors never see it."
                :maxlength="80"
                :error="err('focus_keyword')"
            />
            <div class="flex items-start justify-between gap-4">
                <div class="grid gap-1">
                    <label for="seo-schema" class="text-sm font-medium"
                        >Structured data (JSON-LD)</label
                    >
                    <p class="text-sm text-muted-foreground">
                        Lets Google show rich results. Keep it on unless the
                        page needs it off.
                    </p>
                </div>
                <Switch id="seo-schema" v-model="seo.schema_enabled" />
            </div>
        </div>
    </div>
</template>
