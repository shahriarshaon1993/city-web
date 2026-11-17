<?php

namespace App\Console\Commands;

use App\Actions\Leads\HashOldLeadIps;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('leads:hash-ips')]
#[Description('Replace lead IP addresses older than 90 days with a salted hash (NFR-PRIV-04)')]
class HashLeadIpsCommand extends Command
{
    public function handle(HashOldLeadIps $hash): int
    {
        $count = $hash->handle();

        if ($count > 0) {
            $this->info("Hashed {$count} lead IP addresses.");
        }

        return self::SUCCESS;
    }
}
