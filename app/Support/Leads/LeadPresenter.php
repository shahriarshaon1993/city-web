<?php

namespace App\Support\Leads;

use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Support\Forms\FormOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * What the admin screens show of a lead, in plain words. One place so the table, the board, the
 * drawer and the export cannot disagree — and the one place that decides who may see a phone number.
 */
final class LeadPresenter
{
    /**
     * Phone, WhatsApp and email as the actor may see them (`leads.view-contact`, otherwise masked).
     *
     * @return array{phone: string, whatsapp: string|null, email: string|null, contact_visible: bool}
     */
    public static function contact(Lead $lead, User $actor): array
    {
        if ($actor->can('viewContact', Lead::class)) {
            return ['phone' => $lead->phone, 'whatsapp' => $lead->whatsapp, 'email' => $lead->email, 'contact_visible' => true];
        }

        return [
            'phone' => ContactMask::phone($lead->phone),
            'whatsapp' => $lead->whatsapp === null ? null : ContactMask::phone($lead->whatsapp),
            'email' => ContactMask::email($lead->email),
            'contact_visible' => false,
        ];
    }

    /**
     * FR-LEAD-009 click-to-call and WhatsApp (`https://wa.me/{number}?text=`), email. Only built for a
     * user who may see the real number.
     *
     * @return array{call: string|null, whatsapp: string|null, email: string|null}
     */
    public static function links(Lead $lead, User $actor): array
    {
        if (! $actor->can('viewContact', Lead::class)) {
            return ['call' => null, 'whatsapp' => null, 'email' => null];
        }

        $number = ltrim($lead->whatsapp ?? $lead->phone, '+');
        $greeting = $lead->project !== null
            ? "Hello {$lead->name}, this is Gemcon City about {$lead->project->name}."
            : "Hello {$lead->name}, this is Gemcon City.";

        return [
            'call' => 'tel:'.$lead->phone,
            'whatsapp' => 'https://wa.me/'.$number.'?text='.rawurlencode($greeting),
            'email' => $lead->email === null ? null : 'mailto:'.$lead->email,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function answers(Lead $lead): array
    {
        $lists = ['timeline' => 'timeline', 'purpose' => 'purpose', 'contact_time' => 'contact_time', 'visit_slot' => 'contact_time', 'subject' => 'subject', 'ownership' => 'ownership', 'plot_facing' => 'facing', 'budget_band' => 'budget'];
        $labels = [
            'timeline' => 'Purchase timeline',
            'purpose' => 'Purpose',
            'contact_time' => 'Best time to call',
            'visit_date' => 'Preferred visit date',
            'visit_slot' => 'Preferred visit time',
            'visitors' => 'Visitors',
            'subject' => 'Subject',
            'land_location' => 'Land location',
            'land_size_katha' => 'Land size (katha)',
            'road_width_ft' => 'Road width (ft)',
            'plot_facing' => 'Plot facing',
            'ownership' => 'Ownership',
            'budget_band' => 'Budget',
            'channel' => 'Channel',
        ];

        $rows = [];

        foreach ($lead->answers ?? [] as $key => $value) {
            if (! is_scalar($value) || (string) $value === '') {
                continue;
            }

            $value = (string) $value;
            $shown = match (true) {
                $key === 'channel' => LeadReasons::CHANNELS[$value] ?? $value,
                isset($lists[$key]) => FormOptions::label($lists[$key], $value) ?? Str::headline($value),
                default => $value,
            };

            $rows[] = ['label' => $labels[$key] ?? Str::headline((string) $key), 'value' => $shown];
        }

        return $rows;
    }

    /**
     * One line of the timeline as plain data: `title` says what happened, `detail` and `html` add
     * to it, `by` is the team member (null = the visitor or the system).
     *
     * @return array{id: int, kind: string, title: string, detail: string|null, html: string|null, by: string|null, at: string|null}
     */
    public static function activity(LeadActivity $activity): array
    {
        $payload = $activity->payload ?? [];
        $detail = null;
        $html = null;

        $title = match ($activity->kind) {
            LeadActivityKind::Submission => self::submissionTitle($payload, $detail),
            LeadActivityKind::Touch => self::touchTitle($payload, $detail),
            LeadActivityKind::Status => self::statusTitle($payload, $detail),
            LeadActivityKind::Assignment => self::assignmentTitle($payload, $detail),
            LeadActivityKind::Note => 'Note added',
            LeadActivityKind::FollowUp => self::followUpTitle($payload),
            LeadActivityKind::Email => 'Email sent',
        };

        if ($activity->kind === LeadActivityKind::Note) {
            $html = is_string($payload['html'] ?? null) ? $payload['html'] : null;
        }

        return [
            'id' => $activity->id,
            'kind' => $activity->kind->value,
            'title' => $title,
            'detail' => $detail,
            'html' => $html,
            'by' => $activity->user?->name,
            'at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function submissionTitle(array $payload, ?string &$detail): string
    {
        $type = is_string($payload['type'] ?? null) ? LeadTypes::label($payload['type']) : 'Enquiry';
        $notes = array_filter([
            isset($payload['channel']) && is_string($payload['channel']) ? 'Channel: '.$payload['channel'] : null,
            isset($payload['spam_reason']) && is_string($payload['spam_reason']) ? 'Flagged as spam: '.$payload['spam_reason'] : null,
            ($payload['turnstile'] ?? null) === 'unavailable' ? 'The spam check could not run, so a person should look at it.' : null,
        ]);
        $detail = $notes === [] ? null : implode(' ', $notes);

        return match ($payload['type'] ?? null) {
            Lead::MANUAL_TYPE => 'Added by hand',
            Lead::IMPORT_TYPE => 'Imported from a file',
            default => "{$type} form submitted",
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function touchTitle(array $payload, ?string &$detail): string
    {
        $type = is_string($payload['type'] ?? null) ? LeadTypes::label($payload['type']) : 'Enquiry';
        $detail = is_string($payload['message'] ?? null) ? $payload['message'] : null;

        return "Asked again ({$type})";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function statusTitle(array $payload, ?string &$detail): string
    {
        $from = LeadStatus::tryFrom((string) ($payload['from'] ?? ''))?->label() ?? '—';
        $to = LeadStatus::tryFrom((string) ($payload['to'] ?? ''))?->label() ?? '—';
        $detail = implode(' · ', array_filter([
            is_string($payload['reason'] ?? null) ? $payload['reason'] : null,
            is_string($payload['note'] ?? null) ? $payload['note'] : null,
        ])) ?: null;

        return ($payload['undo'] ?? false) ? "Status change undone: back to {$to}" : "Status changed from {$from} to {$to}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function assignmentTitle(array $payload, ?string &$detail): string
    {
        $name = is_string($payload['name'] ?? null) ? $payload['name'] : null;
        $detail = match ($payload['rule'] ?? null) {
            'project_default' => 'The project’s default assignee.',
            'round_robin' => 'Chosen by the least open leads.',
            default => is_string($payload['from'] ?? null) ? "Was with {$payload['from']}." : null,
        };

        return $name === null ? 'Left unassigned' : "Assigned to {$name}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function followUpTitle(array $payload): string
    {
        $at = is_string($payload['at'] ?? null) ? $payload['at'] : null;

        return $at === null
            ? 'Follow-up cleared'
            : 'Follow-up set for '.Carbon::parse($at)->timezone('Asia/Dhaka')->format('d M Y, H:i');
    }
}
