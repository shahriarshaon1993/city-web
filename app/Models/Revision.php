<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * DM-REV (CMS-PUB-002/004): the JSON of a full form payload, `pending` (waiting to go live) or
 * `published` (history). 13.1 only creates the table and the model; 14.1 writes and applies them.
 *
 * @property int $id
 * @property string $revisionable_type
 * @property int $revisionable_id
 * @property string $kind
 * @property array<string, mixed> $payload
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
#[Fillable(['kind', 'payload', 'note', 'created_by'])]
class Revision extends Model
{
    public const UPDATED_AT = null;

    public const string PENDING = 'pending';

    public const string PUBLISHED = 'published';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
