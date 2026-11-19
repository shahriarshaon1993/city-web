<?php

namespace Database\Seeders;

use App\Enums\FacetKind;
use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Models\Amenity;
use App\Models\FacetPage;
use Database\Seeders\Data\DemoProjectDetail;
use Database\Seeders\Data\DemoProjects;
use Database\Seeders\Data\FacetSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The fixed catalogue the project pages need: the amenities of DM-AMEN and the eight facet landing pages
 * of DM-FACET (one per segment and lifecycle value). Text comes from the approved frontend's demo copy,
 * which the client edits in the admin from 13.2. Safe to run again: existing rows keep their text.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->amenities();
        $this->facetPages();
    }

    /** One row per amenity the demo projects list, in the order a residential building shows them. */
    private function amenities(): void
    {
        $order = 0;

        foreach (DemoProjects::rows() as $row) {
            foreach (DemoProjectDetail::find($row['slug'])['amenities'] ?? [] as $amenity) {
                Amenity::query()->firstOrCreate(
                    ['slug' => Str::slug($amenity['name'])],
                    ['name' => $amenity['name'], 'icon' => $amenity['icon'], 'category' => $amenity['category'], 'sort_order' => $order++],
                );
            }
        }
    }

    private function facetPages(): void
    {
        $facets = FacetSeedData::all();
        $order = 0;

        $values = [
            ...array_map(fn (Segment $segment): array => [FacetKind::Segment, $segment->value], Segment::cases()),
            ...array_map(fn (Lifecycle $lifecycle): array => [FacetKind::Status, $lifecycle->value], Lifecycle::cases()),
        ];

        foreach ($values as [$kind, $value]) {
            $demo = $facets["{$kind->value}:{$value}"] ?? null;

            if ($demo === null) {
                continue;
            }

            $page = FacetPage::query()->firstOrCreate(
                ['kind' => $kind->value, 'value' => $value],
                ['title' => $demo['title'], 'intro' => '<p>'.e($demo['intro']).'</p>', 'sort_order' => $order],
            );

            if ($page->wasRecentlyCreated) {
                $page->seoMeta()->create(['meta_description' => Str::limit($demo['description'], 170, '')]);
            }

            $order++;
        }
    }
}
