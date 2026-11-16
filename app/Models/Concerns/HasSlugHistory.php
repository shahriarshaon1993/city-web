<?php

namespace App\Models\Concerns;

use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * SEO-TEC-003 / DM-SLUGH: a slug change stores the old slug, so requests to it can 301 to the
 * current URL. History rows always point at the entity itself (old → current, never old → older),
 * and a slug that comes back into use stops being history. The slug column is `slug`; the model
 * must use SoftDeletes (history is kept while the item is in the Recycle bin, dropped on delete forever).
 */
trait HasSlugHistory
{
    /**
     * @return MorphMany<SlugHistory, $this>
     */
    public function slugHistories(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }

    protected static function bootHasSlugHistory(): void
    {
        static::updating(function (self $model): void {
            if (! $model->isDirty('slug')) {
                return;
            }

            $old = (string) $model->getOriginal('slug');

            if ($old === '') {
                return;
            }

            $model->slugHistories()->where('slug', $model->slug)->delete();

            SlugHistory::query()->updateOrCreate(
                ['sluggable_type' => $model->getMorphClass(), 'slug' => $old],
                ['sluggable_id' => $model->getKey()],
            );
        });

        static::deleted(function (self $model): void {
            if ($model->isForceDeleting()) {
                $model->slugHistories()->delete();
            }
        });
    }

    /** The entity that used to have this slug, for the 301 of SEO-TEC-003. */
    public static function findBySlugHistory(string $slug): ?static
    {
        $history = SlugHistory::query()
            ->where('sluggable_type', static::query()->getModel()->getMorphClass())
            ->where('slug', $slug)
            ->first();

        return $history === null ? null : static::query()->find($history->sluggable_id);
    }
}
