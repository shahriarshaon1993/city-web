<?php

namespace App\Support\Import\Definitions;

use App\Actions\Leads\ImportLead;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Support\Forms\PhoneNumber;
use App\Support\Import\ImportDefinition;
use App\Support\Import\ImportField;
use App\Support\Import\ImportValues;
use App\Support\Import\RowResult;
use Carbon\CarbonInterface;

/**
 * ADM-IO-001 leads, shaped for a Facebook Lead Ads export (`full_name`, `phone_number`, `p:+8801…`,
 * `created_time`, `campaign_name`) and any plain spreadsheet. A phone that is already a lead of the same
 * project is skipped, so running a file twice adds nothing.
 */
final class LeadsImport extends ImportDefinition
{
    /** @var array<string, int>|null project name / slug (lower case) → id, filled on first use */
    private ?array $projects = null;

    public function help(): string
    {
        return 'Enquiries from a Facebook Lead Ads download or a spreadsheet. They arrive unassigned, marked Imported, with no notification or automatic reply.';
    }

    public function fields(): array
    {
        return [
            new ImportField('name', 'Name', true, ['full name', 'fullname', 'contact name', 'customer name'], 'Rahim Uddin'),
            new ImportField('phone', 'Phone', true, ['phone number', 'mobile', 'mobile number', 'contact number', 'whatsapp number'], '01712345678', 'Bangladesh numbers as 01712345678, other countries with +code'),
            new ImportField('email', 'Email', false, ['email address', 'e mail'], 'rahim@example.com'),
            new ImportField('project', 'Project', false, ['project name', 'interested project', 'interested in'], 'Gemcon Tower', 'Name or URL slug of a project on this site'),
            new ImportField('message', 'Message', false, ['comments', 'comment', 'notes', 'note', 'remarks'], 'Wants a 3-bedroom flat'),
            new ImportField('source', 'Source', false, ['utm source', 'platform', 'channel'], 'facebook'),
            new ImportField('campaign', 'Campaign', false, ['campaign name', 'ad name', 'form name'], 'Summer launch'),
            new ImportField('received', 'Date received', false, ['created time', 'created at', 'date', 'submitted at', 'submitted'], '2026-11-02 14:30', 'When they enquired; empty = now'),
        ];
    }

    public function check(array $row): RowResult
    {
        $name = ImportValues::text($row['name'] ?? '', 120);

        if ($name === null) {
            return RowResult::fail('Name is empty.');
        }

        $phone = PhoneNumber::fromAnyCountry($row['phone'] ?? '');

        if ($phone === null) {
            return RowResult::fail('The phone number is missing or not valid. Use 01712345678, or +country code for other countries.');
        }

        $email = ImportValues::text($row['email'] ?? '', 190);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return RowResult::fail("“{$email}” is not an email address.");
        }

        $projectId = null;
        $projectText = ImportValues::text($row['project'] ?? '', 190);

        if ($projectText !== null) {
            $projectId = $this->projectId($projectText);

            if ($projectId === null) {
                return RowResult::fail("No project called “{$projectText}” on this site.");
            }
        }

        $received = ImportValues::date($row['received'] ?? '');

        if ($received === false) {
            return RowResult::fail('The date is not one we can read. Use 2026-11-02 or 2026-11-02 14:30.');
        }

        $exists = Lead::query()
            ->where('phone', $phone)
            ->when($projectId === null, fn ($query) => $query->whereNull('project_id'), fn ($query) => $query->where('project_id', $projectId))
            ->exists();

        if ($exists) {
            return RowResult::skip('This phone is already a lead for the same project.');
        }

        return RowResult::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email === null ? null : mb_strtolower($email),
            'project_id' => $projectId,
            'message' => ImportValues::text($row['message'] ?? '', 2000),
            'utm_source' => ImportValues::text($row['source'] ?? '', 100),
            'utm_campaign' => ImportValues::text($row['campaign'] ?? '', 100),
            'received_at' => $received ?: null,
        ]);
    }

    protected function write(User $actor, RowResult $checked, array $options): RowResult
    {
        /** @var array{name: string, phone: string, email: string|null, project_id: int|null, message: string|null, utm_source: string|null, utm_campaign: string|null, received_at: CarbonInterface|null} $data */
        $data = $checked->data;

        app(ImportLead::class)->handle($actor, $data, (string) ($options['file'] ?? 'a file'));

        return $checked;
    }

    private function projectId(string $text): ?int
    {
        if ($this->projects === null) {
            $this->projects = [];

            foreach (Project::query()->get(['id', 'name', 'slug']) as $project) {
                $this->projects[mb_strtolower($project->name)] = $project->id;
                $this->projects[mb_strtolower($project->slug)] = $project->id;
            }
        }

        return $this->projects[mb_strtolower($text)] ?? null;
    }
}
