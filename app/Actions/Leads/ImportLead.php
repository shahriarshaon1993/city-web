<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ADM-IO-001: one lead from an imported file (a Facebook Lead Ads export, a spreadsheet from an
 * event). It is a quiet record: no notification, webhook or autoresponder (the people were not just
 * now asked), and no owner — the manager assigns the batch from the Leads screen. The file's own
 * date, when it has one, becomes the lead's date so the pipeline and the reports stay honest.
 */
final class ImportLead
{
    /**
     * @param  array{name: string, phone: string, email: string|null, project_id: int|null, message: string|null, utm_source: string|null, utm_campaign: string|null, received_at: CarbonInterface|null}  $data  cleaned by the import definition
     */
    public function handle(User $actor, array $data, string $fileName): Lead
    {
        return DB::transaction(function () use ($actor, $data, $fileName): Lead {
            $at = $data['received_at'] ?? now();

            $lead = Lead::create([
                'reference' => (string) Str::ulid(),
                'type' => Lead::IMPORT_TYPE,
                'status' => LeadStatus::New,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'project_id' => $data['project_id'],
                'message' => $data['message'],
                'source_url' => '/',
                'utm_source' => $data['utm_source'],
                'utm_campaign' => $data['utm_campaign'],
                'consent_text' => "Imported by {$actor->name} from {$fileName}",
                'last_touched_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            $lead->activities()->create([
                'kind' => LeadActivityKind::Submission,
                'payload' => ['type' => Lead::IMPORT_TYPE, 'file' => $fileName],
            ]);

            return $lead;
        });
    }
}
