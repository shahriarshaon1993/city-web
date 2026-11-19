<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-06: the drag order of testimonials, partners, FAQs, team members and offices. The ids given
 * take over the `sort_order` values they already hold, in the new order, so reordering one team group
 * (or one FAQ topic) leaves every other row where it was. Returns the previous order for Undo.
 */
final class ReorderSocialItems
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids  the rows being ordered, in the new order
     * @return list<int> the same rows in the order they had before
     */
    public function handle(User $actor, string $model, array $ids, string $log): array
    {
        return DB::transaction(function () use ($actor, $model, $ids, $log): array {
            $rows = $model::query()->whereKey($ids)->orderBy('sort_order')->orderBy('id')->get(['id', 'sort_order']);

            $previous = array_values($rows->pluck('id')->map(intval(...))->all());
            $slots = $rows->pluck('sort_order')->map(intval(...))->sort()->values()->all();

            // Only ids that exist take part (a row deleted in another tab is skipped).
            $ordered = array_values(array_filter($ids, fn (int $id): bool => in_array($id, $previous, true)));

            foreach ($ordered as $position => $id) {
                $model::query()->whereKey($id)->update(['sort_order' => $slots[$position]]);
            }

            if ($ordered !== $previous) {
                $this->logActivity->handle($log, 'updated', 'Order changed', $actor, null, ['count' => count($ordered)]);
            }

            return $previous;
        });
    }
}
