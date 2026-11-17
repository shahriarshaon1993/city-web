<?php

namespace App\Support\Publishing;

use App\Actions\Projects\SaveProject;
use App\Models\Project;
use App\Models\User;
use App\Queries\Admin\ProjectEditor;
use App\Support\Projects\PublishChecklist;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Projects in the publishing workflow (FR-PROJ, CMS-PUB): the editor payload is `ProjectEditor::form`,
 * applying it is `SaveProject`, the publish rules are `PublishChecklist`.
 */
final class ProjectPublishing implements PublishingDriver
{
    public function __construct(
        private readonly ProjectHydrator $hydrator,
        private readonly SaveProject $saveProject,
        private readonly PublishChecklist $checklist,
    ) {}

    public function type(): string
    {
        return 'project';
    }

    public function logName(): string
    {
        return 'projects';
    }

    public function noun(): string
    {
        return 'project';
    }

    public function label(Model $entity): string
    {
        return $this->project($entity)->name;
    }

    public function publicPath(Model $entity): string
    {
        return '/projects/'.$this->project($entity)->slug;
    }

    public function snapshot(Model $entity): array
    {
        return ProjectEditor::form($this->project($entity));
    }

    public function hydrate(Model $entity, array $payload, bool $preview = false): Project
    {
        return $this->hydrator->handle($this->project($entity), $payload, $preview);
    }

    public function apply(?User $actor, Model $entity, array $payload, bool $log = true): Project
    {
        return $this->saveProject->handle($actor, $payload, $this->project($entity), $log);
    }

    public function checklist(Model $entity): array
    {
        return $this->checklist->items($this->project($entity));
    }

    public function fieldLabels(): array
    {
        return [
            'name' => 'Name', 'slug' => 'URL slug', 'tagline' => 'Tagline', 'segment' => 'Segment', 'lifecycle' => 'Status',
            'booking_state' => 'Booking', 'location_id' => 'Area', 'is_featured' => 'Featured',
            'address' => 'Address', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'land_area_katha' => 'Land area (katha)',
            'storeys' => 'Storeys', 'total_units' => 'Total units', 'units_per_floor' => 'Units per floor', 'lifts' => 'Lifts',
            'car_parks' => 'Car parks', 'basements' => 'Basements', 'road_width_ft' => 'Road width (ft)', 'facing' => 'Facing',
            'orientation' => 'Orientation', 'launch_date' => 'Launch', 'handover_date' => 'Handover',
            'rajuk_approval_no' => 'RAJUK approval', 'architect' => 'Architect', 'structural_engineer' => 'Structural engineer',
            'contractor' => 'Contractor', 'custom_facts' => 'Extra facts', 'overview' => 'Overview', 'design_narrative' => 'Design narrative',
            'highlights' => 'Highlights', 'cover_asset_id' => 'Cover image', 'gallery' => 'Gallery', 'video_type' => 'Video type',
            'video_url' => 'Video link', 'video_asset_id' => 'Video file', 'video_poster_asset_id' => 'Video poster',
            'video_captions_asset_id' => 'Video captions', 'virtual_tour_url' => 'Virtual tour', 'unit_types' => 'Unit types',
            'amenity_ids' => 'Amenities', 'progress_updates' => 'Progress updates', 'nearby_places' => 'Nearby places',
            'documents' => 'Documents', 'brochure_gated' => 'Brochure behind a form', 'show_price' => 'Show prices',
            'price_from_manual' => 'Price set by hand', 'price_from_bdt' => 'Price from (BDT)', 'price_note' => 'Price note',
            'default_assignee_id' => 'Default assignee', 'whatsapp_override' => 'WhatsApp number', 'similar_ids' => 'Similar projects',
            'seo' => 'SEO',
        ];
    }

    public function htmlFields(): array
    {
        return ['overview', 'design_narrative'];
    }

    private function project(Model $entity): Project
    {
        return $entity instanceof Project ? $entity : throw new InvalidArgumentException('Expected a project.');
    }
}
