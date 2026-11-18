<?php

namespace App\Models;

use App\Models\Concerns\HasSlugHistory;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-PCAT: a news category (FR-NEWS-002); exactly one per post. Delete = Recycle bin, blocked while
 * posts use it unless they are moved to another category first (crud.md matrix, DEL-03).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'description', 'sort_order'])]
class PostCategory extends Model implements Trashable
{
    use BinBehaviour, HasSlugHistory, SoftDeletes;

    public const int NAME_MAX = 60;

    public const int DESCRIPTION_MAX = 300;

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public function path(): string
    {
        return '/news/category/'.$this->slug;
    }

    public static function binType(): string
    {
        return 'post_category';
    }

    public static function binTypeLabel(): string
    {
        return 'News category';
    }

    public static function binPermission(): string
    {
        return 'news.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    /** DEL-03: the dialog on the categories screen offers "Move posts to…"; the bin itself refuses while posts remain. */
    public function binUsageBlock(User $actor): ?string
    {
        $posts = $this->posts()->count();

        return $posts === 0 ? null : "“{$this->name}” has {$posts} ".($posts === 1 ? 'post' : 'posts').'. Move them to another category first.';
    }
}
