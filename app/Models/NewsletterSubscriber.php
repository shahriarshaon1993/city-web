<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-SUBS: a newsletter address. Single opt-in is `active` at once; with email enabled it starts
 * `pending` until the link in the confirmation mail is opened (spec/leads.md "Newsletter"). The
 * `token` serves both the confirm and the unsubscribe link. The admin screen comes with 15.2.
 *
 * @property int $id
 * @property string $email
 * @property SubscriberStatus $status
 * @property string $token
 * @property Carbon|null $confirmed_at
 * @property string|null $source_url
 * @property string $consent_text
 * @property string|null $ip_hash
 */
#[Guarded([])]
class NewsletterSubscriber extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => SubscriberStatus::class, 'confirmed_at' => 'datetime'];
    }
}
