<?php

namespace App\Models;

use App\Enums\NearbyCategory;
use App\Enums\TravelMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DM-NEAR: a landmark near a project with its travel time (FR-PROJ-006, at most 10 per project).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property NearbyCategory $category
 * @property int $minutes
 * @property TravelMode $mode
 * @property int $sort_order
 */
#[Fillable(['project_id', 'name', 'category', 'minutes', 'mode', 'sort_order'])]
class ProjectNearbyPlace extends Model
{
    public const int MAX_PER_PROJECT = 10;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NearbyCategory::class,
            'mode' => TravelMode::class,
            'minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
