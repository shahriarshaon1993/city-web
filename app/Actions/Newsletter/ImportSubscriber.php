<?php

namespace App\Actions\Newsletter;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * ADM-IO-001: one address from an imported list. The person confirmed at the wizard that these people
 * agreed to the newsletter, so the address is active at once (no confirmation mail to a whole list);
 * the consent text records who vouched for it and from which file.
 */
final class ImportSubscriber
{
    public function handle(User $actor, string $email, ?CarbonInterface $confirmedAt, string $fileName): NewsletterSubscriber
    {
        return NewsletterSubscriber::create([
            'email' => $email,
            'status' => SubscriberStatus::Active,
            'confirmed_at' => $confirmedAt ?? now(),
            'token' => Str::random(40),
            'source_url' => null,
            'consent_text' => "Imported by {$actor->name} from {$fileName}; consent confirmed by the importer",
        ]);
    }
}
