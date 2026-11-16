<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, provide, reactive, ref, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import ErrorBadge from '@/components/admin/ErrorBadge.vue';
import {
    assetMapKey,
    createAssetMap,
    newKey,
} from '@/components/admin/catalog/context';
import ProjectAmenitiesTab from '@/components/admin/projects/ProjectAmenitiesTab.vue';
import ProjectBasicsTab from '@/components/admin/projects/ProjectBasicsTab.vue';
import ProjectContentTab from '@/components/admin/projects/ProjectContentTab.vue';
import ProjectDocumentsTab from '@/components/admin/projects/ProjectDocumentsTab.vue';
import ProjectFactsTab from '@/components/admin/projects/ProjectFactsTab.vue';
import ProjectMediaTab from '@/components/admin/projects/ProjectMediaTab.vue';
import ProjectNearbyTab from '@/components/admin/projects/ProjectNearbyTab.vue';
import ProjectPricingTab from '@/components/admin/projects/ProjectPricingTab.vue';
import ProjectProgressTab from '@/components/admin/projects/ProjectProgressTab.vue';
import ProjectRail from '@/components/admin/projects/ProjectRail.vue';
import ProjectRoutingTab from '@/components/admin/projects/ProjectRoutingTab.vue';
import ProjectSeoTab from '@/components/admin/projects/ProjectSeoTab.vue';
import ProjectUnitsTab from '@/components/admin/projects/ProjectUnitsTab.vue';
import {
    errorView,
    fieldIdOfError,
    projectEditorKey,
    tabOfError,
} from '@/components/admin/projects/context';
import { useConfirm } from '@/composables/useConfirm';
import { cn } from '@/lib/utils';
import {
    duplicate,
    destroy,
    publish,
    store,
    unpublish,
    update,
    index as projectsIndex,
} from '@/routes/admin/projects';
import type {
    AssetMap,
    ChecklistItem,
    ProjectFormData,
    ProjectOptions,
    ProjectSummary,
    RedirectOption,
} from '@/types';

// ADM-SCR-01 project editor (design/admin.md §12.4): tabs Basics … SEO on one form, one Save, the
// publish checklist and the verbs in the rail. Errors in a tab show as a red count on it, a
// repeater card with errors opens itself, and the error summary jumps across tabs (VAL-07).
// The Revisions tab and Schedule arrive with the publishing workflow (14.1).
const props = defineProps<{
    project: ProjectSummary | null;
    form: ProjectFormData;
    assets: AssetMap;
    options: ProjectOptions;
    checklist: ChecklistItem[];
    lastSaved: string | null;
    can: {
        publish: boolean;
        delete: boolean;
        create: boolean;
        update: boolean;
    };
    redirectOptions: RedirectOption[];
}>();

const LISTS = [
    'custom_facts',
    'highlights',
    'gallery',
    'unit_types',
    'progress_updates',
    'nearby_places',
    'documents',
] as const;

// Props are reactive proxies; the form needs plain JSON, and every repeater card a client key.
function withKeys(data: ProjectFormData): ProjectFormData {
    const clone = JSON.parse(JSON.stringify(data)) as ProjectFormData;

    for (const list of LISTS) {
        (clone[list] as Array<{ key: string }>) = (
            clone[list] as Array<Record<string, unknown>>
        ).map((item) => ({ ...item, key: newKey() })) as Array<{ key: string }>;
    }

    return clone;
}

const form = useForm<ProjectFormData>(withKeys(props.form));
const confirm = useConfirm();

provide(assetMapKey, createAssetMap(props.assets));
provide(
    projectEditorKey,
    reactive({
        form,
        options: props.options,
        errors: errorView(form),
        get published() {
            return props.project?.status === 'published';
        },
        projectId: props.project?.id ?? null,
        canEdit: props.can.update,
    }),
);

// A saved project starts from the server's values again (new ids, trimmed text). Only a save changes
// `lastSaved`, so a failed save keeps everything that was typed (VAL-06).
watch(
    () => props.lastSaved,
    () => {
        form.defaults(withKeys(props.form));
        form.reset();
    },
);

