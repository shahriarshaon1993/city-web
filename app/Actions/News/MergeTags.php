<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-NEWS-003: merges tags into one. Every post that had a merged tag gets the target (once) and the merged
 * tags are deleted. Returns how many posts were re-tagged.
 */
final class MergeTags
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<int>  $sourceIds  tags to fold into the target
     */
    public function handle(User $actor, Tag $target, array $sourceIds): int
    {
        return DB::transaction(function () use ($actor, $target, $sourceIds): int {
            $sources = Tag::query()->whereIn('id', $sourceIds)->whereKeyNot($target->id)->get();
            $moved = 0;

            foreach ($sources as $source) {
                $rows = DB::table('taggables')->where('tag_id', $source->id)->get();

                foreach ($rows as $row) {
                    $inserted = DB::table('taggables')->insertOrIgnore([
                        'tag_id' => $target->id,
                        'taggable_type' => $row->taggable_type,
                        'taggable_id' => $row->taggable_id,
                    ]);
                    $moved += $inserted;
                }

                $source->delete();
            }

            if ($sources->isNotEmpty()) {
                $this->logActivity->handle(
                    'news',
                    'updated',
                    'Tags merged into '.$target->name.': '.$sources->pluck('name')->implode(', '),
                    $actor,
                    $target,
                    ['label' => $target->name, 'merged' => $sources->pluck('name')->all()],
                );
            }

            return $moved;
        });
    }
}
