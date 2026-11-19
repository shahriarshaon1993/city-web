/** Dashboard home (App\Queries\Admin\DashboardData, ADM-DASH). A widget is null when the role may not see it. */

export type DashboardRange = 7 | 30 | 90;

export type DashboardKpi = {
    key: string;
    label: string;
    icon: string;
    /** null = nothing to count yet: the card shows "—" and `helper` explains why (CMP-ADM-KPI). */
    value: string | null;
    helper: string | null;
    delta: { direction: 'up' | 'down' | 'flat'; text: string } | null;
    href: string | null;
};

export type DashboardQuickAction = {
    key: string;
    label: string;
    icon: string;
    href: string;
};

export type DashboardTrend = {
    days: number;
    total: number;
    /** `YYYY-MM-DD`, one per Dhaka day, oldest first. */
    dates: string[];
    series: {
        all: number[];
        types: { key: string; label: string; values: number[] }[];
    };
};

export type DashboardBar = {
    key: string;
    label: string;
    count: number;
    href: string | null;
};

export type DashboardFollowUp = {
    id: number;
    name: string;
    project: string | null;
    due_at: string;
    overdue: boolean;
    href: string;
};

export type DashboardEnquiry = {
    id: number;
    name: string;
    type: string;
    project: string | null;
    received_at: string;
    href: string;
};

export type DashboardActivity = {
    id: number;
    who: string | null;
    action: { label: string; tone: string };
    subject: string | null;
    created_at: string;
};

export type DashboardSeoHealth = {
    not_found: { path: string; hits: number }[];
    not_found_href: string;
    missing_meta: { pages: number; projects: number; articles: number };
    missing_alt: number;
    missing_alt_href: string | null;
    snapshot_misses: number | null;
};

export type GettingStartedStep = {
    key: string;
    label: string;
    hint: string;
    href: string;
    done: boolean;
};
