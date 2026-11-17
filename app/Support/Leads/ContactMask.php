<?php

namespace App\Support\Leads;

/**
 * Viewer role (spec/admin.md §10.2: "no phone/email: masked"). The real value never leaves the
 * server for a user without `leads.view-contact`, the same rule as hidden prices (AC-PROJ-01).
 */
final class ContactMask
{
    public static function phone(string $phone): string
    {
        $length = mb_strlen($phone);

        return $length <= 7
            ? str_repeat('•', $length)
            : mb_substr($phone, 0, 4).str_repeat('•', $length - 7).mb_substr($phone, -3);
    }

    public static function email(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return $email === null ? null : '•••';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'•••@'.$domain;
    }
}
