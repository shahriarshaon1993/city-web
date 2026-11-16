<?php

namespace App\Support\Redirects;

use App\Models\Redirect;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules of one redirect (SEO-TEC-008, VAL-01), shared by the dialog's Form Request and the CSV
 * import so both refuse the same things. Loops, chains and the 2,000-rule cap need the rule set, so
 * the `SaveRedirect` Action checks those.
 */
final class RedirectRules
{
    /**
     * @param  array<string, mixed>  $input  the submitted values (the target rules look at `source` and `code`)
     * @param  bool  $unique  false for the import, which updates an existing rule instead of refusing it
     * @return array<string, list<ValidationRule|Closure|string>>
     */
    public static function rules(array $input, ?int $ignoreId = null, bool $unique = true): array
    {
        $code = (int) ($input['code'] ?? 301);
        $source = RedirectPath::parseSource((string) ($input['source'] ?? ''));

        return [
            'source' => [
                'required', 'string', 'max:500',
                fn (string $attribute, mixed $value, Closure $fail) => self::checkSource((string) $value, $ignoreId, $unique, $fail),
            ],
            'code' => ['required', 'integer', Rule::in(Redirect::CODES)],
            'target' => $code === 410
                ? ['nullable']
                : [
                    'required', 'string', 'max:500',
                    fn (string $attribute, mixed $value, Closure $fail) => self::checkTarget((string) $value, $source, $fail),
                ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'source.required' => 'Enter the old address, e.g. /old-page.',
            'source.max' => 'The old address can be up to 500 characters.',
            'code.in' => 'Choose Permanent (301), Temporary (302) or Gone (410).',
            'target.required' => 'Enter where visitors should go, e.g. /projects. Gone (410) is the only type without a destination.',
            'target.max' => 'The destination can be up to 500 characters.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'source' => 'old address',
            'target' => 'destination',
            'code' => 'redirect type',
        ];
    }

    private static function checkSource(string $value, ?int $ignoreId, bool $unique, Closure $fail): void
    {
        $parsed = RedirectPath::parseSource($value);

        if ($parsed === null) {
            $fail('Enter an address on this site, like /old-page, or /old-section/* for everything below it.');

            return;
        }

        if ($parsed['source'] === '/') {
            $fail('The home page cannot be redirected.');

            return;
        }

        if (RedirectPath::isProtected($parsed['source'])) {
            $fail('The admin, /internal and /up cannot be redirected.');

            return;
        }

        if (! $unique) {
            return;
        }

        $existing = Redirect::withTrashed()
            ->where('source', $parsed['source'])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->first();

        if ($existing === null) {
            return;
        }

        // DEL-08: a taken value that sits in the Recycle bin says so.
        $fail($existing->trashed()
            ? 'This address belongs to a redirect in the Recycle bin — restore it or delete it forever.'
            : 'A redirect for this address already exists. Edit that one instead.');
    }

    /**
     * @param  array{source: string, is_prefix: bool}|null  $source
     */
    private static function checkTarget(string $value, ?array $source, Closure $fail): void
    {
        if (preg_match('/\s/', $value) === 1) {
            $fail('The destination cannot contain spaces.');

            return;
        }

        $isPath = str_starts_with($value, '/') && ! str_starts_with($value, '//');

        if (! $isPath && ! RedirectPath::isAbsoluteUrl($value)) {
            $fail('Enter a path starting with /, e.g. /projects, or a full URL starting with https://.');

            return;
        }

        if (str_contains($value, '*')) {
            if (! str_ends_with($value, '/*') || substr_count($value, '*') > 1 || ! ($source['is_prefix'] ?? false)) {
                $fail('A * is only allowed at the very end (/new-section/*), and only when the old address is a prefix (/old-section/*).');
            }

            return;
        }

        $own = RedirectPath::ownPath($value);

        if ($source !== null && ! $source['is_prefix'] && $own !== null && RedirectPath::normalise($own) === $source['source']) {
            $fail('The destination is the same as the old address, so visitors would never leave it.');
        }
    }
}
