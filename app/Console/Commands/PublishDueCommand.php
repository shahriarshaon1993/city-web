<?php

namespace App\Console\Commands;

use App\Actions\Publishing\PublishDueEntities;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('publishing:run')]
#[Description('Publish scheduled items and pending revisions whose time has come (CMS-PUB-003, ARC-012)')]
class PublishDueCommand extends Command
{
    public function handle(PublishDueEntities $publishDue): int
    {
        $result = $publishDue->handle();

        if ($result['published'] > 0 || $result['failed'] > 0) {
            $this->info("Published {$result['published']}, failed {$result['failed']}.");
        }

        return self::SUCCESS;
    }
}
