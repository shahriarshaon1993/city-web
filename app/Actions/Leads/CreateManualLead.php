<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Enums\LeadChange;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Support\Leads\LeadReasons;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DEC-33: a lead that staff add by hand (phone call, walk-in, email, event). Same rules as the public
 * forms: the phone is already E.164, an enquiry for the same phone and project inside 24 hours adds a
 * touch to the existing lead (FR-LEAD-004), a new lead gets an owner (FR-LEAD-007). A Sales Executive
 * who can only see their own leads becomes the owner of what they add; a manager may pick the owner.
 */
final class CreateManualLead
{
    public function __construct(
        private readonly FindRecentLead $findRecentLead,
        private readonly AssignLead $assignLead,
        private readonly ReassignLead $reassignLead,
        private readonly SetLeadFollowUp $setFollowUp,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  name, channel, email, whatsapp, project_id, unit_type_id, message
     * @return array{lead: Lead, touched: bool}
     */
    public function handle(User $actor, array $data, string $phone, ?User $assignee, ?CarbonInterface $followUpAt): array
    {
        return DB::transaction(function () use ($actor, $data, $phone, $assignee, $followUpAt): array {
            $project = isset($data['project_id']) ? Project::query()->find((int) $data['project_id']) : null;
            $channelKey = (string) ($data['channel'] ?? 'other');
            $channel = LeadReasons::CHANNELS[$channelKey] ?? $channelKey;
            $message = $this->text($data['message'] ?? null, 2000);

            $existing = $this->findRecentLead->handle($phone, $project);

            if ($existing !== null) {
                $existing->forceFill([
                    'touches_count' => $existing->touches_count + 1,
                    'last_touched_at' => now(),
                    'email' => $existing->email ?? $this->text($data['email'] ?? null, 190),
                ])->save();

                $existing->activities()->create([
                    'user_id' => $actor->id,
                    'kind' => LeadActivityKind::Touch,
                    'payload' => array_filter(['type' => Lead::MANUAL_TYPE, 'channel' => $channel, 'message' => $message]),
                ]);

                $this->logActivity->handle('leads', 'touched', 'Enquiry added to an existing lead', $actor, $existing, ['channel' => $channelKey]);

                LeadUpdated::dispatch($existing, LeadChange::Touched, $actor);

                return ['lead' => $existing, 'touched' => true];
            }

            $lead = Lead::create([
                'reference' => (string) Str::ulid(),
                'type' => Lead::MANUAL_TYPE,
                'status' => LeadStatus::New,
                'name' => $this->text($data['name'] ?? null, 120) ?? '',
                'phone' => $phone,
                'whatsapp' => ($data['whatsapp'] ?? false) ? $phone : null,
                'email' => $this->text($data['email'] ?? null, 190),
                'project_id' => $project?->id,
                'unit_type_id' => $project !== null ? ($data['unit_type_id'] ?? null) : null,
                'answers' => ['channel' => $channelKey],
                'message' => $message,
                'source_url' => route('admin.leads.index', absolute: false),
                'consent_text' => "Recorded by {$actor->name} ({$channel})",
                'last_touched_at' => now(),
            ]);

            $lead->activities()->create([
                'user_id' => $actor->id,
                'kind' => LeadActivityKind::Submission,
                'payload' => ['type' => Lead::MANUAL_TYPE, 'channel' => $channel],
            ]);

            $owner = $assignee ?? (! $actor->can('leads.view-all') ? $actor : null);

            // The owner and follow-up belong to the creation: one `lead.created`, not extra updates.
            if ($owner !== null) {
                $this->reassignLead->handle($actor, $lead, $owner, announce: false);
            } else {
                $this->assignLead->handle($lead, $project);
            }

            if ($followUpAt !== null) {
                $this->setFollowUp->handle($actor, $lead, $followUpAt, announce: false);
            }

            $this->logActivity->handle('leads', 'created', 'Lead added by hand', $actor, $lead, ['channel' => $channelKey]);

            LeadCreated::dispatch($lead, $actor);

            return ['lead' => $lead, 'touched' => false];
        });
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }
}
