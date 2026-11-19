<?php

namespace App\Support\Import;

use Carbon\CarbonImmutable;
use Throwable;

/** Small readers for cell text that several import types share. */
final class ImportValues
{
    /** A yes/no cell: empty gives `$default`, anything that is clearly "no" gives false, the rest true. */
    public static function truthy(string $value, bool $default): bool
    {
        $value = mb_strtolower(trim($value));

        return match (true) {
            $value === '' => $default,
            in_array($value, ['no', 'n', '0', 'false', 'off', 'inactive', 'draft', 'hidden'], true) => false,
            default => true,
        };
    }

    /**
     * A date or date-time as people write it, read as Asia/Dhaka time when the cell names no zone;
     * a date in the future is clamped to now.
     *
     * @return CarbonImmutable|false|null null = empty cell, false = not a date
     */
    public static function date(string $value): CarbonImmutable|false|null
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value, 'Asia/Dhaka')->utc();
        } catch (Throwable) {
            return false;
        }

        return $date->isFuture() ? CarbonImmutable::now() : $date;
    }

    /** First `$max` characters of a cell, or null when it is empty. */
    public static function text(string $value, int $max): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
