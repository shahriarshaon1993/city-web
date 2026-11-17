<?php

namespace App\Support\Notifications;

use Illuminate\Notifications\DatabaseNotification;

/** The shape the bell and the inbox read (`AdminNotification` in types/notifications.ts). */
final class NotificationPresenter
{
    /**
     * @return array{id: string, kind: string, kind_label: string, icon: string, title: string, body: string|null, url: string|null, read: bool, created_at: string|null}
     */
    public static function row(DatabaseNotification $notification): array
    {
        $kind = NotificationKind::fromClass($notification->type);
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'kind' => $kind->value ?? 'other',
            'kind_label' => $kind?->label() ?? 'Notification',
            'icon' => $kind?->icon() ?? 'bell',
            'title' => is_string($data['title'] ?? null) ? $data['title'] : 'Notification',
            'body' => is_string($data['body'] ?? null) ? $data['body'] : null,
            // Only paths on this site: a stored link can never send the admin elsewhere.
            'url' => is_string($data['url'] ?? null) && str_starts_with($data['url'], '/') && ! str_starts_with($data['url'], '//') ? $data['url'] : null,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
