<?php

namespace App\Http\Requests\Admin;

use App\Models\NotFoundLog;
use App\Support\Redirects\NotFoundIgnore;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SEO-TEC-009 ignore list: one regular expression per line, each up to 200 characters. `reset`
 * goes back to the built-in list.
 */
class UpdateNotFoundIgnoreListRequest extends FormRequest
{
    private const int MAX_PATTERNS = 50;

    public function authorize(): bool
    {
        return $this->user()->can('manage', NotFoundLog::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reset' => ['sometimes', 'boolean'],
            'patterns' => [
                'nullable', 'string', 'max:5000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $patterns = NotFoundIgnore::split((string) $value);

                    if (count($patterns) > self::MAX_PATTERNS) {
                        $fail('Use at most '.self::MAX_PATTERNS.' patterns.');

                        return;
                    }

                    foreach ($patterns as $number => $pattern) {
                        if (mb_strlen($pattern) > 200) {
                            $fail('Pattern '.($number + 1).' is longer than 200 characters.');

                            return;
                        }

                        if (! NotFoundIgnore::isValid($pattern)) {
                            $fail('“'.mb_strimwidth($pattern, 0, 40, '…').'” is not a valid regular expression.');

                            return;
                        }
                    }
                },
            ],
        ];
    }
}
