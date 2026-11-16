<?php

namespace App\Actions\Locations;

use App\Actions\Activity\LogActivity;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ADM-SCR-04 / CRUD-06: the order of the cities, or of the areas inside one city. An area keeps
 * its city when dragged; moving it to another city is done in its editor. Returns the previous order for Undo.
 */
final class ReorderLocations
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  int|null  $parentId  null = the cities
     * @param  list<int>  $ids  in the new order
     * @return list<int> the order of the same list before this change
     *
     * @throws ValidationException when an id does not belong to the list
     */
    public function handle(User $actor, ?int $parentId, array $ids): array
    {
        return DB::transaction(function () use ($actor, $parentId, $ids): array {
            $siblings = Location::query()->where('parent_id', $parentId);
            $previous = array_values((clone $siblings)->orderBy('sort_order')->orderBy('name')->pluck('id')->map(intval(...))->all());

            if (array_diff($ids, $previous) !== []) {
                throw ValidationException::withMessages(['ids' => 'These items are not in the same list.']);
            }

            foreach ($ids as $position => $id) {
                Location::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }

            if (array_slice($previous, 0, count($ids)) !== $ids) {
                $this->logActivity->handle('locations', 'updated', $parentId === null ? 'Cities reordered' : 'Areas reordered', $actor, null, ['parent_id' => $parentId]);
            }

            return $previous;
        });
    }
}
