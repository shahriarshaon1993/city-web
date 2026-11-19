<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Support\Import\ImportType;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADM-IO-001: one pass of the import wizard. The uploaded file waits on the private disk while the
 * person maps columns and checks the preview; the queued run then reads it in slices (`processed_rows`
 * is where the next slice starts) and removes it when done. Failed rows go to an error CSV.
 *
 * @property int $id
 * @property int|null $user_id
 * @property ImportType $type
 * @property string $original_name
 * @property string $extension
 * @property ImportStatus $status
 * @property array<string, int|null>|null $mapping field key → column index in the file
 * @property array<string, mixed>|null $options
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $created_count
 * @property int $updated_count
 * @property int $skipped_count
 * @property int $failed_count
 * @property string|null $error_path
 * @property string|null $message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 */
#[Guarded([])]
class ImportRun extends Model
{
    public const string DISK = 'local';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'mapping' => 'array',
            'options' => 'array',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
        return "imports/{$this->id}";
    }

    public function sourcePath(): string
    {
        return "{$this->directory()}/source.{$this->extension}";
    }
}
