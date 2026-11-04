<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, onMounted, ref, useId } from 'vue';
import CtaBandBlock from '@/components/blocks/CtaBandBlock.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { FaqCategorySlug, FaqPageProps } from '@/types/faq';

// PG-18 / LAY-FAQ: light H1 + intro and the search box on a hairline; topics
// as a sticky vertical list in 3 columns (a sideways row below 1024 px) and
// the questions in 9, grouped by category with an H2 each; multi-open
// hairline accordions whose answers stay in the HTML (`hidden=until-found`).
// Topics act as tabs and the box filters as you type (owner 8.2); without
// JavaScript every group shows and the topics are jump links. Ends with the
// "Still have questions?" band.
const props = defineProps<FaqPageProps>();

const searchId = useId();
const active = ref<'all' | FaqCategorySlug>('all');
const query = ref('');

const words = computed(() =>
    query.value.trim().toLowerCase().split(/\s+/).filter(Boolean),
);

const haystacks = computed(
    () =>
        new Map(
            props.faq.items.map((item) => [
                item.id,
                `${item.question} ${item.answer.replace(/<[^>]+>/g, ' ')}`.toLowerCase(),
            ]),
        ),
);

const groups = computed(() =>
    props.faq.categories
        .filter(
            (category) =>
                active.value === 'all' || category.slug === active.value,
        )
        .map((category) => ({
            ...category,
            items: props.faq.items.filter(
                (item) =>
                    item.category === category.slug &&
                    words.value.every((word) =>
                        haystacks.value.get(item.id)?.includes(word),
                    ),
            ),
        }))
        .filter((group) => group.items.length > 0),
);

const shown = computed(() =>
    groups.value.reduce((sum, group) => sum + group.items.length, 0),
);

const countLabel = computed(() =>
    shown.value === 1
        ? props.copy.results_count_one
        : props.copy.results_count.replace('{n}', String(shown.value)),
);

const anchor = (slug: string) => `topic-${slug}`;

function select(slug: 'all' | FaqCategorySlug): void {
    active.value = slug;
    history.replaceState(
        history.state,
        '',
        slug === 'all' ? location.pathname : `#${anchor(slug)}`,
    );
}

onMounted(() => {
    const slug = location.hash.replace('#topic-', '');
    const match = props.faq.categories.find(
        (category) => category.slug === slug,
    );

    if (match) {
        active.value = match.slug;
    }
});

const itemClass =
    'relative inline-flex min-h-11 shrink-0 snap-start items-center gap-1.5 rounded-xs text-body whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

const topics = computed(() => [
    {
        slug: 'all' as const,
        label: props.copy.all_label,
        count: props.faq.items.length,
        href: '#faq-questions',
    },
    ...props.faq.categories.map((category) => ({
        ...category,
        href: `#${anchor(category.slug)}`,
    })),
]);
</script>

<template>
    <div>
        <div
            class="mx-auto w-full max-w-content px-4 pt-8 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
        >
            <Breadcrumbs :items="copy.breadcrumbs" />

            <header
                v-reveal.hero
                class="mt-8 grid gap-x-8 gap-y-6 border-b border-border pb-10 lg:mt-12 lg:grid-cols-12 lg:items-end lg:pb-14"
            >
                <h1 data-hero-line class="text-h1 text-balance lg:col-span-7">
                    {{ copy.title }}
                </h1>
                <div
                    data-hero-line
                    class="flex flex-col gap-5 lg:col-span-5 lg:col-start-8"
                >
                    <p
                        class="max-w-prose text-body-lg text-pretty text-muted-foreground"
                    >
                        {{ copy.intro }}
                    </p>
                    <div role="search" class="flex flex-col gap-2">
                        <label :for="searchId" class="text-sm font-medium">
                            {{ copy.search_label }}
                        </label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                :id="searchId"
                                v-model="query"
                                type="search"
                                maxlength="100"
                                autocomplete="off"
                                :placeholder="copy.search_placeholder"
                                aria-controls="faq-questions"
                                class="h-12 pl-11"
                            />
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-x-8 lg:grid-cols-12 xl:gap-x-10">
                <nav
                    aria-labelledby="faq-topics-label"
                    class="border-b border-border py-1 lg:sticky lg:top-28 lg:col-span-3 lg:self-start lg:border-b-0 lg:pt-(--section-sm)"
                >
                    <p
                        id="faq-topics-label"
                        class="sr-only text-overline text-muted-foreground uppercase lg:not-sr-only lg:mb-3"
                    >
                        {{ copy.topics_label }}
                    </p>
                    <ul
                        class="-my-1 flex snap-x [scrollbar-width:none] gap-5 overflow-x-auto py-1 lg:my-0 lg:flex-col lg:items-start lg:gap-0 lg:overflow-visible [&::-webkit-scrollbar]:hidden"
                    >
                        <li v-for="topic in topics" :key="topic.slug">
                            <a
                                :href="topic.href"
                                :aria-current="
                                    active === topic.slug ? 'true' : undefined
                                "
                                :class="
                                    cn(
                                        itemClass,
                                        active === topic.slug &&
                                            'text-foreground after:scale-x-100',
                                    )
                                "
                                @click.prevent="select(topic.slug)"
                            >
                                {{ topic.label }}
                                <span
                                    class="tabular text-sm text-muted-foreground"
                                >
                                    {{ topic.count }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div
                    id="faq-questions"
                    class="scroll-mt-28 lg:col-span-9 lg:col-start-4"
                >
                    <p class="sr-only" aria-live="polite">{{ countLabel }}</p>

                    <section
                        v-for="group in groups"
                        :id="anchor(group.slug)"
                        :key="group.slug"
                        :aria-labelledby="`${anchor(group.slug)}-heading`"
                        class="scroll-mt-28 pt-(--section-sm)"
                    >
                        <h2
                            :id="`${anchor(group.slug)}-heading`"
                            class="mb-4 font-display text-h3 text-balance"
                        >
                            {{ group.label }}
                        </h2>
                        <Accordion
                            type="multiple"
                            :unmount-on-hide="false"
                            class="border-t border-border"
                        >
                            <AccordionItem
                                v-for="item in group.items"
                                :key="item.id"
                                :value="String(item.id)"
                            >
                                <AccordionTrigger>
                                    {{ item.question }}
                                </AccordionTrigger>
                                <AccordionContent>
                                    <!-- eslint-disable-next-line vue/no-v-html -->
                                    <div
                                        class="rich-text max-w-prose pb-2 text-muted-foreground"
                                        v-html="item.answer"
                                    />
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </section>

                    <EmptyState
                        v-if="groups.length === 0"
                        variant="no-results"
                        :title="copy.empty_title.replace('{q}', query.trim())"
                        :body="copy.empty_body"
                        class="mt-(--section-sm)"
                    >
                        <div class="flex flex-wrap justify-center gap-3">
                            <Button
                                v-if="active !== 'all'"
                                variant="outline"
                                @click="select('all')"
                            >
                                {{ copy.empty_all_action }}
                            </Button>
                            <Button variant="link" @click="query = ''">
                                {{ copy.empty_clear_action }}
                            </Button>
                        </div>
                    </EmptyState>
                </div>
            </div>
        </div>

        <CtaBandBlock :block="cta" :first="false" />
    </div>
</template>
