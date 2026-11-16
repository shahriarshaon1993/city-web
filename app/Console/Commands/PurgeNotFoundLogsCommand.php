<?php

namespace App\Console\Commands;

use App\Actions\Monitoring\PurgeNotFoundLogs;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('not-found:purge')]
#[Description('Delete 404 monitor entries not seen for 90 days (SEO-TEC-009)')]
class PurgeNotFoundLogsCommand extends Command
{
    public function handle(PurgeNotFoundLogs $purge): int
    {
        $this->info("Removed {$purge->handle()} 404 entrie(s).");

        return self::SUCCESS;
    }
}
