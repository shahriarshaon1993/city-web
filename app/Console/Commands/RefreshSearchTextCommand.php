<?php

namespace App\Console\Commands;

use App\Actions\Search\RefreshSearchText;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('search:refresh')]
#[Description('Rebuild the site-search text of every project, post and page (FR-SRCH-010)')]
class RefreshSearchTextCommand extends Command
{
    public function handle(RefreshSearchText $refresh): int
    {
        $this->info("Rebuilt the search text of {$refresh->handleAll()} record(s).");

        return self::SUCCESS;
    }
}
