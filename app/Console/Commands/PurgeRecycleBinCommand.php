<?php

namespace App\Console\Commands;

use App\Actions\RecycleBin\PurgeRecycleBin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('recycle-bin:purge')]
#[Description('Delete Recycle bin items whose auto-delete date has passed (DEL-06)')]
class PurgeRecycleBinCommand extends Command
{
    public function handle(PurgeRecycleBin $purge): int
    {
        $this->info("Removed {$purge->handle()} item(s) from the Recycle bin.");

        return self::SUCCESS;
    }
}
