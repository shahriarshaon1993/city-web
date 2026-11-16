<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleSlash,
    ExternalLink,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, provide, ref, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import SlugField from '@/components/admin/SlugField.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RelatedPicker from '@/components/admin/catalog/RelatedPicker.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
    newKey,
} from '@/components/admin/catalog/context';
import { errorView } from '@/components/admin/projects/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, index as locationsIndex } from '@/routes/admin/locations';
import { store, update } from '@/routes/admin/locations/areas';
import type { AreaFormData, AreaOptions, AreaSummary, AssetMap } from '@/types';

// ADM-SCR-04 area editor (FR-LOC-002): identity, intro, facts, map centre, nearby areas and SEO on
// one page. The badge under the intro says whether the page is indexable and why not (FR-LOC-003),
// live as the intro changes. Delete is refused while the area has projects (DEL-03).
const props = defineProps<{
    area: AreaSummary | null;
    form: AreaFormData;
    assets: AssetMap;
    options: AreaOptions;
    lastSaved: string | null;
    can: { save: boolean; delete: boolean };
}>();

function withKeys(data: AreaFormData): AreaFormData {
    const clone = JSON.parse(JSON.stringify(data)) as AreaFormData;
    clone.facts = clone.facts.map((fact) => ({ ...fact, key: newKey('f') }));

    return clone;
}

const form = useForm<AreaFormData>(withKeys(props.form));
const confirm = useConfirm();
const errors = errorView(form);

provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults(withKeys(props.form));
        form.reset();
    },
);

const published = computed({
    get: () => form.publish_status === 'published',
    set: (value: boolean) => {
        form.publish_status = value ? 'published' : 'draft';
    },
});

// FR-LOC-003 live: the saved project count with the words of the intro as typed.
const words = computed(() => {
    const text = form.intro
        .replace(/<\/(p|li|h[2-4]|blockquote)>|<br\s*\/?>/gi, ' ')
        .replace(/<[^>]*>/g, ' ')
        .trim();

    return text === '' ? 0 : text.split(/\s+/u).length;
});

const reasons = computed(() => {
    const result: string[] = [];

    if ((props.area?.indexability.published_projects ?? 0) === 0) {
        result.push('No published project in this area yet.');
    }

    if (words.value < props.options.min_intro_words) {
        result.push(
            `The intro has ${words.value} of the ${props.options.min_intro_words} words it needs.`,
        );
    }

    return result;
});

function addFact(): void {
    if (form.facts.length < props.options.limits.facts) {
        form.facts.push({ key: newKey('f'), label: '', value: '' });
    }
}

