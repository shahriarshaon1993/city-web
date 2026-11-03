<?php

namespace App\Actions\Careers;

use App\Support\Forms\Countries;
use App\Support\Forms\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Takes a validated job application (FR-CAR-002) or general CV drop
 * (FR-CAR-004, `job` null). Part A (8.2) only normalises the phone, like
 * SubmitLead: nothing is stored until 17.1, which adds the
 * `job_applications` row, the CV on the private disk (FR-LEAD-011), the
 * spam rules of FR-LEAD-003 and the HR notification here (DEC-29).
 *
 * @phpstan-type SubmittedApplication array{reference: string, job: string|null, phone: string|null, cv_name: string|null}
 */
final class SubmitJobApplication
{
    /**
     * @param  array<string, mixed>  $data  The Form Request's validated input.
     * @return SubmittedApplication
     */
    public function handle(array $data): array
    {
        $cv = $data['cv'] ?? null;

        return [
            'reference' => (string) Str::ulid(),
            'job' => is_string($data['job'] ?? null) ? $data['job'] : null,
            'phone' => is_string($data['phone'] ?? null)
                ? PhoneNumber::normalize($data['phone'], (string) ($data['phone_country'] ?? Countries::DEFAULT))
                : null,
            'cv_name' => $cv instanceof UploadedFile ? $cv->getClientOriginalName() : null,
        ];
    }
}
