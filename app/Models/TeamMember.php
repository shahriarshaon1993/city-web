<?php

namespace App\Models;

use App\Enums\TeamGroup;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-TEAM / FR-SOC-004: a person on the team grids and the leadership page. The slug is made from
 * the name when the person is created and is never shown. Delete = Recycle bin.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $role_title
 * @property TeamGroup $group
 * @property int|null $photo_asset_id
 * @property string|null $bio
 * @property string|null $linkedin_url
 * @property string|null $email
 * @property bool $show_email
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['name', 'slug', 'role_title', 'group', 'photo_asset_id', 'bio', 'linkedin_url', 'email', 'show_email', 'sort_order', 'is_published'])]
class TeamMember extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, SoftDeletes;

    public const int NAME_MAX = 100;

    public const int ROLE_MAX = 100;

    public const int BIO_MAX = 400;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['group' => TeamGroup::class, 'show_email' => 'boolean', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_asset_id');
    }

    /**
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function mediaUsageLabel(string $field): string
    {
        return "Team · {$this->name} › Photo";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.team.edit', $this);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($this->photo_asset_id === $fromAssetId) {
            $this->forceFill(['photo_asset_id' => $toAssetId])->saveQuietly();
        }
    }

    public static function binType(): string
    {
        return 'team_member';
    }

    public static function binTypeLabel(): string
    {
        return 'Team member';
    }

    public static function binPermission(): string
    {
        return 'social.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.team.edit', $this);
    }
}
