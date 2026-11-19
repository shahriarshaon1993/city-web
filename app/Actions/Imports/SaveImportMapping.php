<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Models\ImportRun;
use App\Support\Import\ImportFile;
use App\Support\Import\ImportMapper;

/**
 * ADM-IO-001 step 2: remembers which column of the file feeds which field, until the import starts.
 */
final class SaveImportMapping
{
    /**
     * @param  array<string, mixed>  $mapping  field key → column index (or null/'' for "not in the file")
     */
    public function handle(ImportRun $run, array $mapping): ImportRun
    {
        if ($run->status !== ImportStatus::Uploaded) {
            return $run;
        }

        $columns = count(ImportFile::headers($run));

        $run->forceFill(['mapping' => ImportMapper::clean($run->type->definition(), $mapping, $columns)])->save();

        return $run;
    }
}
