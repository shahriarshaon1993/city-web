<?php

namespace App\Support\Publishing;

use App\Actions\Locations\SaveArea;
use App\Models\Location;
use App\Models\User;
use App\Queries\Admin\AreaEditor;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Areas in the publishing workflow (FR-LOC, CMS-PUB): the editor payload is `AreaEditor::form`,
 * applying it is `SaveArea`. An area has no publish-only rules; its indexability is advice
 * (FR-LOC-003), not a gate.
 */
final class AreaPublishing implements PublishingDriver
{
    public function __construct(
        private readonly AreaHydrator $hydrator,
        private readonly SaveArea $saveArea,
    ) {}

    public function type(): string
    {
        return 'location';
    }

    public function logName(): string
    {
        return 'locations';
    }

    public function noun(): string
    {
        return 'area';
    }

    public function label(Model $entity): string
    {
        return $this->area($entity)->name;
    }

    public function publicPath(Model $entity): string
    {
        return '/locations/'.$this->area($entity)->slug;
    }

    public function snapshot(Model $entity): array
    {
        return AreaEditor::form($this->area($entity));
    }

    public function hydrate(Model $entity, array $payload, bool $preview = false): Location
    {
        return $this->hydrator->handle($this->area($entity), $payload);
    }

    public function apply(?User $actor, Model $entity, array $payload, bool $log = true): Location
    {
        return $this->saveArea->handle($actor, $payload, $this->area($entity), $log);
    }

    public function checklist(Model $entity): array
    {
        return [];
    }

    public function fieldLabels(): array
    {
        return [
            'name' => 'Name', 'slug' => 'URL slug', 'parent_id' => 'City', 'intro' => 'Intro',
            'segment_intros' => 'Intro by segment', 'facts' => 'Facts', 'hero_asset_id' => 'Hero image',
            'latitude' => 'Latitude', 'longitude' => 'Longitude', 'zoom' => 'Map zoom', 'nearby_ids' => 'Nearby areas', 'seo' => 'SEO',
        ];
    }

    public function htmlFields(): array
    {
        return ['intro', 'segment_intros'];
    }

    private function area(Model $entity): Location
    {
        return $entity instanceof Location ? $entity : throw new InvalidArgumentException('Expected a location.');
    }
}
