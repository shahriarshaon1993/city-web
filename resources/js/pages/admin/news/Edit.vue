<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, Trash2 } from '@lucide/vue';
import { computed, provide, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SlugField from '@/components/admin/SlugField.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RelatedPicker from '@/components/admin/catalog/RelatedPicker.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import ArticleEditor from '@/components/admin/news/ArticleEditor.vue';
import TagsInput from '@/components/admin/news/TagsInput.vue';
import { errorView } from '@/components/admin/projects/context';
import ConflictDialog from '@/components/admin/publishing/ConflictDialog.vue';
import PublishingRail from '@/components/admin/publishing/PublishingRail.vue';
import RevisionsPanel from '@/components/admin/publishing/RevisionsPanel.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { usePublishingEditor } from '@/composables/usePublishingEditor';
import {
    autosave,
    destroy,
    duplicate,
    index as newsIndex,
    store,
    update,
} from '@/routes/admin/news';
import type { ChecklistItem, PostEditProps, PostFormData } from '@/types';

// ADM-SCR-08 post editor (FR-NEWS-001): title, address, summary, cover and the article on one page, then
// category, author, tags and related projects, SEO and revisions. The rail holds the publishing workflow
// and its checklist (VAL-05). A published post saves to pending changes (CMS-PUB-002); stale saves open the
// conflict dialog; the form autosaves every 60 seconds (UX-10).
const props = defineProps<PostEditProps>();

function copyOf(data: PostFormData): PostFormData {
    return JSON.parse(JSON.stringify(data)) as PostFormData;
}

const form = useForm<PostFormData>(copyOf(props.form));
const confirm = useConfirm();
const errors = errorView(form);

const editor = usePublishingEditor({
    form,
    workflow: () => props.post,
    lastSaved: () => props.lastSaved,
    autosaveUrl: () => (props.post ? autosave.url(props.post.id) : null),
    canEdit: () => props.can.update,
    reseed: () => {
        form.defaults(copyOf(props.form));
        form.reset();
    },
});

provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults(copyOf(props.form));
        form.reset();
    },
);

