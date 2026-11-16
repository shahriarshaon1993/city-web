<?php

namespace App\Actions\Amenities;

use App\Actions\Activity\LogActivity;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Models\Amenity;
use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DEL-03: an amenity that projects list cannot be deleted; with `detach` (a second confirm in the UI)
 * it is removed from every project first. Restoring it from the bin does not re-add it to them.
 */
final class DeleteAmenity
{
    public function __construct(
        private readonly MoveToRecycleBin $moveToRecycleBin,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @throws ValidationException when projects use the amenity and `detach` is false
     */
    public function handle(User $actor, Amenity $amenity, bool $detach = false): RecycleBinEntry
    {
        return DB::transaction(function () use ($actor, $amenity, $detach): RecycleBinEntry {
            if ($detach) {
                $count = $amenity->projects()->count();
                $amenity->projects()->detach();

                if ($count > 0) {
                    $this->logActivity->handle('amenities', 'updated', "Amenity removed from {$count} projects: {$amenity->name}", $actor, $amenity, ['projects' => $count]);
                }
            }

            return $this->moveToRecycleBin->handle($actor, $amenity);
        });
    }
}
