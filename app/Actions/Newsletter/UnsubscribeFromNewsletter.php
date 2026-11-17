<?php

namespace App\Actions\Newsletter;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;

/**
 * One-click unsubscribe from the link every newsletter mail carries. The row stays (status
 * `unsubscribed`) so the address is not mailed again; erasing it is a separate admin action.
 * Returns false for an unknown token.
 */
final class UnsubscribeFromNewsletter
{
    public function handle(string $token): bool
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if ($subscriber === null) {
            return false;
        }

        if ($subscriber->status !== SubscriberStatus::Unsubscribed) {
            $subscriber->forceFill(['status' => SubscriberStatus::Unsubscribed])->save();
        }

        return true;
    }
}
