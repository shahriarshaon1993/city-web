<?php

namespace App\Actions\Newsletter;

use App\Actions\Activity\LogActivity;
use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use App\Models\User;

/**
 * ADM-SCR-12: a team member unsubscribes an address (the subscriber asked by phone or email). The
 * row stays with status `unsubscribed` so the address is never mailed again. Returns false when it
 * was already unsubscribed. The log entry carries no address (NFR-PRIV).
 */
final class UnsubscribeSubscriber
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, NewsletterSubscriber $subscriber): bool
    {
        if ($subscriber->status === SubscriberStatus::Unsubscribed) {
            return false;
        }

        $subscriber->forceFill(['status' => SubscriberStatus::Unsubscribed])->save();

        $this->logActivity->handle('newsletter_subscribers', 'unsubscribed', 'Subscriber unsubscribed', $actor, $subscriber);

        return true;
    }
}
