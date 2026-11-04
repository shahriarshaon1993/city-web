const DATE_TIME = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Dhaka',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

/** `04 Nov 2026 · 14:35` in Dhaka time (admin dates are DD MMM YYYY, design/admin.md §12.3). */
export function formatDateTime(iso: string): string {
    return DATE_TIME.format(new Date(iso)).replace(',', ' ·');
}

/** `just now`, `5m ago`, `3h ago`, `2d ago`; older than 30 days falls back to the date. */
export function timeAgo(iso: string, now: number = Date.now()): string {
    const seconds = Math.max(
        0,
        Math.round((now - new Date(iso).getTime()) / 1000),
    );

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    return days <= 30 ? `${days}d ago` : formatDateTime(iso).split(' · ')[0];
}
