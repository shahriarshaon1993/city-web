<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Project;
use App\Support\Forms\FormType;
use App\Support\Leads\LeadContext;
use App\Support\Leads\SpamRules;
use App\Support\Leads\TurnstileResult;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the lead of one validated form for one project (or none), or — when the same phone asked
 * about the same project in the last 24 hours — adds a "touch" to that lead instead (FR-LEAD-004).
 * New leads get a status from the spam rules (FR-LEAD-003) and an assignee (FR-LEAD-007); the exact
 * consent text shown is stored with them (FR-LEAD-014).
 */
final class StoreLead
{
    public function __construct(
        private readonly AssignLead $assignLead,
        private readonly StoreLeadDocuments $storeDocuments,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The Form Request's validated input.
     */
    public function handle(
        FormType $type,
        array $data,
        string $phone,
        ?Project $project,
        LeadContext $context,
        TurnstileResult $turnstile,
    ): Lead {
        return DB::transaction(function () use ($type, $data, $phone, $project, $context, $turnstile): Lead {
            $existing = $this->recentLead($phone, $project);
            $reference = $existing->reference ?? (string) Str::ulid();
            $spam = SpamRules::reason(is_string($data['message'] ?? null) ? $data['message'] : null);
            $attachments = $this->storeDocuments->handle(is_array($data['documents'] ?? null) ? $data['documents'] : [], $reference);
            $answers = $this->answers($type, $data);

            if ($existing !== null) {
                return $this->touch($existing, $type, $data, $answers, $attachments, $spam, $turnstile);
            }

            $lead = Lead::create([
                'reference' => $reference,
                'type' => $type->value,
                'status' => $this->initialStatus($spam, $turnstile),
                'name' => $this->text($data['name'] ?? null, 120) ?? '',
                'phone' => $phone,
                'whatsapp' => ($data['whatsapp_same'] ?? false) ? $phone : null,
                'email' => $this->email($data['email'] ?? null),
                'country' => $this->country($type, $data),
                'project_id' => $project?->id,
                'unit_type_id' => $project !== null && isset($data['unit_type']) ? (int) $data['unit_type'] : null,
                'answers' => $answers === [] ? null : $answers,
                'message' => $this->text($data['message'] ?? null, 2000),
                'attachments' => $attachments === [] ? null : $attachments,
                'source_url' => $this->text($data['source_url'] ?? null, 500) ?? '/',
                'form_location' => $this->text($data['form_location'] ?? null, 40),
                'utm_source' => $this->text($data['utm_source'] ?? null, 100),
                'utm_medium' => $this->text($data['utm_medium'] ?? null, 100),
                'utm_campaign' => $this->text($data['utm_campaign'] ?? null, 100),
                'utm_term' => $this->text($data['utm_term'] ?? null, 100),
                'utm_content' => $this->text($data['utm_content'] ?? null, 100),
                'referrer_host' => $this->text($data['referrer_host'] ?? null, 190),
                'ga_client_id' => $context->gaClientId,
                'consent_text' => (string) (Settings::get('forms', 'consent', [])[$type->value] ?? ''),
                'ip' => $context->ip,
                'user_agent' => $context->userAgent,
                'last_touched_at' => now(),
            ]);

            $lead->activities()->create([
                'kind' => LeadActivityKind::Submission,
                'payload' => array_filter([
                    'type' => $type->value,
                    'form_location' => $lead->form_location,
                    'spam_reason' => $spam,
                    'turnstile' => $turnstile->value,
                ]),
            ]);

            if ($lead->status !== LeadStatus::Spam) {
                $this->assignLead->handle($lead, $project);
            }

            return $lead;
        });
    }

    /**
     * FR-LEAD-004: same normalised phone and same project (or none) within the duplicate window.
     */
    private function recentLead(string $phone, ?Project $project): ?Lead
    {
        return Lead::query()
            ->where('phone', $phone)
            ->when($project === null, fn ($query) => $query->whereNull('project_id'), fn ($query) => $query->where('project_id', $project?->id))
            ->where('created_at', '>=', now()->subHours(Lead::DUPLICATE_WINDOW_HOURS))
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $answers
     * @param  list<array{path: string, name: string, size: int, mime: string}>  $attachments
     */
    private function touch(Lead $lead, FormType $type, array $data, array $answers, array $attachments, ?string $spam, TurnstileResult $turnstile): Lead
    {
        $merged = [...($lead->attachments ?? []), ...$attachments];

        $lead->forceFill([
            'touches_count' => $lead->touches_count + 1,
            'last_touched_at' => now(),
            'email' => $lead->email ?? $this->email($data['email'] ?? null),
            'attachments' => $merged === [] ? null : $merged,
        ])->save();

        $lead->activities()->create([
            'kind' => LeadActivityKind::Touch,
            'payload' => array_filter([
                'type' => $type->value,
                'form_location' => $this->text($data['form_location'] ?? null, 40),
                'message' => $this->text($data['message'] ?? null, 2000),
                'answers' => $answers === [] ? null : $answers,
                'attachments' => $attachments === [] ? null : count($attachments),
                'spam_reason' => $spam,
                'turnstile' => $turnstile->value,
            ]),
        ]);

        return $lead;
    }

    private function initialStatus(?string $spam, TurnstileResult $turnstile): LeadStatus
    {
        return match (true) {
            $spam !== null => LeadStatus::Spam,
            $turnstile->needsReview() => LeadStatus::NeedsReview,
            default => LeadStatus::New,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function answers(FormType $type, array $data): array
    {
        $answers = [];

        foreach ($type->answerKeys() as $key) {
            $value = $data[$key] ?? null;

            if ($value !== null && $value !== '') {
                $answers[$key] = $value;
            }
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function country(FormType $type, array $data): ?string
    {
        $country = $type === FormType::Nrb ? ($data['country'] ?? null) : ($data['phone_country'] ?? null);

        return is_string($country) && strlen($country) === 2 ? strtoupper($country) : null;
    }

    private function email(mixed $value): ?string
    {
        $email = $this->text($value, 190);

        return $email === null ? null : Str::lower($email);
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }
}
