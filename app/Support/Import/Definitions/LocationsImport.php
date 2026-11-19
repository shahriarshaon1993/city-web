<?php

namespace App\Support\Import\Definitions;

use App\Actions\Locations\SaveArea;
use App\Actions\Locations\SaveCity;
use App\Models\Location;
use App\Models\User;
use App\Support\Import\ImportDefinition;
use App\Support\Import\ImportField;
use App\Support\Import\ImportValues;
use App\Support\Import\RowResult;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Support\Str;

/**
 * ADM-IO-001 locations (FR-LOC-001): cities and areas. Cities are published at once like the dialog
 * does; areas arrive as drafts, on the default map zoom, to be finished in their editor. A city or area
 * whose URL exists already is skipped, never changed. An area may name a city that comes earlier in the
 * same file.
 */
final class LocationsImport extends ImportDefinition
{
    private const int AREA_ZOOM = 14;

    /** @var array<string, true> cities (lower case name) that earlier rows of this file create */
    private array $citiesInFile = [];

    public function help(): string
    {
        return 'Cities and the areas inside them. Areas arrive as drafts, so nothing appears on the site until you publish it.';
    }

    public function fields(): array
    {
        return [
            new ImportField('name', 'Name', true, ['city', 'area', 'location', 'location name'], 'Bashundhara R/A'),
            new ImportField('type', 'Type', false, ['kind'], 'area', 'city or area; empty = area when a city is given, otherwise city'),
            new ImportField('city', 'City', false, ['parent', 'parent city', 'in city'], 'Dhaka', 'Required for an area: the city it is in'),
            new ImportField('slug', 'URL', false, ['slug', 'url slug'], 'bashundhara-ra', 'Empty = made from the name'),
            new ImportField('intro', 'Introduction', false, ['description', 'about', 'intro'], 'A planned residential area in north-east Dhaka.'),
            new ImportField('latitude', 'Latitude', false, ['lat'], '23.8193'),
            new ImportField('longitude', 'Longitude', false, ['lng', 'lon', 'long'], '90.4526'),
        ];
    }

    public function check(array $row): RowResult
    {
        $name = ImportValues::text($row['name'] ?? '', 100);

        if ($name === null || mb_strlen($name) < 2) {
            return RowResult::fail('The name needs at least 2 characters.');
        }

        $cityName = ImportValues::text($row['city'] ?? '', 100);
        $type = mb_strtolower(trim($row['type'] ?? ''));
        $type = $type === '' ? ($cityName === null ? 'city' : 'area') : $type;

        if (! in_array($type, ['city', 'area'], true)) {
            return RowResult::fail('Type must be city or area.');
        }

        $slug = trim($row['slug'] ?? '') === '' ? Str::slug($name) : trim($row['slug']);

        if ($slug === '' || preg_match(SlugAvailability::PATTERN, $slug) !== 1 || mb_strlen($slug) > 100) {
            return RowResult::fail('The URL must be lower-case letters, numbers and single hyphens, e.g. bashundhara-ra.');
        }

        $holder = Location::withTrashed()->where('slug', $slug)->first();

        if ($holder?->trashed()) {
            return RowResult::fail("This URL belongs to “{$holder->name}” in the Recycle bin. Restore it or delete it forever first.");
        }

        if ($holder !== null) {
            return RowResult::skip("A city or area already uses the URL {$slug}.");
        }

        if ($type === 'city') {
            $this->citiesInFile[mb_strtolower($name)] = true;

            return RowResult::create(['type' => 'city', 'name' => $name, 'slug' => $slug]);
        }

        if ($cityName === null) {
            return RowResult::fail('An area needs the city it is in.');
        }

        if ($this->cityId($cityName) === null && ! isset($this->citiesInFile[mb_strtolower($cityName)])) {
            return RowResult::fail("No city called “{$cityName}”. Add the city first, or put its row above this one.");
        }

        $latitude = $this->coordinate($row['latitude'] ?? '', 20.5, 26.7);
        $longitude = $this->coordinate($row['longitude'] ?? '', 88.0, 92.7);

        if ($latitude === false) {
            return RowResult::fail('Latitude must be a number inside Bangladesh (20.5 to 26.7).');
        }

        if ($longitude === false) {
            return RowResult::fail('Longitude must be a number inside Bangladesh (88.0 to 92.7).');
        }

        return RowResult::create([
            'type' => 'area',
            'name' => $name,
            'slug' => $slug,
            'city' => $cityName,
            'intro' => ImportValues::text($row['intro'] ?? '', 60000),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    protected function write(User $actor, RowResult $checked, array $options): RowResult
    {
        $data = $checked->data;

        if ($data['type'] === 'city') {
            app(SaveCity::class)->handle($actor, ['name' => $data['name'], 'slug' => $data['slug']]);

            return $checked;
        }

        $parentId = $this->cityId((string) $data['city']);

        if ($parentId === null) {
            return RowResult::fail("No city called “{$data['city']}”.");
        }

        app(SaveArea::class)->handle($actor, [
            'parent_id' => $parentId,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'intro' => $data['intro'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'zoom' => self::AREA_ZOOM,
            'segment_intros' => [],
            'facts' => [],
            'nearby_ids' => [],
            'seo' => null,
        ]);

        return $checked;
    }

    private function cityId(string $name): ?int
    {
        return Location::query()->cities()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
    }

    /** @return float|false|null null = empty, false = not valid */
    private function coordinate(string $value, float $min, float $max): float|false|null
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) && (float) $value >= $min && (float) $value <= $max ? (float) $value : false;
    }
}
