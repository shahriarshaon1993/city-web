<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * DM-SLUGH (SEO-TEC-003): a slug an entity used to have. The public site answers a request to one of
 * these with a 301 to the entity's current URL. Written by the `HasSlugHistory` trait.
 *
 * @property int $id
 * @property string $sluggable_type
 * @property int $sluggable_id
 * @property string $slug
 * @property Carbon|null $created_at
 */
#[Fillable(['sluggable_type', 'sluggable_id', 'slug'])]
class SlugHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'slug_histories';

    /**
     * @return MorphTo<Model, $this>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
