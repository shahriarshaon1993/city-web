<?php

namespace App\Support\RecycleBin;

use RuntimeException;

/**
 * DEL-05: a restore needs a decision first (slug taken, parent in the bin). Never silent: the
 * bin screen opens the conflict dialog with these.
 */
final class RestoreConflictException extends RuntimeException
{
    /**
     * @param  list<RestoreConflict>  $conflicts
     */
    public function __construct(public readonly array $conflicts)
    {
        parent::__construct('This item cannot be restored until its conflicts are resolved.');
    }
}
