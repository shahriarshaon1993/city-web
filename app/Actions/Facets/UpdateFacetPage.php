<?php

namespace App\Actions\Facets;

use App\Actions\Activity\LogActivity;
use App\Actions\Seo\SaveSeoMeta;
use App\Models\FacetPage;
use App\Models\User;
use App\Support\Html\BasicHtml;
use Illuminate\Support\Facades\DB;

/** ADM-SCR-05: the H1, intro and SEO of one facet landing page (the rows themselves are fixed). */
final class UpdateFacetPage
{
    public function __construct(
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of `UpdateFacetPageRequest`
     */
    public function handle(User $actor, FacetPage $facet, array $data): FacetPage
    {
        return DB::transaction(function () use ($actor, $facet, $data): FacetPage {
            $facet->fill([
                'title' => trim($data['title']),
                'intro' => BasicHtml::clean($data['intro'] ?? null),
            ])->save();

            $this->saveSeoMeta->handle($facet, $data['seo'] ?? null);

            $this->logActivity->handle('facet_pages', 'updated', "Facet page updated: {$facet->title}", $actor, $facet, ['label' => $facet->title]);

            return $facet;
        });
    }
}
