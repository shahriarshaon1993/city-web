<?php

namespace App\Console\Commands;

use App\Actions\Careers\PurgeOldApplications;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('applications:purge')]
#[Description('Delete job applications, with their CVs, not updated for 12 months (NFR-PRIV-04)')]
class PurgeOldApplicationsCommand extends Command
{
    public function handle(PurgeOldApplications $purge): int
    {
        $this->info("Removed {$purge->handle()} application(s).");

        return self::SUCCESS;
    }
}
