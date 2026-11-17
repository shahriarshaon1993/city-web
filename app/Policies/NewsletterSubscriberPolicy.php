<?php

namespace App\Policies;

use App\Models\NewsletterSubscriber;
use App\Models\User;

/**
 * Subscribers (spec/admin.md §10.2 "Subscribers: view/export"): `subscribers.manage` opens the screen
 * and allows export, unsubscribe, delete (Recycle bin) and Erase (hard delete, GDPR).
 */
class NewsletterSubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscribers.manage');
    }

    public function update(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('subscribers.manage');
    }

    public function delete(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('subscribers.manage');
    }

    public function erase(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('subscribers.manage');
    }
}
