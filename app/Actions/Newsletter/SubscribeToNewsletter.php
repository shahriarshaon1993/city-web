<?php

namespace App\Actions\Newsletter;

use App\Actions\Leads\VerifyTurnstile;
use App\Enums\SubscriberStatus;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use App\Support\Leads\IpHash;
use App\Support\Leads\LeadContext;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Takes a validated newsletter sign-up (spec/leads.md "Newsletter", DM-SUBS). Double opt-in when
 * email is enabled in Settings → Notifications: the address stays `pending` until the link in the
 * confirmation mail is opened. Without email it is single opt-in and `active` at once (the consent
 * is recorded either way). An address that is already active answers the same success, so the form
 * never reveals who is subscribed; one that unsubscribed or sits in the Recycle bin starts over,
 * because signing up again is a fresh consent.
 */
final class SubscribeToNewsletter
{
    public function __construct(private readonly VerifyTurnstile $verifyTurnstile) {}

    public function handle(string $email, ?string $sourceUrl, LeadContext $context): SubscriberStatus
    {
        $this->verifyTurnstile->handleOrFail($context->turnstileToken, $context->ip);

        $email = Str::lower(trim($email));
        $doubleOptIn = (bool) Settings::get('notifications', 'email_enabled', false);
        $subscriber = NewsletterSubscriber::withTrashed()->where('email', $email)->first();

        if ($subscriber !== null && ! $subscriber->trashed() && $subscriber->status === SubscriberStatus::Active) {
            return SubscriberStatus::Active;
        }

        $status = $doubleOptIn ? SubscriberStatus::Pending : SubscriberStatus::Active;
        $values = [
            'status' => $status,
            'confirmed_at' => $status === SubscriberStatus::Active ? now() : null,
            'source_url' => $sourceUrl === null ? null : mb_substr($sourceUrl, 0, 500),
            'consent_text' => (string) Settings::get('forms', 'consent', [])['newsletter'],
            'ip_hash' => $context->ip === null ? null : IpHash::make($context->ip),
        ];

        if ($subscriber === null) {
            $subscriber = NewsletterSubscriber::create([...$values, 'email' => $email, 'token' => Str::random(40)]);
        } else {
            $subscriber->trashed() && $subscriber->restore();
            $subscriber->fill($values)->save();
        }

        if ($status === SubscriberStatus::Pending) {
            Mail::to($subscriber->email)->queue(new ConfirmNewsletterSubscription($subscriber));
        }

        return $status;
    }
}
