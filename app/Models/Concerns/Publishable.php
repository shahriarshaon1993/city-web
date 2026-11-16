<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * CMS-PUB-001: `publish_status` (cast to `PublishStatus`) and `published_at`. "Live" means published
 * and not dated in the future; drafts, scheduled and archived rows are never public.
 */
trait Publishable
{
    protected function initializePublishable(): void
    {
        $this->mergeCasts(['publish_status' => PublishStatus::class, 'published_at' => 'datetime']);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('publish_status'), PublishStatus::Published->value)
            ->where(fn (Builder $inner): Builder => $inner
                ->whereNull($this->qualifyColumn('published_at'))
                ->orWhere($this->qualifyColumn('published_at'), '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->publish_status === PublishStatus::Published
            && ($this->published_at === null || ! $this->published_at->isFuture());
    }
}
