<?php

namespace App\Support\Tables;

/**
 * What a saved table view may keep (ADM-TBL-010): the client's URL state without `page` and
 * `view`. Anything else in a posted state is dropped.
 */
final class ViewState
{
    /** @var list<string> */
    public const array KEYS = ['search', 'filter', 'sort', 'per_page', 'cols', 'widths', 'density'];

    /**
     * @param  array<mixed>  $state
     * @return array<string, string|array<string, string>>
     */
    public static function clean(array $state): array
    {
        $clean = [];

        foreach (self::KEYS as $key) {
            $value = $state[$key] ?? null;

            if ($key === 'filter') {
                $filters = [];

                foreach (is_array($value) ? $value : [] as $name => $item) {
                    if (is_string($name) && preg_match('/^[a-z0-9_]{1,40}$/', $name) === 1 && is_scalar($item) && (string) $item !== '') {
                        $filters[$name] = mb_substr((string) $item, 0, 200);
                    }
                }

                if ($filters !== []) {
                    $clean['filter'] = $filters;
                }
            } elseif (is_scalar($value) && (string) $value !== '') {
                $clean[$key] = mb_substr((string) $value, 0, 600);
            }
        }

        return $clean;
    }
}
