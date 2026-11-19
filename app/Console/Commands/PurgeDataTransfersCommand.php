<?php

namespace App\Console\Commands;

use App\Actions\Imports\PurgeDataTransfers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('transfers:purge')]
#[Description('Delete expired export files, unconfirmed import uploads and old error files (ADM-IO-001, ADM-TBL-012)')]
class PurgeDataTransfersCommand extends Command
{
    public function handle(PurgeDataTransfers $purge): int
    {
        $result = $purge->handle();

        $this->info("Removed {$result['files']} file folder(s) and {$result['rows']} old record(s).");

        return self::SUCCESS;
    }
}
