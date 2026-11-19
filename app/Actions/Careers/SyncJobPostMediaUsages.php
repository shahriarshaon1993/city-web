<?php

namespace App\Actions\Careers;

use App\Actions\Media\SyncMediaUsages;
use App\Models\JobPost;

/**
 * DM-MUSE / CMS-MED-008: records the SEO share image as a usage of the job post, so the media library
 * lists where it is used and refuses to delete it. The save Action calls this.
 */
final class SyncJobPostMediaUsages
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    public function handle(JobPost $job): void
    {
        $og = $job->seoMeta()->value('og_image_asset_id');

        $this->syncMediaUsages->handle($job, 'seo_og', $og === null ? [] : [(int) $og]);
    }
}
