<?php

namespace App\Models;

use App\Support\RecycleBin\BinTypes;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DEL-04: the Recycle bin index. The trashed item lives in its own table (soft deleted);
 * `type` is its morph alias and `trashable_id` its key.
 *
 * @property int $id
 * @property string $type
 * @property int $trashable_id
 * @property string $label
 * @property string|null $thumbnail
 * @property int|null $deleted_by
 * @property Carbon $deleted_at
 * @property Carbon|null $purge_at
 */
class RecycleBinEntry extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    /**
     * The soft-deleted model, or null when the row was removed behind the bin's back.
     *
     * @return (Model&Trashable)|null
     */
    public function item(): ?Model
    {
        $class = BinTypes::modelFor($this->type);

        if ($class === null) {
            return null;
        }

        $item = $class::binTrashedQuery()->find($this->trashable_id);

        return $item instanceof Trashable ? $item : null;
    }

    public function typeLabel(): string
    {
        $class = BinTypes::modelFor($this->type);

        return $class === null ? $this->type : $class::binTypeLabel();
    }
}
