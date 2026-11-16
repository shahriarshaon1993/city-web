<?php

namespace App\Models;

use App\Actions\Projects\RefreshProjectDerivedFields;
use App\Enums\Availability;
use App\Support\Media\UsesMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DM-UNIT: one apartment, suite or shop type of a project (1–20 per project, FR-PROJ-002). Saving or
 * deleting one refreshes the project's size, bed and bath ranges and `price_from_bdt`. A price leaves
 * the server only when both the project and this unit have `show_price` (AC-PROJ-01, DEC-54).
 * Floor plan and 3D plan are DM-MUSE usages (`floor_plan`, `plan_3d`).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $size_sft
 * @property int|null $size_sft_max
 * @property int|null $bedrooms
 * @property int|null $bathrooms
 * @property int|null $balconies
 * @property string|null $facing
 * @property string|null $floor_range
 * @property int|null $floor_plan_asset_id
 * @property string|null $plan_description
 * @property int|null $plan_3d_asset_id
 * @property int|null $price_bdt
 * @property bool $show_price
 * @property Availability $availability
 * @property int $sort_order
 */
#[Fillable([
    'project_id', 'name', 'size_sft', 'size_sft_max', 'bedrooms', 'bathrooms', 'balconies', 'facing',
    'floor_range', 'floor_plan_asset_id', 'plan_description', 'plan_3d_asset_id', 'price_bdt',
    'show_price', 'availability', 'sort_order',
])]
class ProjectUnitType extends Model implements UsesMedia
{
    public const int MAX_PER_PROJECT = 20;

    public const int PRICE_MIN = 100_000;

    public const int PRICE_MAX = 10_000_000_000;

    protected static function booted(): void
    {
        $refresh = static function (self $unit): void {
            $project = Project::withTrashed()->find($unit->project_id);

            if ($project !== null) {
                app(RefreshProjectDerivedFields::class)->handle($project);
            }
        };

        static::saved($refresh);
        static::deleted($refresh);
        static::deleted(MediaUsage::forget(...));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_sft' => 'integer',
            'size_sft_max' => 'integer',
            'price_bdt' => 'integer',
            'show_price' => 'boolean',
            'availability' => Availability::class,
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

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'floor_plan_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function plan3d(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'plan_3d_asset_id');
    }

    /** The price a visitor may see, or null (AC-PROJ-01, AC-PROJ-02). */
    public function publicPrice(Project $project): ?int
    {
        return $project->show_price && $this->show_price ? $this->price_bdt : null;
    }

    public function mediaUsageLabel(string $field): string
    {
        $project = $this->project()->withTrashed()->first()->name ?? 'Project';

        return "{$project} › {$this->name} › ".($field === 'plan_3d' ? '3D plan' : 'Floor plan');
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return null;
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        $column = $field === 'plan_3d' ? 'plan_3d_asset_id' : 'floor_plan_asset_id';

        if ($this->{$column} === $fromAssetId) {
            $this->forceFill([$column => $toAssetId])->saveQuietly();
        }
    }
}
