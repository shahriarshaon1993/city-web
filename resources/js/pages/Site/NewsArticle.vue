<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import NewsCard from '@/components/site/NewsCard.vue';
import NewsletterForm from '@/components/site/NewsletterForm.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import ShareRow from '@/components/site/ShareRow.vue';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/format';
import type { NewsArticlePageProps } from '@/types/news';

// PG-14 `/news/{slug}` (LAY-NEWS-DETAIL): a 768 px column with breadcrumb,
// "Category · date · read time" overline, light H1, lead and author; the 16:9
// cover in a 1040 px container (full-bleed on phones); prose at 68ch; projects
// mentioned, tags, share row and author; then on greige three related posts
// and the newsletter sign-up on white. The title is the only H1 and the body
// starts at H2 (FR-NEWS-005). The cover is the LCP image.
defineProps<NewsArticlePageProps>();
</script>

<template>
    <div>
        <article>
            <div class="mx-auto max-w-3xl px-4 pt-8 sm:px-6 lg:pt-12 xl:px-0">
                <Breadcrumbs :items="breadcrumbs" />

                <header v-reveal.hero class="mt-8 flex flex-col gap-6 lg:mt-12">
                    <p
                        data-hero-line
                        class="text-overline text-muted-foreground uppercase"
                    >
                        <Link
                            :href="article.category.url"
                            class="inline-flex min-h-6 items-center text-primary underline-offset-4 hover:underline"
                        >
                            {{ article.category.name }}
                        </Link>
                        <span aria-hidden="true"> · </span>
                        <time :datetime="article.published_at">{{
                            formatDate(article.published_at)
                        }}</time>
                        <span aria-hidden="true"> · </span>
                        {{ article.read_minutes }} min read
                    </p>
                    <h1 data-hero-line class="text-h1 text-balance">
                        {{ article.title }}
                    </h1>
                    <p
                        data-hero-line
                        class="max-w-prose text-lead text-pretty text-muted-foreground"
                    >
                        {{ article.excerpt }}
                    </p>
                    <p
                        data-hero-line
                        class="border-t border-border pt-4 text-sm text-muted-foreground"
                    >
                        {{ copy.by_label }}
                        <span class="font-medium text-foreground">{{
                            article.author.name
                        }}</span
                        ><template v-if="article.author.role">
                            , {{ article.author.role }}
                        </template>
                    </p>
                </header>
            </div>

            <div class="mx-auto mt-10 max-w-[65rem] sm:px-6 lg:mt-14 lg:px-8">
                <ResponsiveImage
                    :image="article.cover"
                    preset="article-cover"
                    priority
                    class="sm:rounded-xl"
                />
            </div>

            <div
                class="mx-auto mt-10 flex max-w-3xl flex-col gap-12 px-4 pb-(--section-md) sm:px-6 lg:mt-14 xl:px-0"
            >
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div class="rich-text max-w-[68ch]" v-html="article.body" />

                <section
                    v-if="article.projects.length > 0"
                    aria-labelledby="article-projects"
                    class="flex flex-col gap-6 border-t border-border pt-8"
                >
                    <h2
                        id="article-projects"
                        class="text-overline text-muted-foreground uppercase"
                    >
                        {{ copy.projects_heading }}
                    </h2>
                    <ul class="grid gap-x-6 gap-y-10 sm:grid-cols-2">
                        <li
                            v-for="project in article.projects"
                            :key="project.id"
                        >
                            <ProjectCard
                                :project="project"
                                variant="mini"
                                heading-as="h3"
                            />
                        </li>
                    </ul>
                </section>

                <div class="flex flex-col gap-8 border-t border-border pt-8">
                    <nav
                        v-if="article.tags.length > 0"
                        :aria-label="copy.tags_label"
                        class="flex flex-wrap items-center gap-x-5"
                    >
                        <span
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ copy.tags_label }}
                        </span>
                        <Link
                            v-for="tag in article.tags"
                            :key="tag.slug"
                            :href="tag.url"
                            class="inline-flex min-h-11 items-center text-body text-primary underline-offset-4 hover:underline"
                        >
                            {{ tag.name }}
                        </Link>
                    </nav>

                    <ShareRow :share="share" />
                </div>

                <aside
                    :aria-label="copy.by_label"
                    class="flex items-center gap-4 border-t border-border pt-8"
                >
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-medium text-secondary-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(article.author.name) }}
                    </span>
                    <p class="flex flex-col">
                        <span
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ copy.by_label }}
                        </span>
                        <span class="font-display text-h4">{{
                            article.author.name
                        }}</span>
                        <span
                            v-if="article.author.role"
                            class="text-sm text-muted-foreground"
                        >
                            {{ article.author.role }}
                        </span>
                    </p>
                </aside>
            </div>
        </article>

        <section
            v-if="related.length > 0"
            aria-labelledby="related-heading"
            class="bg-surface-subtle py-(--section-md)"
        >
            <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
                <BlockHeader
                    heading-tag="h2"
                    heading-id="related-heading"
                    :heading="copy.related_heading"
                    :view-all="{
                        label: copy.related_view_all,
                        url: '/news',
                        new_tab: false,
                    }"
                />
                <ul
                    v-reveal.stagger
                    class="grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
                >
                    <li v-for="post in related" :key="post.id">
                        <NewsCard :post="post" heading-as="h3" show-read-time />
                    </li>
                </ul>
            </div>
        </section>

        <section aria-labelledby="article-newsletter" class="py-(--section-md)">
            <div
                class="mx-auto grid max-w-content gap-8 px-4 sm:px-6 lg:grid-cols-12 lg:px-8 xl:gap-x-8 xl:px-10"
            >
                <div class="flex flex-col gap-3 lg:col-span-5">
                    <h2 id="article-newsletter" class="text-h2 text-balance">
                        {{ copy.newsletter_heading }}
                    </h2>
                    <p class="max-w-prose text-pretty text-muted-foreground">
                        {{ copy.newsletter_text }}
                    </p>
                </div>
                <div class="lg:col-span-6 lg:col-start-7">
                    <NewsletterForm location="article" />
                </div>
            </div>
        </section>
    </div>
</template>
