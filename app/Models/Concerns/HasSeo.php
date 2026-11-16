<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A publishable entity with SEO fields (DM-SEO, spec/cms.md §8.5). The row exists only once an
 * editor fills a field; until then the §9.3 templates apply.
 */
trait HasSeo
{
    /**
     * @return MorphOne<SeoMeta, $this>
     */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    protected static function bootHasSeo(): void
    {
        // A forever-deleted entity takes its SEO row with it; a soft delete keeps it for the restore.
        static::deleted(function (self $model): void {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->seoMeta()->delete();
            }
        });
    }
}
