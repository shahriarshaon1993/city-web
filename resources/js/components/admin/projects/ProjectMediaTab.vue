<script setup lang="ts">
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import FormField from '@/components/forms/FormField.vue';
import GalleryEditor from './GalleryEditor.vue';
import { useProjectEditor } from './context';

// Media: the cover (1600 px wide, required to publish), the gallery (4 to publish), an optional
// video (YouTube link or an uploaded file with its captions) and a virtual tour link.
const { form, errors } = useProjectEditor();

const videoTypes = [
    { value: 'none', label: 'No video' },
    { value: 'youtube', label: 'YouTube link' },
    { value: 'upload', label: 'Uploaded file' },
];
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Cover image"
            description="The photo on cards and at the top of the project page. Needed to publish."
        >
            <FormField
                id="field-cover_asset_id"
                label="Cover image"
                required
                helper="JPEG, PNG, WebP or AVIF, at least 1600 px wide, up to 12 MB. Set the focal point in the library."
                :error="errors.cover_asset_id"
            >
                <template #default="{ describedby, invalid }">
                    <AssetField
                        id="field-cover_asset_id"
                        v-model="form.cover_asset_id"
                        :kinds="['image']"
                        choose-label="Choose cover"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>
        </AdminSection>

        <AdminSection
            title="Gallery"
            description="Shown in the project gallery and lightbox. At least 4 images to publish, up to 60."
        >
            <GalleryEditor />
        </AdminSection>

        <AdminSection
            title="Video"
            description="One video per project, shown on its own section."
        >
            <div class="grid gap-5">
                <ChoiceField
                    id="field-video_type"
                    v-model="form.video_type"
                    label="Video"
                    :options="videoTypes"
                    :error="errors.video_type"
                />

                <TextField
                    v-if="form.video_type === 'youtube'"
                    id="field-video_url"
                    v-model="form.video_url"
                    type="url"
                    label="YouTube link"
                    required
                    :maxlength="255"
                    placeholder="https://www.youtube.com/watch?v=…"
                    helper="youtube.com/watch?v=…, youtu.be/… or youtube.com/shorts/… The site checks the shape of the link only."
                    :error="errors.video_url"
                />

                <template v-if="form.video_type === 'upload'">
                    <FormField
                        id="field-video_asset_id"
                        label="Video file"
                        required
                        helper="MP4 (H.264), up to 50 MB, from the media library."
                        :error="errors.video_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="field-video_asset_id"
                                v-model="form.video_asset_id"
                                :kinds="['video']"
                                choose-label="Choose video"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-video_captions_asset_id"
                        label="Captions (WebVTT)"
                        helper="Required when the video has speech, so deaf and hard-of-hearing visitors can follow it. The library cannot take new .vtt uploads yet; pick one that is already there."
                        :error="errors.video_captions_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="field-video_captions_asset_id"
                                v-model="form.video_captions_asset_id"
                                :kinds="['document']"
                                choose-label="Choose .vtt file"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </template>

                <FormField
                    v-if="form.video_type !== 'none'"
                    id="field-video_poster_asset_id"
                    label="Poster image"
                    helper="Shown before the video plays. Empty uses the cover image."
                    :error="errors.video_poster_asset_id"
                >
                    <template #default="{ describedby, invalid }">
                        <AssetField
                            id="field-video_poster_asset_id"
                            v-model="form.video_poster_asset_id"
                            :kinds="['image']"
                            choose-label="Choose poster"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>
            </div>
        </AdminSection>

        <AdminSection
            title="Virtual tour"
            description="Optional. A link opens as a facade first; nothing loads until the visitor asks."
        >
            <TextField
                id="field-virtual_tour_url"
                v-model="form.virtual_tour_url"
                type="url"
                label="Virtual tour link"
                :maxlength="255"
                placeholder="https://my.matterport.com/show/?m=…"
                helper="From my.matterport.com, kuula.co or www.youtube.com only."
                :error="errors.virtual_tour_url"
            />
        </AdminSection>
    </div>
</template>
