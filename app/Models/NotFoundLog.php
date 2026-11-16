<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * DM-404 (SEO-TEC-009): an address visitors asked for that does not exist, with how often. Rows
 * are written by `RecordNotFound` and removed for good (crud.md DEL-09: no Recycle bin).
 *
 * @property int $id
 * @property string $path
 * @property string|null $referrer
 * @property int $hits
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property bool $is_ignored
 */
#[Fillable(['path', 'referrer', 'hits', 'first_seen_at', 'last_seen_at', 'is_ignored'])]
class NotFoundLog extends Model
{
    public $timestamps = false;

    /** SEO-TEC-009: entries older than this many days are purged. */
    public const int RETENTION_DAYS = 90;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_ignored' => 'boolean',
        ];
    }
}
