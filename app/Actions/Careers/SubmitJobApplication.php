<?php

namespace App\Actions\Careers;

use App\Actions\Leads\VerifyTurnstile;
use App\Actions\Notifications\NotifyNewApplication;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Support\Forms\Countries;
use App\Support\Forms\PhoneNumber;
use App\Support\Leads\IpHash;
use App\Support\Leads\LeadContext;
use App\Support\Leads\SpamRules;
use App\Support\Settings\Settings;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Takes a validated job application (FR-CAR-002) or general CV drop (FR-CAR-004, `job` null) under the
 * same spam rules as a lead (FR-CAR-002, FR-LEAD-003): Turnstile, the 20-a-day limit per phone and the
 * content rules. A cover letter that looks like spam, or a Turnstile that could not be checked, is
 * stored and flagged "needs review" instead of being lost. The honeypot and the minimum fill time run
 * earlier, as route middleware. A job application is not a lead (DEC-67): no webhook, no `generate_lead`.
 *
 * @phpstan-type SubmittedApplication array{reference: string, job: string|null}
 */
final class SubmitJobApplication
{
    /** FR-LEAD-003c: 20 submissions per phone per day. */
    public const int PHONE_LIMIT_PER_DAY = 20;

    public function __construct(
        private readonly VerifyTurnstile $verifyTurnstile,
        private readonly StoreApplicationCv $storeCv,
        private readonly NotifyNewApplication $notify,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The Form Request's validated input.
     * @return SubmittedApplication
     */
    public function handle(array $data, LeadContext $context): array
    {
        $phone = PhoneNumber::normalize((string) ($data['phone'] ?? ''), (string) ($data['phone_country'] ?? Countries::DEFAULT));

        // The Form Request's phone rule uses the same normaliser, so this only guards a programming error.
        abort_if($phone === null, 422);

        $this->ensurePhoneBelowLimit($phone);
        $turnstile = $this->verifyTurnstile->handleOrFail($context->turnstileToken, $context->ip);
        RateLimiter::hit($this->phoneKey($phone), 86400);

        $cv = $data['cv'] ?? null;
        abort_unless($cv instanceof UploadedFile, 422);

        $job = is_string($data['job'] ?? null) && $data['job'] !== ''
            ? JobPost::query()->acceptingApplications()->where('slug', $data['job'])->first()
            : null;
        $reference = (string) Str::ulid();
        $stored = $this->storeCv->handle($cv, $reference);
        $letter = $this->text($data['cover_letter'] ?? null, 3000);

        try {
            $application = DB::transaction(fn (): JobApplication => JobApplication::query()->create([
                'job_post_id' => $job?->id,
                'reference' => $reference,
                'name' => $this->text($data['name'] ?? null, 120) ?? '',
                'phone' => $phone,
                'email' => Str::lower((string) $this->text($data['email'] ?? null, 190)),
                'current_position' => $this->text($data['current_position'] ?? null, 120),
                'years_experience' => filled($data['years_experience'] ?? null) ? (int) $data['years_experience'] : null,
                'expected_salary' => $this->text($data['expected_salary'] ?? null, 40),
                'linkedin_url' => $this->text($data['linkedin_url'] ?? null, 255),
                'cv_path' => $stored['path'],
                'cv_name' => $stored['name'],
                'cv_size' => $stored['size'],
                'cover_letter' => $letter,
                'status' => ApplicationStatus::New,
                'review_reason' => SpamRules::reason($letter) ?? ($turnstile->needsReview() ? 'turnstile_'.$turnstile->value : null),
                // FR-LEAD-014: the exact text shown is stored with the application.
                'consent_text' => (string) (Settings::get('forms', 'consent', [])['job_application'] ?? ''),
                'ip_hash' => $context->ip === null ? null : IpHash::make($context->ip),
            ]));
        } catch (Throwable $exception) {
            Storage::disk(JobApplication::DISK)->deleteDirectory("careers/{$reference}");

            throw $exception;
        }

        $this->notify->handle($application);

        return ['reference' => $reference, 'job' => $job?->slug];
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }

    private function phoneKey(string $phone): string
    {
        return 'application-phone:'.$phone;
    }

    private function ensurePhoneBelowLimit(string $phone): void
    {
        $key = $this->phoneKey($phone);

        if (RateLimiter::tooManyAttempts($key, self::PHONE_LIMIT_PER_DAY)) {
            throw new ThrottleRequestsException('Too many requests.', null, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }
    }
}
