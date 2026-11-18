<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** CRUD-06: the order of the categories on the news pages (FR-NEWS-002). Returns the previous order for Undo. */
final class ReorderPostCategories
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<int>  $ids  every category in the new order
     * @return list<int> the order before this change
     */
    public function handle(User $actor, array $ids): array
    {
        return DB::transaction(function () use ($actor, $ids): array {
            $previous = array_values(PostCategory::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->map(intval(...))->all());

            foreach ($ids as $position => $id) {
                PostCategory::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }

            if (array_slice($previous, 0, count($ids)) !== $ids) {
                $this->logActivity->handle('news', 'updated', 'News categories reordered', $actor, null, ['count' => count($ids)]);
            }

            return $previous;
        });
    }
}
