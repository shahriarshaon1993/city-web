<?php

namespace App\Models\Concerns;

use App\Actions\Search\RefreshSearchText;
use Laravel\Scout\Searchable;

/**
 * FR-SRCH-010: Scout (database engine) over the record's own title column and `search_text`. The model
 * declares `toSearchableArray()` with the full-text columns; this keeps `search_text` current on every save.
 */
trait IsSiteSearchable
{
    use Searchable;

    protected static function bootIsSiteSearchable(): void
    {
        static::saved(fn (self $record) => app(RefreshSearchText::class)->handle($record));
    }
}
