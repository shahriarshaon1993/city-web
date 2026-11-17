<?php

namespace App\Console\Commands;

use App\Actions\Notifications\NotifyFollowUpsDue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('leads:notify-follow-ups')]
#[Description('Notify each assignee of the lead follow-ups due today (daily 09:00, Asia/Dhaka)')]
class NotifyLeadFollowUpsCommand extends Command
{
    public function handle(NotifyFollowUpsDue $notify): int
    {
        $this->info("Notified {$notify->handle()} people.");

        return self::SUCCESS;
    }
}
