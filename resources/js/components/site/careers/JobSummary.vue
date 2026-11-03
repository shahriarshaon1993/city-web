<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useId } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { CareersCopy, JobPost } from '@/types/careers';

// PG-16 summary (LAY-CAREER-DETAIL's "summary card" as the greige action
// panel of direction.md §6, not a card): deadline with the countdown text,
// a hairline <dl> of the role facts and Apply now (teal: the header keeps
// the coral invitation, DP-04). A closed role says "Applications closed"
// and links to the open roles instead (FR-CAR-003).
const props = defineProps<{
    job: JobPost;
    copy: CareersCopy;
}>();

const headingId = useId();

const facts = computed(() =>
    [
        { term: props.copy.department_term, value: props.job.department },
        { term: props.copy.location_term, value: props.job.location },
        { term: props.copy.type_term, value: props.job.employment_type_label },
        { term: props.copy.experience_term, value: props.job.experience },
        { term: props.copy.salary_term, value: props.job.salary_text },
    ].filter((fact) => fact.value),
);
</script>

<template>
    <aside
        :aria-labelledby="headingId"
        class="rounded-xl bg-surface-subtle p-6 xl:p-8"
    >
        <template v-if="job.is_open">
            <p :id="headingId" class="font-display text-h4">
                {{ copy.summary_heading }}
            </p>
            <div
                class="mt-5 flex flex-col gap-1 border-t border-foreground/10 pt-5"
            >
                <span class="text-overline text-muted-foreground uppercase">
                    {{ copy.apply_by_label }}
                </span>
                <time
                    :datetime="job.deadline"
                    class="tabular font-display text-price"
                >
                    {{ formatDate(job.deadline) }}
                </time>
                <span v-if="job.closes_label" class="text-sm text-primary">
                    {{ job.closes_label }}
                </span>
            </div>
        </template>
        <template v-else>
            <p :id="headingId" class="font-display text-h4">
                {{ copy.closed_title }}
            </p>
            <p
                v-if="job.closed_at"
                class="mt-3 text-sm text-pretty text-muted-foreground"
            >
                {{
                    copy.closed_text.replace(
                        '{date}',
                        formatDate(job.closed_at),
                    )
                }}
            </p>
        </template>

        <dl
            class="mt-5 divide-y divide-foreground/10 border-y border-foreground/10"
        >
            <div
                v-for="fact in facts"
                :key="fact.term"
                class="flex flex-col gap-1 py-3"
            >
                <dt class="text-overline text-muted-foreground uppercase">
                    {{ fact.term }}
                </dt>
                <dd class="text-body">{{ fact.value }}</dd>
            </div>
        </dl>

        <a
            v-if="job.is_open"
            href="#apply"
            :class="
                cn(
                    buttonVariants({ variant: 'primary', size: 'lg' }),
                    'mt-6 w-full',
                )
            "
        >
            {{ copy.apply_now }}
        </a>
        <Link
            v-else
            href="/careers"
            :class="
                cn(
                    buttonVariants({ variant: 'outline', size: 'lg' }),
                    'mt-6 w-full',
                )
            "
        >
            {{ copy.closed_action }}
        </Link>
    </aside>
</template>
