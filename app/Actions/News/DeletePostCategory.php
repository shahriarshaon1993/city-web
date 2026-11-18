<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Models\PostCategory;
use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DEL-03: a category with posts cannot be deleted; with a target category (the dialog's "Move posts
 * to…") the posts move first, then the category goes to the Recycle bin. Restoring it does not move
 * the posts back.
 */
final class DeletePostCategory
{
    public function __construct(
        private readonly MoveToRecycleBin $moveToRecycleBin,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @throws ValidationException when posts use the category and there is no target
     */
    public function handle(User $actor, PostCategory $category, ?PostCategory $moveTo = null): RecycleBinEntry
    {
        return DB::transaction(function () use ($actor, $category, $moveTo): RecycleBinEntry {
            if ($moveTo !== null && $moveTo->isNot($category)) {
                $count = $category->posts()->count();
                $category->posts()->update(['category_id' => $moveTo->id]);

                if ($count > 0) {
                    $this->logActivity->handle('news', 'updated', "{$count} posts moved from {$category->name} to {$moveTo->name}", $actor, $category, ['posts' => $count, 'to' => $moveTo->name]);
                }
            }

            return $this->moveToRecycleBin->handle($actor, $category);
        });
    }
}
