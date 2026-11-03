<?php

namespace App\Actions\Newsletter;

use Illuminate\Support\Str;

/**
 * Takes a validated newsletter sign-up (spec/leads.md "Newsletter", DM-SUBS).
 * Part A (7.2) stores nothing, like SubmitLead (DEC-29): 15.1 adds the
 * `newsletter_subscribers` row (single opt-in, double opt-in by email when
 * SMTP is enabled), duplicate handling and spam protection here.
 *
 * @phpstan-type Subscription array{email: string, status: 'active', reference: string}
 */
final class SubscribeToNewsletter
{
    /**
     * @return Subscription
     */
    public function handle(string $email): array
    {
        return [
            'email' => Str::lower(trim($email)),
            'status' => 'active',
            'reference' => (string) Str::ulid(),
        ];
    }
}
