<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-SUBS: a newsletter address. Single opt-in is `active` at once; with email enabled it starts
 * `pending` until the link in the confirmation mail is opened (spec/leads.md "Newsletter"). The
 * `token` serves both the confirm and the unsubscribe link. Delete = Recycle bin for 365 days;
 * "Erase" on the Subscribers screen removes the row for good (ADM-SCR-12, crud.md Subscribers).
 *
 * @property int $id
 * @property string $email
 * @property SubscriberStatus $status
 * @property string $token
 * @property Carbon|null $confirmed_at
 * @property string|null $source_url
 * @property string $consent_text
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $deleted_at
 */
#[Guarded([])]
class NewsletterSubscriber extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => SubscriberStatus::class, 'confirmed_at' => 'datetime'];
    }

    public static function binType(): string
    {
        return 'newsletter_subscriber';
    }

    public static function binTypeLabel(): string
    {
        return 'Subscriber';
    }

    public static function binPermission(): string
    {
        return 'subscribers.manage';
    }

    /** DEL-06: subscribers stay 365 days. */
    public static function binRetentionDays(): ?int
    {
        return 365;
    }

    public function binLabel(): string
    {
        return $this->email;
    }

    public function binUrl(): ?string
    {
        return route('admin.subscribers.index', ['search' => $this->email]);
    }
}
