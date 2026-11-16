<?php

namespace App\Actions\Amenities;

use App\Actions\Activity\LogActivity;
use App\Models\Amenity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** CRUD-06: the library order of the amenities (lists and pickers). Returns the previous order for Undo. */
final class ReorderAmenities
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<int>  $ids  every amenity in the new order
     * @return list<int> the order before this change
     */
    public function handle(User $actor, array $ids): array
    {
        return DB::transaction(function () use ($actor, $ids): array {
            $previous = array_values(Amenity::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->map(intval(...))->all());

            foreach ($ids as $position => $id) {
                Amenity::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }

            if (array_slice($previous, 0, count($ids)) !== $ids) {
                $this->logActivity->handle('amenities', 'updated', 'Amenities reordered', $actor, null, ['count' => count($ids)]);
            }

            return $previous;
        });
    }
}
