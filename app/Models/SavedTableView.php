<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DM-VIEW / ADM-TBL-010: a saved table state. `state` is the query string of the
 * table (search, filter, sort, per_page, cols, widths, density) as an array.
 *
 * @property int $id
 * @property int $user_id
 * @property string $table_key
 * @property string $name
 * @property array<string, mixed> $state
 * @property bool $is_shared
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['table_key', 'name', 'state', 'is_shared', 'is_default'])]
class SavedTableView extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => 'array',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
