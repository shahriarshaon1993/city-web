<?php

namespace App\Support\Notifications;

use App\Notifications\ExportReadyNotification;
use App\Notifications\FollowUpsDueNotification;
use App\Notifications\ImportFinishedNotification;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\NewApplicationNotification;
use App\Notifications\NewLeadNotification;

/**
 * The admin notification types of spec/admin.md §10.8 that exist so far. The value is stored in the
 * notification's `kind` (the inbox filter reads the class); 18.x adds the rest.
 */
enum NotificationKind: string
{
    case NewLead = 'new_lead';
    case LeadAssigned = 'lead_assigned';
    case FollowUpsDue = 'follow_up_due';
    case NewApplication = 'new_application';
    case ImportFinished = 'import_finished';
    case ExportReady = 'export_ready';

    public function label(): string
    {
        return match ($this) {
            self::NewLead => 'New lead',
            self::LeadAssigned => 'Assigned to you',
            self::FollowUpsDue => 'Follow-ups due',
            self::NewApplication => 'New application',
            self::ImportFinished => 'Import finished',
            self::ExportReady => 'Export ready',
        };
    }

    /**
     * @return class-string
     */
    public function notificationClass(): string
    {
        return match ($this) {
            self::NewLead => NewLeadNotification::class,
            self::LeadAssigned => LeadAssignedNotification::class,
            self::FollowUpsDue => FollowUpsDueNotification::class,
            self::NewApplication => NewApplicationNotification::class,
            self::ImportFinished => ImportFinishedNotification::class,
            self::ExportReady => ExportReadyNotification::class,
        };
    }

    /** The lucide icon the inbox shows (kebab-case, resolved on the client). */
    public function icon(): string
    {
        return match ($this) {
            self::NewLead => 'phone-call',
            self::LeadAssigned => 'user-check',
            self::FollowUpsDue => 'calendar-clock',
            self::NewApplication => 'file-user',
            self::ImportFinished => 'file-up',
            self::ExportReady => 'file-down',
        };
    }

    public static function fromClass(string $class): ?self
    {
        foreach (self::cases() as $kind) {
            if ($kind->notificationClass() === $class) {
                return $kind;
            }
        }

        return null;
    }
}
