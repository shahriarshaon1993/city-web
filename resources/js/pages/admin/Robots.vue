<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Info, RotateCcw, TriangleAlert } from '@lucide/vue';
import { computed, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import SeoTabs from '@/components/admin/seo/SeoTabs.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { update } from '@/routes/admin/robots';

// ADM-SCR-14 Robots.txt (SEO-TEC-010): the editor with a live preview of what the file will say.
// Outside production the site always serves "Disallow: /", whatever is saved here.
const props = defineProps<{
    body: string;
    defaultBody: string;
    isCustom: boolean;
    served: string;
    blocksEverything: boolean;
    environment: string;
    publicUrl: string;
    canManage: boolean;
    lastSaved: string | null;
}>();

const MAX = 5000;

const form = useForm({ body: props.body });
const confirm = useConfirm();

watch(
    () => props.body,
    (body) => {
        form.defaults({ body });
        form.reset();
    },
);

// A draft that disallows "/" for every crawler hides the whole site from search engines.
const blocksSite = computed(() => /^\s*Disallow:\s*\/\s*$/im.test(form.body));
const length = computed(() => form.body.length);

function save(): void {
    form.put(update.url(), { preserveScroll: true });
}

async function reset(): Promise<void> {
    const answer = await confirm({
        title: 'Go back to the default robots.txt?',
        description:
            'Your saved version is replaced by the default (admin, search, shortlist, compare, thank-you, preview and internal pages are disallowed, and the sitemap is listed).',
        confirmLabel: 'Use the default',
        destructive: true,
    });

    if (answer) {
        router.put(update.url(), { body: '' }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Robots.txt" />

    <AdminEditor
        title="Robots.txt"
        description="Tells search engines which parts of the site they may crawl. Served at /robots.txt."
        :form="form"
        :last-saved="lastSaved"
        save-label="Save robots.txt"
        :save-reason="
            canManage ? null : 'You can read robots.txt but not change it.'
        "
        :field-id="() => 'robots-body'"
        @save="save"
    >
        <template #tabs>
            <SeoTabs current="robots" />
        </template>

        <template #rail>
            <div class="grid gap-2 border-t pt-4">
                <Button
                    v-if="canManage"
                    type="button"
                    variant="outline"
                    class="w-full"
                    :disabled="!isCustom"
                    :title="
                        isCustom ? undefined : 'The default is already in use.'
                    "
                    @click="reset"
                >
                    <RotateCcw aria-hidden="true" />
                    Use the default
                </Button>
                <Button as-child variant="ghost" class="w-full">
                    <a :href="publicUrl" target="_blank" rel="noopener">
                        <ExternalLink aria-hidden="true" />
                        Open /robots.txt
                    </a>
                </Button>
            </div>
        </template>

        <div class="flex max-w-[1100px] flex-col gap-6">
            <div
                v-if="blocksEverything"
                role="note"
                class="flex gap-3 rounded-lg border border-info-200 bg-info-50 p-4 text-sm text-info-700"
            >
                <Info class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <p>
                    This copy of the site runs as
                    <strong>{{ environment }}</strong
                    >, not production, so /robots.txt always says
                    <code>Disallow: /</code> here and every page sends
                    <code>noindex</code>. What you save below is used on the
                    live site.
                </p>
            </div>

            <AdminSection
                title="File contents"
                description="One directive per line: User-agent, Disallow, Allow and Sitemap."
            >
                <div class="grid gap-2">
                    <Label for="robots-body">robots.txt</Label>
                    <Textarea
                        id="robots-body"
                        v-model="form.body"
                        rows="14"
                        :maxlength="MAX"
                        spellcheck="false"
                        autocapitalize="off"
                        autocomplete="off"
                        wrap="off"
                        class="font-mono text-[13px]"
                        aria-describedby="robots-body-help"
                        :readonly="!canManage"
                        :aria-invalid="!!form.errors.body || undefined"
                    />
                    <div class="flex items-start justify-between gap-4">
                        <FieldHelp id="robots-body">
                            Keep the Sitemap line so crawlers find /sitemap.xml.
                            The admin, search, shortlist, compare, thank-you,
                            preview and internal paths are disallowed by
                            default.
                        </FieldHelp>
                        <p
                            class="shrink-0 text-xs tabular-nums"
                            :class="
                                length >= MAX * 0.95
                                    ? 'text-warning'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ length.toLocaleString('en-GB') }} /
                            {{ MAX.toLocaleString('en-GB') }}
                        </p>
                    </div>
                    <InputError :message="form.errors.body" />
                    <div
                        v-if="blocksSite"
                        role="alert"
                        class="flex gap-3 rounded-lg border border-warning-200 bg-warning-50 p-3 text-sm text-warning-700"
                    >
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <p>
                            <code>Disallow: /</code> hides the whole site from
                            search engines. Keep it only while the site is not
                            ready to be found.
                        </p>
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Preview"
                description="What visitors and crawlers get from /robots.txt."
            >
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <h3 class="text-sm font-medium">
                            On the live site (after you save)
                        </h3>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/50 p-3 font-mono text-[13px] leading-6"
                            aria-label="Preview of robots.txt on the live site"
                            >{{
                                form.body.trim() === ''
                                    ? defaultBody
                                    : form.body
                            }}</pre>
                    </div>
                    <div v-if="blocksEverything" class="grid gap-2">
                        <h3 class="text-sm font-medium">
                            On this copy ({{ environment }}) right now
                        </h3>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/50 p-3 font-mono text-[13px] leading-6"
                            aria-label="robots.txt served on this copy"
                            >{{ served }}</pre>
                    </div>
                </div>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
