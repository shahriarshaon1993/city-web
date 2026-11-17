<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * CMS-PUB workflow: an entity went live, or its pending changes did. Dispatched after the commit.
 * 14.1 only busts the catalogue cache; the sitemaps (19.2) and the snapshot render (19.3) listen
 * here later, as AC-PROJ-04 asks.
 */
final class EntityPublished
{
    use Dispatchable;

    public function __construct(public readonly Model $entity, public readonly bool $firstPublish) {}
}
