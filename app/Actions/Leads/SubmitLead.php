<?php

namespace App\Actions\Leads;

use App\Models\Project;
use App\Support\Forms\Countries;
use App\Support\Forms\FormType;
use App\Support\Forms\PhoneNumber;
use App\Support\Leads\LeadContext;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Takes a validated lead form (FR-LEAD workflow): checks Turnstile and the per-phone limit,
 * normalises the phone to E.164 (FR-LEAD-002) and stores the lead through `StoreLead`. A shortlist
 * enquiry (FR-SHORT-004) names several `projects` and creates one lead per project, each with its
 * own duplicate check. The brochure form gets a 10-minute signed download link (AC-PROJ-05).
 *
 * @phpstan-type SubmittedLead array{reference: string, type: FormType, phone: string, project: string|null, projects: list<string>, form_location: string|null, download_url: string|null}
 */
final class SubmitLead
{
    /** FR-LEAD-003c: 20 submissions per phone per day. */
    public const int PHONE_LIMIT_PER_DAY = 20;

    public function __construct(
        private readonly VerifyTurnstile $verifyTurnstile,
        private readonly StoreLead $storeLead,
        private readonly CreateBrochureLink $createBrochureLink,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The Form Request's validated input.
     * @return SubmittedLead
     */
    public function handle(FormType $type, array $data, LeadContext $context): array
    {
        $phone = PhoneNumber::normalize((string) ($data['phone'] ?? ''), (string) ($data['phone_country'] ?? Countries::DEFAULT));

        // The Form Request's phone rule uses the same normaliser, so this only guards a programming error.
        abort_if($phone === null, 422);

        $this->ensurePhoneBelowLimit($phone);
        $turnstile = $this->verifyTurnstile->handleOrFail($context->turnstileToken, $context->ip);
        RateLimiter::hit($this->phoneKey($phone), 86400);

        $slugs = $this->projectSlugs($data);
        $projects = Project::query()->published()->whereIn('slug', $slugs)->get()->keyBy('slug');
        $targets = $slugs === []
            ? [null]
            : array_values(array_filter(array_map(fn (string $slug): ?Project => $projects->get($slug), $slugs)));

        $leads = [];

        foreach ($targets as $project) {
            $leads[] = $this->storeLead->handle($type, $data, $phone, $project, $context, $turnstile);
        }

        $first = $projects->get($slugs[0] ?? '');

        return [
            // The thank-you page uses it to fire `generate_lead` once (FR-LEAD-005).
            'reference' => $leads[0]->reference,
            'type' => $type,
            'phone' => $phone,
            'project' => is_string($data['project'] ?? null) ? $data['project'] : null,
            'projects' => is_array($data['projects'] ?? null) ? array_values(array_filter($data['projects'], is_string(...))) : [],
            'form_location' => is_string($data['form_location'] ?? null) ? $data['form_location'] : null,
            // FR-LEAD-005: the brochure form stays on the page and starts the download.
            'download_url' => $type === FormType::BrochureDownload && $first !== null ? $this->createBrochureLink->handle($first) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function projectSlugs(array $data): array
    {
        if (is_array($data['projects'] ?? null)) {
            return array_values(array_filter($data['projects'], is_string(...)));
        }

        return is_string($data['project'] ?? null) ? [$data['project']] : [];
    }

    private function phoneKey(string $phone): string
    {
        return 'lead-phone:'.$phone;
    }

    private function ensurePhoneBelowLimit(string $phone): void
    {
        $key = $this->phoneKey($phone);

        if (RateLimiter::tooManyAttempts($key, self::PHONE_LIMIT_PER_DAY)) {
            throw new ThrottleRequestsException('Too many requests.', null, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }
    }
}
