/** One admin notification (App\Support\Notifications\NotificationPresenter::row). */
export type AdminNotification = {
    id: string;
    kind: string;
    kind_label: string;
    icon: string;
    title: string;
    body: string | null;
    /** A path on this site, or null. */
    url: string | null;
    read: boolean;
    created_at: string | null;
};

export type NotificationFeed = {
    unread: number;
    items: AdminNotification[];
};

export type NotificationKindOption = { value: string; label: string };

export type NotificationsMeta = {
    page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_url: string | null;
    next_url: string | null;
};

/** One row of the webhook delivery log under Settings → Webhook (FR-LEAD-012). */
export type WebhookDeliveryRow = {
    id: number;
    event: string;
    lead: { id: number; name: string } | null;
    state: 'delivered' | 'queued' | 'retrying' | 'failed' | 'skipped';
    status_code: number | null;
    attempts: number;
    response_excerpt: string | null;
    created_at: string | null;
};

/** Summary band + filter counts of the inbox (NotificationController::stats). */
export type NotificationStats = {
    total: number;
    today: number;
    kinds: Record<string, { total: number; unread: number }>;
};
