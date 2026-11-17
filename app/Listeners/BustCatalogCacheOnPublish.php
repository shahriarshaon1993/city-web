<?php

namespace App\Listeners;

use App\Events\EntityPublished;
use App\Support\Catalog\CatalogCache;

/** CMS-CACHE-002: a publish raises the catalogue version, so cached lists and pages are rebuilt. */
final class BustCatalogCacheOnPublish
{
    public function handle(EntityPublished $event): void
    {
        CatalogCache::bust();
    }
}
