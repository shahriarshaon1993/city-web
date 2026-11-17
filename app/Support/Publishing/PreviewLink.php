<?php

namespace App\Support\Publishing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * CMS-PUB-005: `/preview/{type}/{id}?revision={rev}&signature=…`, valid 60 minutes and shareable with
 * reviewers who have no account. Without `revision` the page shows the pending revision, else the
 * saved entity.
 */
final class PreviewLink
{
    public const int MINUTES = 60;

    public static function make(Model $entity, ?int $revisionId = null): string
    {
        return URL::temporarySignedRoute(
            'preview.show',
            now()->addMinutes(self::MINUTES),
            array_filter(['type' => $entity->getMorphClass(), 'id' => $entity->getKey(), 'revision' => $revisionId]),
        );
    }
}
