<?php

namespace App\Queries\Site;

use App\Http\Resources\Site\ProjectDetailResource;
use App\Models\Location;
use App\Models\Project;
use App\Support\Catalog\CatalogCache;
use App\Support\Media\AssetMap;
use App\Support\Seo\ProjectMeta;
use App\Support\Settings\Settings;
use Illuminate\Database\Eloquent\Collection;

/**
 * PG-04 data of one published project: the `ProjectDetail` prop plus what the controller needs for
 * the head. Cached 24 hours under the catalogue version (CMS-CACHE-003); the enquiry panel and the
 * absolute URLs are added after the cache because they read Settings and the request host.
 *
 * @phpstan-import-type ProjectMetaInput from ProjectMeta
 *
 * `find()` answers only for a live project; `gone()` and `movedTo()` serve AC-PROJ-06 and SEO-TEC-003.
 */
final class ProjectDetail
{
    /** AC-PROJ-06: a deleted project answers 410 Gone this long, then 404. */
    public const int GONE_DAYS = 90;

    /**
     * @return array{project: array<string, mixed>, meta: ProjectMetaInput, seo: array<string, mixed>}|null
     */
    public static function find(string $slug): ?array
    {
        $payload = CatalogCache::remember("projects:detail:{$slug}", CatalogCache::PAGE_TTL, fn (): ?array => self::build($slug));

        if ($payload === null) {
            return null;
        }

        $project = $payload['project'];
        $base = $payload['enquiry'];
        $project['enquiry'] = [
            'heading' => $base['heading'],
            'whatsapp' => $base['whatsapp_override'] ?: (Settings::group('contact')['whatsapp_number'] ?? null),
            'whatsapp_text' => "Hi, I'm interested in {$base['name']}. ".url('/projects/'.$slug),
        ];

        return ['project' => $project, 'meta' => $payload['meta'], 'seo' => $payload['seo']];
    }

    /**
     * SEO-TEC-003: a project that used to have this slug, for the 301 to its current page.
     */
    public static function movedTo(string $slug): ?string
    {
        $project = Project::findBySlugHistory($slug);

        return $project !== null && $project->isPublished() ? $project->slug : null;
    }

    /**
     * AC-PROJ-06 for a published project in the Recycle bin: the redirect target an editor chose,
     * `true` for 410 Gone inside its 90 days, `null` (404) after them or for one that was never public.
     */
    public static function gone(string $slug): string|bool|null
    {
        $project = Project::onlyTrashed()->where('slug', $slug)->first();

        if ($project === null || $project->publish_status->value !== 'published' || $project->deleted_at === null) {
            return null;
        }

        if ($project->archived_redirect_to !== null && $project->archived_redirect_to !== '') {
            return $project->archived_redirect_to;
        }

        return $project->deleted_at->greaterThan(now()->subDays(self::GONE_DAYS)) ? true : null;
    }

    /**
     * @return array{project: array<string, mixed>, meta: ProjectMetaInput, seo: array<string, mixed>, enquiry: array<string, mixed>}|null
     */
    private static function build(string $slug): ?array
    {
        $project = Project::query()->published()->where('slug', $slug)
            ->with([
                'location.parent', 'cover.media', 'amenities', 'unitTypes', 'nearbyPlaces', 'documents',
                'progressUpdates.photos.media', 'seoMeta.ogImage.media',
            ])->first();

        if ($project === null) {
            return null;
        }

        $assets = AssetMap::load([
            $project->cover_asset_id,
            $project->video_poster_asset_id,
            $project->video_asset_id,
            $project->video_captions_asset_id,
            ...array_column($project->gallery ?? [], 'asset_id'),
            ...array_column($project->highlights ?? [], 'asset_id'),
            ...$project->unitTypes->pluck('floor_plan_asset_id')->all(),
            ...$project->documents->pluck('media_asset_id')->all(),
        ]);

        $detail = ProjectDetailResource::make($project, $assets, self::similar($project))->resolve();
        $seo = $project->seoMeta;
        $katha = $project->land_area_katha;

        return [
            'project' => $detail,
            // What the §9.3 title and description templates read (ProjectMeta).
            'meta' => [
                'name' => $project->name,
                'segment' => $project->segment->value,
                'lifecycle' => $project->lifecycle->value,
                'lifecycle_label' => $project->lifecycle->label(),
                'location' => ['area' => $project->location->name, 'city' => $project->location->parent->name ?? ''],
                'size_min_sft' => $project->size_min_sft,
                'size_max_sft' => $project->size_max_sft,
                'beds_min' => $project->beds_min,
                'beds_max' => $project->beds_max,
                'land_katha' => $katha !== null && floor($katha) === $katha ? (int) $katha : $katha,
                'handover_date' => $project->handover_date?->toDateString(),
            ],
            'seo' => [
                'meta_title' => $seo?->meta_title,
                'meta_description' => $seo?->meta_description,
                'robots' => $seo?->robots,
                'canonical_url' => $seo?->canonical_url,
                'og_image' => $seo?->ogImage?->ogUrl() ?? $project->cover?->ogUrl(),
            ],
            'enquiry' => [
                'name' => $project->name,
                'whatsapp_override' => $project->whatsapp_override,
                'heading' => $project->lifecycle->value === 'handed_over'
                    ? 'Interested in a resale or a similar project?'
                    : "Interested in {$project->name}?",
            ],
        ];
    }

    /**
     * AC-PROJ-07 / FR-PROJ-007: the editors' picks that are published, else up to 3 published
     * projects of the same segment and city, newest first, excluding the project itself.
     *
     * @return list<array<string, mixed>>
     */
    private static function similar(Project $project): array
    {
        $picks = $project->similar()->published()->with(ProjectCards::WITH)->get();

        if ($picks->isNotEmpty()) {
            return ProjectCards::cards($picks->take(Project::SIMILAR_MAX));
        }

        $cityId = $project->location->parent_id;
        $areaIds = $cityId === null
            ? [$project->location_id]
            : Location::query()->where('parent_id', $cityId)->pluck('id')->all();

        /** @var Collection<int, Project> $matches */
        $matches = ProjectCards::query()
            ->whereKeyNot($project->id)
            ->where('segment', $project->segment->value)
            ->whereIn('location_id', $areaIds)
            ->orderByDesc('published_at')
            ->limit(Project::SIMILAR_MAX)
            ->get();

        return ProjectCards::cards($matches);
    }
}
