<?php

namespace App\Actions\Amenities;

use App\Actions\Activity\LogActivity;
use App\Models\Amenity;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * DM-AMEN: creates or edits an amenity from the dialog (ADM-SCR-03). The slug follows the name once
 * and then stays (the value is not a public URL); a new amenity goes to the end of the order.
 */
final class SaveAmenity
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated `name`, `category` and `icon`
     * @return array{amenity: Amenity, changed: bool}
     */
    public function handle(User $actor, array $data, ?Amenity $amenity = null): array
    {
        $creating = $amenity === null;
        $amenity ??= new Amenity(['sort_order' => ((int) Amenity::query()->max('sort_order')) + 1]);

        $amenity->fill([
            'name' => trim((string) $data['name']),
            'category' => $data['category'],
            'icon' => $data['icon'],
        ]);

        if ($creating) {
            $amenity->slug = $this->freeSlug($amenity->name);
        }

        $changed = $creating || $amenity->isDirty();

        if ($changed) {
            $amenity->save();

            $this->logActivity->handle(
                'amenities',
                $creating ? 'created' : 'updated',
                ($creating ? 'Amenity created: ' : 'Amenity updated: ').$amenity->name,
                $actor,
                $amenity,
                ['label' => $amenity->name],
            );
        }

        return ['amenity' => $amenity, 'changed' => $changed];
    }

    private function freeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'amenity';
        $candidate = $base;

        for ($number = 2; Amenity::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-{$number}";
        }

        return $candidate;
    }
}
