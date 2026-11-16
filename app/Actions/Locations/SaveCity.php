<?php

namespace App\Actions\Locations;

use App\Actions\Activity\LogActivity;
use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Location;
use App\Models\User;

/**
 * FR-LOC-001: creates or edits a city from the dialog. A city is always published (the directory
 * hides one without a published area on its own); it goes to the end of the order.
 */
final class SaveCity
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated `name` and `slug`
     * @return array{location: Location, changed: bool}
     */
    public function handle(User $actor, array $data, ?Location $city = null): array
    {
        $creating = $city === null;
        $city ??= new Location([
            'type' => LocationType::City,
            'publish_status' => PublishStatus::Published,
            'published_at' => now(),
            'zoom' => 11,
            'sort_order' => ((int) Location::query()->cities()->max('sort_order')) + 1,
        ]);

        $city->fill(['name' => trim((string) $data['name']), 'slug' => $data['slug']]);
        $changed = $creating || $city->isDirty();

        if ($changed) {
            $city->save();

            $this->logActivity->handle(
                'locations',
                $creating ? 'created' : 'updated',
                ($creating ? 'City created: ' : 'City updated: ').$city->name,
                $actor,
                $city,
                ['label' => $city->name],
            );
        }

        return ['location' => $city, 'changed' => $changed];
    }
}
