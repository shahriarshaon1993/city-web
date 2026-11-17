<?php

namespace App\Actions\Newsletter;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;

/**
 * Double opt-in: opening the link of the confirmation mail makes a pending address active.
 * Returns false for an unknown token. Opening it again is harmless.
 */
final class ConfirmNewsletterSubscription
{
    public function handle(string $token): bool
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if ($subscriber === null || $subscriber->status === SubscriberStatus::Unsubscribed) {
            return false;
        }

        if ($subscriber->status === SubscriberStatus::Pending) {
            $subscriber->forceFill(['status' => SubscriberStatus::Active, 'confirmed_at' => now()])->save();
        }

        return true;
    }
}