function save(): void {
    const options = { preserveScroll: true };

    if (props.area) {
        form.patch(update.url(props.area.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function fieldId(key: string): string {
    if (key.startsWith('seo.')) {
        return `seo-${key.slice(4).replace('og_image_asset_id', 'og-image').replace(/_/g, '-')}`;
    }

    return `field-${key.replace(/\./g, '-')}`;
}

async function deleteArea(): Promise<void> {
    if (!props.area) {
        return;
    }

    const answer = await confirm({
        title: 'Delete this area?',
        subject: props.area.name,
        description:
            'The area page leaves the website. The area moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete area',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.area.id));
    }
}

const title = computed(
    () => form.name.trim() || props.area?.name || 'New area',
);
const cityOptions = computed(() => props.options.cities);
const segmentIntro = ref(
    Object.values(props.form.segment_intros).some((text) => text !== ''),
);
</script>

<template>
    <Head :title="title" />

    <AdminEditor
        :title="title"
        :description="
            area
                ? 'The area page lists its projects. Add an intro of at least 150 words so search engines may index it.'
                : 'Choose the city, name the area and save. Publish it when its intro is ready.'
        "
        :form="form"
        :last-saved="lastSaved"
        save-label="Save area"
        :save-reason="
            can.save ? null : 'You can read this area but not change it.'
        "
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="locationsIndex.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All locations</Link
            >
        </template>

        <template #rail>
            <div class="grid gap-3 border-t pt-4">
                <p v-if="area" class="text-xs text-muted-foreground">
                    {{ area.projects_count }}
                    {{ area.projects_count === 1 ? 'project' : 'projects' }} in
                    this area.
                </p>
                <Button
                    v-if="area?.public_url"
                    as-child
                    variant="ghost"
                    class="w-full"
                >
                    <a :href="area.public_url" target="_blank" rel="noopener">
                        <ExternalLink aria-hidden="true" />
                        View on site
                    </a>
                </Button>
                <ReasonButton
                    v-if="area && can.delete"
                    type="button"
                    variant="ghost"
                    class="w-full justify-start text-destructive hover:text-destructive"
                    :reason="
                        area.projects_count > 0
                            ? `Used by ${area.projects_count} ${area.projects_count === 1 ? 'project' : 'projects'} — move them to another area first.`
                            : null
                    "
                    @click="deleteArea"
                >
                    <Trash2 aria-hidden="true" />
                    Delete area
                </ReasonButton>
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Identity"
                description="Name, URL and the city the area belongs to."
            >
                <div class="grid gap-5">
                    <TextField
                        id="field-name"
                        v-model="form.name"
                        label="Area name"
                        required
                        :maxlength="100"
                        placeholder="Bashundhara R/A"
                        :error="errors.name"
                    />
                    <SlugField
                        id="field-slug"
                        v-model="form.slug"
                        :from="form.name"
                        source="location"
                        :ignore-id="area?.id ?? null"
                        :published="area?.status === 'published'"
                        prefix="/locations/"
                        label="URL"
                        required
                        :error="errors.slug"
                    />
                    <ChoiceField
                        id="field-parent_id"
                        v-model="form.parent_id"
                        label="City"
                        required
                        placeholder="Choose a city"
                        :options="cityOptions"
                        helper="Move an area to another city here."
                        :error="errors.parent_id"
                    />
                    <SwitchRow
                        id="field-publish_status"
                        v-model="published"
                        label="Published"
                        helper="A draft area is hidden from the website, and so are its landing pages."
                        :error="errors.publish_status"
                    />
                </div>
            </AdminSection>

            <AdminSection
                title="Intro"
                description="The text at the top of the area page."
            >
                <div class="grid gap-4">
                    <FormField
                        id="field-intro"
                        label="Intro"
                        :error="errors.intro"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-intro"
                                v-model="form.intro"
                                :min-words="options.min_intro_words"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <div
                        role="status"
                        :class="[
                            'flex gap-3 rounded-lg border p-3 text-sm',
                            reasons.length === 0
                                ? 'border-success/40 bg-success-50 text-success-700'
                                : 'bg-muted/50',
                        ]"
                    >
                        <CircleCheck
                            v-if="reasons.length === 0"
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <CircleSlash
                            v-else
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="font-medium">
                                {{
                                    reasons.length === 0
                                        ? 'Indexable'
                                        : 'Not indexed (noindex, follow)'
                                }}
                            </p>
                            <p
                                v-if="reasons.length"
                                class="text-muted-foreground"
                            >
                                {{ reasons.join(' ') }} The page still works for
                                visitors; it stays out of search results and the
                                sitemaps.
                            </p>
                            <p v-else>
                                Search engines may index this page. Publish the
                                area for it to go live.
                            </p>
                        </div>
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Facts"
                :description="`Label and value pairs in the “At a glance” list. Up to ${options.limits.facts}.`"
            >
                <div id="field-facts" class="grid gap-3">
                    <div
                        v-for="(fact, index) in form.facts"
                        :key="fact.key"
                        class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start"
                    >
                        <TextField
                            :id="`field-facts-${index}-label`"
                            v-model="fact.label"
                            label="Label"
                            required
                            :maxlength="60"
                            :error="errors[`facts.${index}.label`]"
                        />
                        <TextField
                            :id="`field-facts-${index}-value`"
                            v-model="fact.value"
                            label="Value"
                            required
                            :maxlength="120"
                            :error="errors[`facts.${index}.value`]"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="text-destructive hover:text-destructive sm:mt-7"
                            :aria-label="`Remove fact ${index + 1}`"
                            @click="form.facts.splice(index, 1)"
                        >
                            <Trash2 aria-hidden="true" />
                        </Button>
                    </div>
                    <p
                        v-if="!form.facts.length"
                        class="text-sm text-muted-foreground"
                    >
                        No facts yet.
                    </p>
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="
                                form.facts.length >= options.limits.facts
                            "
                            @click="addFact"
                        >
                            <Plus aria-hidden="true" />
                            Add fact
                        </Button>
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Photo and map"
                description="The hero photo and where the map is centred."
            >
                <div class="grid gap-5">
                    <FormField
                        id="field-hero_asset_id"
                        label="Hero image"
                        helper="At least 1600 px wide. Shown under the header on the area page."
                        :error="errors.hero_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="field-hero_asset_id"
                                v-model="form.hero_asset_id"
                                :kinds="['image']"
                                choose-label="Choose hero image"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <div class="grid gap-5 md:grid-cols-3">
                        <TextField
                            id="field-latitude"
                            v-model="form.latitude"
                            numeric
                            :step="0.0000001"
                            :min="20.5"
                            :max="26.7"
                            label="Latitude"
                            helper="20.5 to 26.7"
                            :error="errors.latitude"
                        />
                        <TextField
                            id="field-longitude"
                            v-model="form.longitude"
                            numeric
                            :step="0.0000001"
                            :min="88"
                            :max="92.7"
                            label="Longitude"
                            helper="88.0 to 92.7"
                            :error="errors.longitude"
                        />
                        <TextField
                            id="field-zoom"
                            v-model="form.zoom"
                            numeric
                            :step="1"
                            :min="options.limits.zoom_min"
                            :max="options.limits.zoom_max"
                            label="Map zoom"
                            required
                            :helper="`${options.limits.zoom_min} (city) to ${options.limits.zoom_max} (street)`"
                            :error="errors.zoom"
                        />
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Nearby areas"
                :description="`Linked from the area page. Up to ${options.limits.nearby}.`"
            >
                <FormField
                    id="field-nearby_ids"
                    label="Nearby areas"
                    :error="errors.nearby_ids"
                >
                    <template #default="{ describedby, invalid }">
                        <RelatedPicker
                            id="field-nearby_ids"
                            v-model="form.nearby_ids"
                            :options="options.areas"
                            :max="options.limits.nearby"
                            noun="an area"
                            :describedby="describedby"
                            :invalid="invalid"
                        />
                    </template>
                </FormField>
            </AdminSection>

            <AdminSection
                title="Intros by segment"
                description="Optional. Text for /locations/area/residential and the other segment pages; without it the page uses a template sentence and is not indexed."
            >
                <div class="grid gap-5">
                    <Button
                        v-if="!segmentIntro"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="justify-self-start"
                        @click="segmentIntro = true"
                    >
                        Write segment intros
                    </Button>
                    <template v-else>
                        <FormField
                            v-for="segment in options.segments"
                            :id="`field-segment_intros-${segment.value}`"
                            :key="segment.value"
                            :label="segment.label"
                            :error="errors[`segment_intros.${segment.value}`]"
                        >
                            <template #default="{ describedby, invalid }">
                                <Textarea
                                    :id="`field-segment_intros-${segment.value}`"
                                    v-model="form.segment_intros[segment.value]"
                                    rows="4"
                                    :aria-invalid="invalid || undefined"
                                    :aria-describedby="describedby"
                                />
                            </template>
                        </FormField>
                    </template>
                </div>
            </AdminSection>

            <AdminSection
                title="Search and sharing"
                description="How the area page looks in Google and when shared."
            >
                <SeoPanel
                    v-model="form.seo"
                    :path="`/locations/${form.slug || 'area-name'}`"
                    :fallback-title="`Flats & Commercial Spaces in ${form.name || 'this area'} | Gemcon City`"
                    :fallback-description="`Projects in ${form.name || 'this area'} by Gemcon City. Book a site visit.`"
                    :errors="errors"
                />
            </AdminSection>
        </div>
    </AdminEditor>
</template>
