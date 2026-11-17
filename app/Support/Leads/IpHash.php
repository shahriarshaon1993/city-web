<?php

namespace App\Support\Leads;

/**
 * NFR-PRIV-04: SHA-256 of an IP address salted with the app key. Stored in place of the raw IP
 * after 90 days (leads) and from the start (newsletter subscribers).
 */
final class IpHash
{
    public static function make(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
