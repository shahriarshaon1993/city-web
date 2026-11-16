<?php

namespace App\Models;

use App\Enums\AmenityCategory;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-AMEN: an amenity a project can list (icon from the Lucide allowlist). Delete = Recycle bin,
 * blocked while a project uses it (crud.md matrix, DEL-03).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property AmenityCategory $category
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'icon', 'category', 'sort_order'])]
class Amenity extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    public const int NAME_MAX = 80;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['category' => AmenityCategory::class, 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'amenity_project')->withPivot('sort_order');
    }

    public static function binType(): string
    {
        return 'amenity';
    }

    public static function binTypeLabel(): string
    {
        return 'Amenity';
    }

    public static function binPermission(): string
    {
        return 'catalog.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUsageBlock(User $actor): ?string
    {
        $projects = $this->projects()->count();

        return $projects === 0 ? null : "“{$this->name}” is used by {$projects} ".($projects === 1 ? 'project' : 'projects').'. Remove it from them first.';
    }
}
