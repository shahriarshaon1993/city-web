<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { defineAsyncComponent, h } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import BarList from '@/components/admin/dashboard/BarList.vue';
import DashboardWidget from '@/components/admin/dashboard/DashboardWidget.vue';
import GettingStartedCard from '@/components/admin/dashboard/GettingStartedCard.vue';
import KpiCard from '@/components/admin/dashboard/KpiCard.vue';
import RangeToggle from '@/components/admin/dashboard/RangeToggle.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { adminIcon } from '@/lib/admin-icons';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { dashboard } from '@/routes/admin';
import { index as activityLog } from '@/routes/admin/activity-log';
import { index as leadsIndex } from '@/routes/admin/leads';
import { index as notFoundIndex } from '@/routes/admin/not-found';
import type {
    DashboardActivity,
    DashboardBar,
    DashboardEnquiry,
    DashboardFollowUp,
    DashboardKpi,
    DashboardQuickAction,
    DashboardRange,
    DashboardSeoHealth,
    DashboardTrend,
    GettingStartedStep,
} from '@/types/admin-dashboard';

// ADM-DASH (design/admin.md §12.2). Every widget prop is null when the role may not see it, so
// the page only draws what the server sent.
const props = defineProps<{
    ranges: {
        trend: DashboardRange;
        projects: DashboardRange;
        sources: DashboardRange;
    };
    kpis: DashboardKpi[];
    quickActions: DashboardQuickAction[];
    trend: DashboardTrend | null;
    pipeline: DashboardBar[] | null;
    leadsByProject: DashboardBar[] | null;
    leadSources: DashboardBar[] | null;
    unreadEnquiries: DashboardEnquiry[] | null;
    followUps: DashboardFollowUp[] | null;
    recentActivity: DashboardActivity[] | null;
    seoHealth: DashboardSeoHealth | null;
    gettingStarted: { steps: GettingStartedStep[] } | null;
}>();

// Unovis (~200 KB) loads only when the dashboard is opened, never with the other admin pages.
const LeadsTrendChart = defineAsyncComponent({
    loader: () => import('@/components/admin/dashboard/LeadsTrendChart.vue'),
    loadingComponent: { render: () => h(Skeleton, { class: 'h-64 w-full' }) },
    delay: 0,
});

const page = usePage();
const firstName = page.props.auth.user.name.split(/\s+/u)[0];

