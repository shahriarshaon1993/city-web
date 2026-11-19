<?php

namespace App\Console\Commands;

use App\Actions\Careers\CloseExpiredJobPosts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('careers:close-expired')]
#[Description('Close open job posts whose deadline has passed (FR-CAR-003)')]
class CloseExpiredJobPostsCommand extends Command
{
    public function handle(CloseExpiredJobPosts $close): int
    {
        $this->info("Closed {$close->handle()} job post(s).");

        return self::SUCCESS;
    }
}
