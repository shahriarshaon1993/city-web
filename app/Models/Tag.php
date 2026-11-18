<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * DM-TAG: a free-entry tag (FR-NEWS-003). Names are unique case-insensitively (the column collation),
 * so a second "nrb" is the existing "NRB". A tag has no Recycle bin: it is deleted for good and
 * detached from its posts (crud.md matrix).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    public const int NAME_MAX = 50;

    /**
     * @return MorphToMany<Post, $this>
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function path(): string
    {
        return '/news/tag/'.$this->slug;
    }
}
