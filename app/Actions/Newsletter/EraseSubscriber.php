<?php

namespace App\Actions\Newsletter;

use App\Actions\Activity\LogActivity;
use App\Models\NewsletterSubscriber;
use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ADM-SCR-12 / crud.md Subscribers "Erase = Hard": removes the address for good (a person asked to be
 * forgotten). No Undo and no Recycle bin copy. The log entry keeps only the row number, never the address.
 */
final class EraseSubscriber
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, NewsletterSubscriber $subscriber): void
    {
        DB::transaction(function () use ($actor, $subscriber): void {
            $id = $subscriber->id;

            RecycleBinEntry::query()
                ->where('type', NewsletterSubscriber::binType())
                ->where('trashable_id', $id)
                ->delete();

            $subscriber->forceDelete();

            $this->logActivity->handle('newsletter_subscribers', 'erased', 'Subscriber erased for good', $actor, null, ['subscriber_id' => $id]);
        });
    }
}
