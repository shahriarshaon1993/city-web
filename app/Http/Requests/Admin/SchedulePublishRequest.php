<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * CMS-PUB-003: the go-live time of "Schedule", typed as Asia/Dhaka wall time (`2026-12-01T09:00`).
 * Authorisation is the entity's `publish` ability, checked by the controller.
 */
class SchedulePublishRequest extends FormRequest
{
    public const string FORMAT = 'Y-m-d\TH:i';

    public const string ZONE = 'Asia/Dhaka';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'publish_at' => ['required', 'date_format:'.self::FORMAT, function (string $attribute, mixed $value, Closure $fail): void {
                $at = Carbon::createFromFormat(self::FORMAT, (string) $value, self::ZONE);

                if ($at->lessThanOrEqualTo(now()->addMinute())) {
                    $fail('Choose a time at least a minute from now.');
                } elseif ($at->greaterThan(now()->addYears(2))) {
                    $fail('Choose a time within the next two years.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['publish_at' => 'go-live time'];
    }

    /** The chosen instant. */
    public function at(): Carbon
    {
        return Carbon::createFromFormat(self::FORMAT, (string) $this->validated('publish_at'), self::ZONE)->utc();
    }
}
