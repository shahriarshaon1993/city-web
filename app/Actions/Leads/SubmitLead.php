<?php

namespace App\Actions\Leads;

use App\Support\Demo\DemoMedia;
use App\Support\Forms\Countries;
use App\Support\Forms\FormType;
use App\Support\Forms\PhoneNumber;
use Illuminate\Support\Str;

/**
 * Takes a validated lead form (FR-LEAD). Part A (7.1) only normalises the
 * phone and works out the response: nothing is stored until 15.1, which adds
 * storage, spam checks, duplicate handling and assignment here (DEC-29).
 * A shortlist enquiry (FR-SHORT-004) names several `projects`: 15.1 creates
 * one lead per project, each with its own FR-LEAD-004 duplicate check.
 *
 * @phpstan-type SubmittedLead array{reference: string, type: FormType, phone: string|null, project: string|null, projects: list<string>, form_location: string|null, download_url: string|null}
 */
final class SubmitLead
{
    /**
     * @param  array<string, mixed>  $data  The Form Request's validated input.
     * @return SubmittedLead
     */
    public function handle(FormType $type, array $data): array
    {
        $phone = is_string($data['phone'] ?? null)
            ? PhoneNumber::normalize($data['phone'], (string) ($data['phone_country'] ?? Countries::DEFAULT))
            : null;

        return [
            // Becomes the lead's public reference in 15.1; the thank-you page uses it to fire `generate_lead` once.
            'reference' => (string) Str::ulid(),
            'type' => $type,
            'phone' => $phone,
            'project' => is_string($data['project'] ?? null) ? $data['project'] : null,
            'projects' => is_array($data['projects'] ?? null) ? array_values(array_filter($data['projects'], is_string(...))) : [],
            'form_location' => is_string($data['form_location'] ?? null) ? $data['form_location'] : null,
            // FR-LEAD-005: the brochure form stays on the page and starts the download.
            'download_url' => $type === FormType::BrochureDownload ? $this->brochureUrl() : null,
        ];
    }

    /**
     * The gated brochure's file (demo PDF until the media library, 11.1).
     */
    private function brochureUrl(): ?string
    {
        return DemoMedia::document('documents/brochure')['url'] ?? null;
    }
}
