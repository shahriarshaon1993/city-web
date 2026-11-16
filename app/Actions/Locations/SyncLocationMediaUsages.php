<?php

namespace App\Actions\Locations;

use App\Actions\Media\SyncMediaUsages;
use App\Models\Location;

/**
 * DM-MUSE / DEC-78: writes the usage rows of a location's hero image and SEO share image, so the
 * library knows where they are used (CMS-MED-008). The location's save Action calls this.
 */
final class SyncLocationMediaUsages
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    public function handle(Location $location): void
    {
        $this->syncMediaUsages->handle($location, 'hero', $this->single($location->hero_asset_id));
        $this->syncMediaUsages->handle($location, 'seo_og', $this->single($location->seoMeta()->value('og_image_asset_id')));
    }

    /**
     * @return list<int>
     */
    private function single(?int $id): array
    {
        return $id === null ? [] : [$id];
    }
}
