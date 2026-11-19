<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADM-TBL-012: a table export of more than 5,000 rows, written in the background to the private disk
 * and offered through a signed link for 24 hours.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $table_key
 * @property string $format
 * @property list<string> $columns
 * @property array<string, mixed> $query the table state (search, filter, sort) as the URL carried it
 * @property string $status queued | running | ready | failed
 * @property int|null $row_count
 * @property string $filename
 * @property string|null $path
 * @property string|null $message
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 */
#[Guarded([])]
class ExportRun extends Model
{
    public const string DISK = 'local';

    /** ADM-TBL-012: more rows than this are prepared in the background. */
    public const int QUEUE_THRESHOLD = 5000;

    /** How long the download link works. */
    public const int LINK_HOURS = 24;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'query' => 'array',
            'row_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directory(): string
    {
        return "exports/{$this->id}";
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'ready' && $this->path !== null && $this->expires_at !== null && $this->expires_at->isFuture();
    }
}
