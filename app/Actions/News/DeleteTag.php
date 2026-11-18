<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Crud matrix: a tag is deleted for good (no Recycle bin) and detached from its posts; the dialog told
 * the editor how many posts it is on. Returns that number.
 */
final class DeleteTag
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Tag $tag): int
    {
        return DB::transaction(function () use ($actor, $tag): int {
            $count = DB::table('taggables')->where('tag_id', $tag->id)->count();
            $name = $tag->name;

            $tag->delete();

            $this->logActivity->handle('news', 'deleted', "Tag deleted: {$name}", $actor, null, ['label' => $name, 'posts' => $count]);

            return $count;
        });
    }
}