function save(overwrite = false): void {
    const options = { preserveScroll: true };

    editor.prepare(overwrite);

    if (props.post) {
        form.patch(update.url(props.post.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function fieldId(key: string): string {
    if (key.startsWith('seo.')) {
        return `seo-${key.slice(4).replace('og_image_asset_id', 'og-image').replace(/_/g, '-')}`;
    }

    if (key.startsWith('tags.')) {
        return 'field-tags';
    }

    if (key.startsWith('project_ids.')) {
        return 'field-project_ids';
    }

    return `field-${key.replace(/\./g, '-')}`;
}

// A checklist "Fix" link: scroll to the field and focus it.
function fix(item: ChecklistItem): void {
    const target = document.getElementById(item.field);

    target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    target?.focus({ preventScroll: true });
}

async function deletePost(): Promise<void> {
    if (!props.post) {
        return;
    }

    const live = props.post.status === 'published';
    const answer = await confirm({
        title: 'Delete this post?',
        subject: props.post.title,
        description: live
            ? 'It leaves the website at once. The post moves to the Recycle bin and can be restored as a draft for 90 days.'
            : 'The post moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete post',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.post.id));
    }
}

const title = computed(
    () => form.title.trim() || props.post?.title || 'New post',
);
const slugPath = computed(() => `/news/${form.slug}`);
</script>

<template>
    <Head :title="title" />

    <AdminEditor
        :title="title"
        :description="
            post
                ? `${post.read_minutes} min read. Changes to a published post wait as pending changes until you publish them.`
                : 'Write the article, choose a category and a cover, then save. It stays a draft until you publish it.'
        "
        :form="form"
        :last-saved="editor.savedAt.value"
        :note="editor.autosaveNote.value"
        :save-label="
            post && post.status !== 'published'
                ? 'Save draft'
                : post
                  ? 'Save changes'
                  : 'Save post'
        "
        :save-reason="
            can.update ? null : 'You can read this post but not change it.'
        "
        :field-id="fieldId"
        @save="save()"
    >
        <template #actions>
            <Link
                :href="newsIndex.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All posts</Link
            >
        </template>

        <template #rail>
            <PublishingRail
                :workflow="post"
                noun="post"
                :checklist="checklist"
                :dirty="form.isDirty"
                :can-publish="can.publish"
                :can-edit="can.update"
                :path="slugPath"
                @fix="fix"
            />
            <p v-if="!post" class="border-t pt-4 text-xs text-muted-foreground">
                Save the post first. It stays a draft until you publish it.
            </p>
            <div v-if="post" class="grid gap-1 border-t pt-4">
                <Button
                    v-if="can.duplicate"
                    type="button"
                    variant="ghost"
                    class="w-full justify-start"
                    @click="router.post(duplicate.url(post.id))"
                >
                    <Copy aria-hidden="true" />
                    Duplicate
                </Button>
                <Button
                    v-if="can.delete"
                    type="button"
                    variant="ghost"
                    class="w-full justify-start text-destructive hover:text-destructive"
                    @click="deletePost"
                >
                    <Trash2 aria-hidden="true" />
                    Delete post
                </Button>
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Article"
                description="The title is the page’s only Heading 1, so the article starts at Heading 2."
            >
                <div class="grid gap-5">
                    <TextField
                        id="field-title"
                        v-model="form.title"
                        label="Title"
                        required
                        :maxlength="options.limits.title"
                        placeholder="Keys handed over at Gemcon Palm Court"
                        :error="errors.title"
                    />
                    <SlugField
                        id="field-slug"
                        v-model="form.slug"
                        :from="form.title"
                        source="post"
                        :ignore-id="post?.id ?? null"
                        :published="post?.status === 'published'"
                        prefix="/news/"
                        label="URL"
                        required
                        :error="errors.slug"
                    />
                    <FormField
                        id="field-excerpt"
                        label="Summary"
                        :helper="`Shown on cards and under the title; it is the meta description unless you write one under Search and sharing. Up to ${options.limits.excerpt} characters.`"
                        :error="errors.excerpt"
                    >
                        <template #default="{ describedby, invalid }">
                            <Textarea
                                id="field-excerpt"
                                v-model="form.excerpt"
                                rows="3"
                                :maxlength="options.limits.excerpt"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                            <p
                                class="mt-1 text-right text-xs text-muted-foreground tabular-nums"
                            >
                                {{ form.excerpt.length }} /
                                {{ options.limits.excerpt }}
                            </p>
                        </template>
                    </FormField>
                    <FormField
                        id="field-cover_asset_id"
                        label="Cover image"
                        :helper="`At least ${options.limits.cover_min_width} px wide, with alt text. Shown above the article and on cards.`"
                        :error="errors.cover_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="field-cover_asset_id"
                                v-model="form.cover_asset_id"
                                :kinds="['image']"
                                choose-label="Choose cover image"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-body"
                        label="Article"
                        helper="Headings H2 to H4, in order. Images come from the media library and use its alt text unless you change it here."
                        :error="errors.body"
                    >
                        <template #default="{ describedby, invalid }">
                            <ArticleEditor
                                id="field-body"
                                v-model="form.body"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Details"
                description="Where the post is listed, who wrote it and what it links to."
            >
                <div class="grid gap-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <ChoiceField
                            id="field-category_id"
                            v-model="form.category_id"
                            label="Category"
                            required
                            placeholder="Choose a category"
                            :options="options.categories"
                            helper="Exactly one. Manage them under News › Categories."
                            :error="errors.category_id"
                        />
                        <ChoiceField
                            id="field-author_user_id"
                            v-model="form.author_user_id"
                            label="Author"
                            none-label="Gemcon City editorial team"
                            :options="options.authors"
                            helper="Shown in the byline. Without one the byline says “Gemcon City editorial team”."
                            :error="errors.author_user_id"
                        />
                    </div>
                    <FormField
                        id="field-tags"
                        label="Tags"
                        :helper="`Up to ${options.limits.tags}. Names that match an existing tag, in any letter case, use it.`"
                        :error="errors.tags"
                    >
                        <template #default="{ describedby, invalid }">
                            <TagsInput
                                id="field-tags"
                                v-model="form.tags"
                                :suggestions="options.tag_suggestions"
                                :max="options.limits.tags"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-project_ids"
                        label="Related projects"
                        :helper="`Up to ${options.limits.projects}. Shown under the article; unpublished projects are left out.`"
                        :error="errors.project_ids"
                    >
                        <template #default="{ describedby, invalid }">
                            <RelatedPicker
                                id="field-project_ids"
                                v-model="form.project_ids"
                                :options="options.projects"
                                :max="options.limits.projects"
                                noun="a project"
                                :describedby="describedby"
                                :invalid="invalid"
                            />
                        </template>
                    </FormField>
                    <SwitchRow
                        id="field-is_featured"
                        v-model="form.is_featured"
                        label="Featured"
                        helper="The newest featured post leads page 1 of the news page."
                        :error="errors.is_featured"
                    />
                </div>
            </AdminSection>

            <AdminSection
                title="Search and sharing"
                description="How the post looks in Google and when shared."
            >
                <SeoPanel
                    v-model="form.seo"
                    :path="slugPath"
                    :fallback-title="`${form.title || 'Post title'} | Gemcon City`"
                    :fallback-description="
                        form.excerpt || 'The summary of the post.'
                    "
                    :errors="errors"
                />
            </AdminSection>

            <AdminSection
                v-if="post"
                title="Revisions"
                description="Pending changes and what was published before. Compare a version with the live post, or restore it."
            >
                <RevisionsPanel
                    :type="post.type"
                    :id="post.id"
                    :refresh-key="`${post.lock_version}:${post.status}`"
                    :can-restore="can.update"
                    noun="post"
                />
            </AdminSection>
        </div>
    </AdminEditor>

    <ConflictDialog
        v-model:open="editor.conflictOpen.value"
        :details="editor.conflict.value"
        :can-overwrite="post?.can_overwrite ?? false"
        :busy="form.processing"
        noun="post"
        @reload="editor.reload()"
        @overwrite="editor.overwrite(save)"
    />
</template>