// --- tabs -------------------------------------------------------------------------------------
const TABS = [
    { key: 'basics', label: 'Basics' },
    { key: 'facts', label: 'Facts' },
    { key: 'content', label: 'Content' },
    { key: 'media', label: 'Media' },
    { key: 'units', label: 'Unit types' },
    { key: 'amenities', label: 'Amenities' },
    { key: 'progress', label: 'Progress' },
    { key: 'nearby', label: 'Nearby' },
    { key: 'documents', label: 'Documents' },
    { key: 'pricing', label: 'Pricing' },
    { key: 'routing', label: 'Lead routing' },
    { key: 'seo', label: 'SEO' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

const tab = ref<TabKey>('basics');

// "Plans" is hidden for a land project without unit types (spec edge case).
const tabs = computed(() =>
    TABS.filter(
        (item) =>
            item.key !== 'units' ||
            form.segment !== 'land' ||
            form.unit_types.length > 0,
    ),
);

const tabErrors = computed(() => {
    const counts: Record<string, number> = {};

    for (const key of Object.keys(form.errors)) {
        const target = tabOfError(key);
        counts[target] = (counts[target] ?? 0) + 1;
    }

    return counts;
});

const components: Record<TabKey, unknown> = {
    basics: ProjectBasicsTab,
    facts: ProjectFactsTab,
    content: ProjectContentTab,
    media: ProjectMediaTab,
    units: ProjectUnitsTab,
    amenities: ProjectAmenitiesTab,
    progress: ProjectProgressTab,
    nearby: ProjectNearbyTab,
    documents: ProjectDocumentsTab,
    pricing: ProjectPricingTab,
    routing: ProjectRoutingTab,
    seo: ProjectSeoTab,
};

function onTabKey(event: KeyboardEvent, index: number): void {
    const last = tabs.value.length - 1;
    const next =
        { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: last }[
            event.key
        ] ?? null;

    if (next === null) {
        return;
    }

    event.preventDefault();
    const target = tabs.value[(next + last + 1) % (last + 1)];
    tab.value = target.key;
    void nextTick(() =>
        document.getElementById(`project-tab-${target.key}`)?.focus(),
    );
}

async function focusField(id: string): Promise<void> {
    await nextTick();

    const element = document.getElementById(id);
    const control = element?.matches('input, select, textarea, button')
        ? element
        : element?.querySelector<HTMLElement>(
              'input:not([type="hidden"]), select, textarea, button, [tabindex="0"]',
          );

    (control ?? element)?.scrollIntoView({ block: 'center' });
    (control ?? element)?.focus({ preventScroll: true });
}

/** An error summary link: show the tab that holds the field, then focus it. */
function reveal(id: string): void {
    const key = Object.keys(form.errors).find(
        (item) => fieldIdOfError(item) === id,
    );

    if (key) {
        tab.value = tabOfError(key) as TabKey;
    }

    void focusField(id);
}

/** A checklist "Fix" link (CMP-ADM-CHECKLIST). */
function fix(item: ChecklistItem): void {
    tab.value = item.tab as TabKey;
    void focusField(fieldIdOfError(item.field));
}

// --- save and verbs -----------------------------------------------------------------------------
const isDraft = computed(
    () => !props.project || props.project.status !== 'published',
);

function save(): void {
    const options = { preserveScroll: true };

    if (props.project) {
        form.patch(update.url(props.project.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function publishNow(): void {
    if (props.project) {
        router.post(
            publish.url(props.project.id),
            {},
            { preserveScroll: true },
        );
    }
}

function unpublishNow(): void {
    if (props.project) {
        router.post(
            unpublish.url(props.project.id),
            {},
            { preserveScroll: true },
        );
    }
}

function duplicateNow(): void {
    if (props.project) {
        router.post(duplicate.url(props.project.id));
    }
}

async function deleteNow(): Promise<void> {
    if (!props.project) {
        return;
    }

    const live = props.project.status === 'published';
    const answer = await confirm({
        title: 'Delete this project?',
        subject: props.project.name,
        description: live
            ? 'It leaves the website. Visitors get “Gone” for 90 days unless you send them to another page. The project moves to the Recycle bin and can be restored as a draft.'
            : 'The project moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete project',
        destructive: true,
        redirect: live
            ? { label: 'Redirect visitors to…', options: props.redirectOptions }
            : undefined,
    });

    if (answer) {
        router.delete(destroy.url(props.project.id), {
            data: { redirect_to: answer.redirect },
        });
    }
}

const title = computed(
    () => form.name.trim() || props.project?.name || 'New project',
);
</script>

<template>
    <Head :title="title" />

    <AdminEditor
        :title="title"
        :description="
            project
                ? 'Every tab saves together. Drafts stay hidden until you publish them.'
                : 'Fill in the basics and save: the project stays a draft until you publish it.'
        "
        :form="form"
        :last-saved="lastSaved"
        :save-label="isDraft ? 'Save draft' : 'Save changes'"
        :save-reason="
            can.update ? null : 'You can read this project but not change it.'
        "
        :field-id="fieldIdOfError"
        @save="save"
        @reveal="reveal"
    >
        <template #actions>
            <Link
                :href="projectsIndex.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All projects</Link
            >
        </template>

        <template #tabs>
            <div
                class="-mb-px flex gap-5 overflow-x-auto"
                role="tablist"
                aria-label="Project sections"
            >
                <button
                    v-for="(item, index) in tabs"
                    :id="`project-tab-${item.key}`"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.key"
                    :aria-controls="`project-panel-${item.key}`"
                    :tabindex="tab === item.key ? 0 : -1"
                    :class="
                        cn(
                            'inline-flex h-11 shrink-0 items-center gap-1.5 border-b-2 px-1 text-sm font-medium whitespace-nowrap transition-colors',
                            tab === item.key
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="tab = item.key"
                    @keydown="onTabKey($event, index)"
                >
                    {{ item.label }}
                    <ErrorBadge :count="tabErrors[item.key] ?? 0" />
                </button>
            </div>
        </template>

        <template #rail>
            <ProjectRail
                :project="project"
                :checklist="checklist"
                :dirty="form.isDirty"
                :can="can"
                :path="`/projects/${form.slug}`"
                @publish="publishNow"
                @unpublish="unpublishNow"
                @duplicate="duplicateNow"
                @delete="deleteNow"
                @fix="fix"
            />
        </template>

        <div class="grid max-w-[1100px] gap-6">
            <section
                v-for="item in tabs"
                v-show="tab === item.key"
                :id="`project-panel-${item.key}`"
                :key="item.key"
                role="tabpanel"
                :aria-labelledby="`project-tab-${item.key}`"
            >
                <component :is="components[item.key]" />
            </section>

            <!-- Below 1280 px the rail is hidden, so its verbs and the checklist follow the form. -->
            <div class="rounded-lg border bg-card p-4 xl:hidden">
                <ProjectRail
                    :project="project"
                    :checklist="checklist"
                    :dirty="form.isDirty"
                    :can="can"
                    :path="`/projects/${form.slug}`"
                    @publish="publishNow"
                    @unpublish="unpublishNow"
                    @duplicate="duplicateNow"
                    @delete="deleteNow"
                    @fix="fix"
                />
            </div>
        </div>
    </AdminEditor>
</template>
