<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * FR-LEAD-009: the follow-up date and time, typed as Asia/Dhaka wall time (`2026-12-01T10:00`).
 * Empty clears it. Not earlier than the start of today, and within two years.
 */
class SetLeadFollowUpRequest extends FormRequest
{
    public const string FORMAT = 'Y-m-d\TH:i';

    public const string ZONE = 'Asia/Dhaka';

    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && $this->user()->can('update', $lead);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'follow_up_at' => ['nullable', 'date_format:'.self::FORMAT, function (string $attribute, mixed $value, Closure $fail): void {
                $at = Carbon::createFromFormat(self::FORMAT, (string) $value, self::ZONE);

                if ($at->lessThan(now(self::ZONE)->startOfDay())) {
                    $fail('Choose today or a later day.');
                } elseif ($at->greaterThan(now()->addYears(2))) {
                    $fail('Choose a day within the next two years.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['follow_up_at' => 'follow-up'];
    }

    public function at(): ?Carbon
    {
        $value = $this->validated('follow_up_at');

        return $value === null || $value === '' ? null : Carbon::createFromFormat(self::FORMAT, (string) $value, self::ZONE)->utc();
    }
}
