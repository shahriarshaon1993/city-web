<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { useId } from 'vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MinimalLayout from '@/layouts/MinimalLayout.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { ErrorPageProps } from '@/types/site';

// PG-26 / LAY-ERROR, rendered by App\Support\Errors\ErrorPage with the real
// status code. 500/503 use the logo-only layout. Popular projects are
// CMP-CARD-01 `mini` cards.
defineOptions({
    layout: (props: ErrorPageProps) =>
        props.minimal ? MinimalLayout : SiteLayout,
});

defineProps<ErrorPageProps>();

const page = usePage();
const searchId = useId();
const popularId = useId();
</script>

<template>
    <div
        class="mx-auto flex max-w-content flex-col items-center gap-16 px-4 py-16 sm:px-6 lg:px-8 lg:py-24 xl:px-10"
    >
        <div
            class="flex min-h-[50svh] w-full max-w-2xl flex-col items-center justify-center gap-5 text-center"
        >
            <!-- Decorative: the H1 carries the meaning. -->
            <p
                aria-hidden="true"
                class="tabular font-display text-display font-extrabold text-brand-accent-decor"
            >
                {{ status }}
            </p>
            <h1 class="text-h1">{{ title }}</h1>
            <p class="text-lead text-muted-foreground">{{ message }}</p>
            <p v-if="retry_text" class="font-semibold">{{ retry_text }}</p>

            <form
                v-if="search"
                role="search"
                method="get"
                :action="search.action"
                class="mt-2 flex w-full max-w-lg flex-col gap-2 text-left"
            >
                <Label :for="searchId">Search the website</Label>
                <div class="flex gap-3">
                    <Input
                        :id="searchId"
                        type="search"
                        :name="search.name"
                        :minlength="search.min_length"
                        maxlength="100"
                        enterkeyhint="search"
                        required
                        class="h-12 flex-1"
                    />
                    <Button type="submit" size="lg">
                        <template #icon>
                            <Search class="size-5" aria-hidden="true" />
                        </template>
                        Search
                    </Button>
                </div>
            </form>

            <div class="mt-2 flex flex-wrap justify-center gap-3">
                <Button v-if="status === 503" as="a" :href="page.url">
                    Try again
                </Button>
                <Button v-else-if="minimal" as="a" href="/">
                    Back to home
                </Button>
                <Button
                    v-else
                    as-child
                    :variant="search ? 'outline' : 'primary'"
                >
                    <Link href="/">Back to home</Link>
                </Button>
            </div>
        </div>

        <section
            v-if="popular && popular.projects.length > 0"
            :aria-labelledby="popularId"
            class="flex w-full flex-col gap-6"
        >
            <h2 :id="popularId" class="text-h3">{{ popular.heading }}</h2>
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="project in popular.projects" :key="project.id">
                    <ProjectCard :project="project" variant="mini" />
                </li>
            </ul>
        </section>
    </div>
</template>