// Each chart widget changes its own range; a partial reload leaves every other widget untouched.
function setRange(
    key: 'trend' | 'projects' | 'sources',
    value: DashboardRange,
): void {
    router.get(
        dashboard.url(),
        { ...props.ranges, [key]: value },
        {
            only: [
                'ranges',
                key === 'trend'
                    ? 'trend'
                    : key === 'projects'
                      ? 'leadsByProject'
                      : 'leadSources',
            ],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

const leadsUrl = leadsIndex.url();
const followUpsUrl = leadsIndex.url({
    query: { filter: { assignee: 'me', follow_up: 'overdue,today' } },
});
const unreadUrl = leadsIndex.url({ query: { filter: { status: 'new' } } });

const plural = (count: number, noun: string): string =>
    `${count.toLocaleString('en-GB')} ${noun}${count === 1 ? '' : 's'}`;

const total = (rows: DashboardBar[]): number =>
    rows.reduce((sum, row) => sum + row.count, 0);
</script>

<template>
    <Head title="Dashboard" />

    <AdminPageHeader
        title="Dashboard"
        :description="`Welcome back, ${firstName}. Here is how the site is doing.`"
    />

    <div class="space-y-6">
        <GettingStartedCard
            v-if="gettingStarted"
            :steps="gettingStarted.steps"
        />

        <!-- KPI cards: 4 × 2, 2 × 4, 1 column -->
        <section
            v-if="kpis.length"
            aria-label="Key numbers"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        >
            <KpiCard v-for="kpi in kpis" :key="kpi.key" :kpi="kpi" />
        </section>

        <!-- CMP-ADM-QUICK -->
        <section v-if="quickActions.length" aria-labelledby="quick-heading">
            <h2 id="quick-heading" class="sr-only">Quick actions</h2>
            <ul class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <li v-for="action in quickActions" :key="action.key">
                    <Link
                        :href="action.href"
                        class="flex h-18 items-center gap-3 rounded-xl border bg-card px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                        >
                            <component
                                :is="adminIcon(action.icon)"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </span>
                        {{ action.label }}
                    </Link>
                </li>
            </ul>
        </section>

        <!-- 8 / 4 split -->
        <div
            v-if="trend || unreadEnquiries || followUps"
            class="grid gap-6 xl:grid-cols-12"
        >
            <div
                v-if="trend && pipeline"
                class="flex min-w-0 flex-col gap-6 xl:col-span-8"
            >
                <DashboardWidget title="Leads trend" :view-all-href="leadsUrl">
                    <template #filter>
                        <RangeToggle
                            :model-value="ranges.trend"
                            label="Leads trend period"
                            @update:model-value="setRange('trend', $event)"
                        />
                    </template>
                    <p
                        v-if="trend.total === 0"
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        No leads in the last {{ trend.days }} days.
                    </p>
                    <LeadsTrendChart v-else :trend="trend" />
                </DashboardWidget>

                <DashboardWidget title="Pipeline" :view-all-href="leadsUrl">
                    <p
                        v-if="total(pipeline) === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No leads in the pipeline yet.
                    </p>
                    <BarList v-else :rows="pipeline" />
                </DashboardWidget>
            </div>

            <div
                v-if="unreadEnquiries || followUps"
                class="flex min-w-0 flex-col gap-6 xl:col-span-4"
            >
                <DashboardWidget
                    v-if="unreadEnquiries"
                    title="Unread enquiries"
                    :view-all-href="unreadUrl"
                >
                    <p
                        v-if="unreadEnquiries.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Every enquiry has been opened.
                    </p>
                    <ul v-else class="divide-y border-y">
                        <li
                            v-for="enquiry in unreadEnquiries"
                            :key="enquiry.id"
                        >
                            <Link
                                :href="enquiry.href"
                                class="flex min-h-14 items-center justify-between gap-3 py-2 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium"
                                    >
                                        {{ enquiry.name }}
                                    </span>
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                    >
                                        {{ enquiry.type
                                        }}<template v-if="enquiry.project">
                                            · {{ enquiry.project }}</template
                                        >
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span
                                        class="text-xs text-muted-foreground"
                                        :title="
                                            formatDateTime(enquiry.received_at)
                                        "
                                    >
                                        {{ timeAgo(enquiry.received_at) }}
                                    </span>
                                    <span
                                        class="text-sm font-medium text-primary"
                                        >Open</span
                                    >
                                </span>
                            </Link>
                        </li>
                    </ul>
                </DashboardWidget>

                <DashboardWidget
                    v-if="followUps"
                    title="My follow-ups"
                    :view-all-href="followUpsUrl"
                >
                    <p
                        v-if="followUps.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Nothing is due. Follow-ups you set on your leads show up
                        here on the day.
                    </p>
                    <ul v-else class="divide-y border-y">
                        <li v-for="lead in followUps" :key="lead.id">
                            <Link
                                :href="lead.href"
                                class="flex min-h-14 items-center justify-between gap-3 py-2 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium"
                                    >
                                        {{ lead.name }}
                                    </span>
                                    <span
                                        v-if="lead.project"
                                        class="block truncate text-xs text-muted-foreground"
                                    >
                                        {{ lead.project }}
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 text-xs"
                                    :class="
                                        lead.overdue
                                            ? 'font-medium text-destructive'
                                            : 'text-muted-foreground'
                                    "
                                    :title="formatDateTime(lead.due_at)"
                                >
                                    {{ lead.overdue ? 'Overdue' : 'Due today' }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </DashboardWidget>
            </div>
        </div>

        <!-- 6 / 6 -->
        <div
            v-if="leadsByProject && leadSources"
            class="grid gap-6 xl:grid-cols-2"
        >
            <DashboardWidget title="Leads by project" :view-all-href="leadsUrl">
                <template #filter>
                    <RangeToggle
                        :model-value="ranges.projects"
                        label="Leads by project period"
                        @update:model-value="setRange('projects', $event)"
                    />
                </template>
                <p
                    v-if="leadsByProject.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No project enquiries in the last {{ ranges.projects }} days.
                </p>
                <BarList v-else :rows="leadsByProject" />
            </DashboardWidget>

            <DashboardWidget title="Lead sources">
                <template #filter>
                    <RangeToggle
                        :model-value="ranges.sources"
                        label="Lead sources period"
                        @update:model-value="setRange('sources', $event)"
                    />
                </template>
                <p
                    v-if="leadSources.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No leads in the last {{ ranges.sources }} days.
                </p>
                <BarList v-else :rows="leadSources" />
            </DashboardWidget>
        </div>

        <!-- 8 / 4 -->
        <div
            v-if="recentActivity || seoHealth"
            class="grid gap-6 xl:grid-cols-12"
        >
            <DashboardWidget
                v-if="recentActivity"
                title="Recent activity"
                :view-all-href="activityLog.url()"
                class="xl:col-span-8"
            >
                <p
                    v-if="recentActivity.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Changes made in the admin are listed here.
                </p>
                <ul v-else class="divide-y border-y">
                    <li
                        v-for="entry in recentActivity"
                        :key="entry.id"
                        class="flex min-h-12 flex-wrap items-center gap-x-3 gap-y-1 py-2 text-sm"
                    >
                        <StatusBadge
                            :label="entry.action.label"
                            :tone="entry.action.tone"
                        />
                        <span class="min-w-0 flex-1 truncate">
                            <span class="font-medium">{{
                                entry.who ?? 'System'
                            }}</span>
                            <template v-if="entry.subject">
                                <span class="text-muted-foreground"> · </span>
                                {{ entry.subject }}
                            </template>
                        </span>
                        <span
                            class="shrink-0 text-xs text-muted-foreground"
                            :title="formatDateTime(entry.created_at)"
                        >
                            {{ timeAgo(entry.created_at) }}
                        </span>
                    </li>
                </ul>
            </DashboardWidget>

            <DashboardWidget
                v-if="seoHealth"
                title="SEO health"
                class="xl:col-span-4"
            >
                <div class="space-y-5 text-sm">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <h3
                                class="text-overline tracking-[0.16em] text-muted-foreground uppercase"
                            >
                                Top 404s, 7 days
                            </h3>
                            <Link
                                :href="notFoundIndex.url()"
                                class="text-xs font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Open monitor
                            </Link>
                        </div>
                        <p
                            v-if="seoHealth.not_found.length === 0"
                            class="mt-2 text-muted-foreground"
                        >
                            No missing addresses this week.
                        </p>
                        <ul v-else class="mt-2 divide-y border-y">
                            <li
                                v-for="row in seoHealth.not_found"
                                :key="row.path"
                                class="flex min-h-10 items-center justify-between gap-3 py-1.5"
                            >
                                <code class="truncate font-mono text-xs">{{
                                    row.path
                                }}</code>
                                <span
                                    class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ row.hits }}
                                    {{ row.hits === 1 ? 'visit' : 'visits' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <dl class="divide-y border-y">
                        <div
                            class="flex min-h-11 items-center justify-between gap-3 py-2"
                        >
                            <dt>Without a meta description</dt>
                            <dd class="text-right text-muted-foreground">
                                <template
                                    v-if="
                                        seoHealth.missing_meta.pages +
                                            seoHealth.missing_meta.projects +
                                            seoHealth.missing_meta.articles ===
                                        0
                                    "
                                >
                                    None
                                </template>
                                <template v-else>
                                    {{
                                        plural(
                                            seoHealth.missing_meta.pages,
                                            'page',
                                        )
                                    }}
                                    ·
                                    {{
                                        plural(
                                            seoHealth.missing_meta.projects,
                                            'project',
                                        )
                                    }}
                                    ·
                                    {{
                                        plural(
                                            seoHealth.missing_meta.articles,
                                            'article',
                                        )
                                    }}
                                </template>
                            </dd>
                        </div>
                        <div
                            class="flex min-h-11 items-center justify-between gap-3 py-2"
                        >
                            <dt>Images without alt text</dt>
                            <dd class="text-right">
                                <span
                                    v-if="seoHealth.missing_alt === 0"
                                    class="text-muted-foreground"
                                    >None</span
                                >
                                <Link
                                    v-else-if="seoHealth.missing_alt_href"
                                    :href="seoHealth.missing_alt_href"
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ seoHealth.missing_alt }}
                                </Link>
                                <span v-else>{{ seoHealth.missing_alt }}</span>
                            </dd>
                        </div>
                        <div
                            class="flex min-h-11 items-center justify-between gap-3 py-2"
                        >
                            <dt>Snapshot misses</dt>
                            <dd class="text-muted-foreground">
                                {{
                                    seoHealth.snapshot_misses === null
                                        ? 'Not switched on yet'
                                        : seoHealth.snapshot_misses
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </DashboardWidget>
        </div>
    </div>
</template>
