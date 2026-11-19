<?php

namespace App\Support\Tables;

/**
 * A cell that starts with = + - @ is read as a formula by Excel; prefix it so a name like
 * "=HYPERLINK(…)" stays text. Used by every file the admin writes for download (table exports,
 * import error files, personal-data exports).
 */
final class ExportCell
{
    /**
     * A column marked `text` (phone numbers, FR-LEAD-010) keeps a value that is only `+` and digits as
     * it is: the leading `+` is the point, and a number cannot be a formula.
     */
    public static function safe(mixed $value, bool $isText = false): mixed
    {
        if ($isText && is_string($value) && preg_match('/^\+\d{4,20}$/', $value) === 1) {
            return $value;
        }

        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
            ? "'".$value
            : $value;
    }
}
