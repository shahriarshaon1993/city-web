<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaFolder;
use App\Models\User;
use App\Support\Media\FolderRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-002 move: a folder takes its subfolders and files along. Refused when the result would
 * be deeper than 3 levels, when the target is the folder itself or one of its subfolders, or when
 * the target already holds a folder of that name.
 */
final class MoveMediaFolder
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException on `parent_id`
     */
    public function handle(User $actor, MediaFolder $folder, ?int $parentId): MediaFolder
    {
        if ($parentId === $folder->parent_id) {
            return $folder;
        }

        $parent = $parentId === null ? null : MediaFolder::query()->findOrFail($parentId);

        if ($parent !== null && in_array($parent->id, $folder->subtreeIds(), true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A folder cannot move into itself or into one of its own subfolders.',
            ]);
        }

        $depth = ($parent?->depth() ?? 0) + $folder->height();

        if ($depth > MediaFolder::MAX_DEPTH) {
            throw ValidationException::withMessages([
                'parent_id' => 'There it would sit '.$depth.' levels deep; folders go 3 levels deep at most.',
            ]);
        }

        FolderRules::ensureNameIsFree($folder->name, $parent?->id, $folder->id, 'parent_id');

        DB::transaction(function () use ($actor, $folder, $parent): void {
            $folder->update([
                'parent_id' => $parent?->id,
                'sort_order' => (int) MediaFolder::query()->where('parent_id', $parent?->id)->max('sort_order') + 1,
            ]);

            $this->logActivity->handle('media', 'folder_moved', 'Media folder moved', $actor, $folder, [
                'to' => $parent->name ?? 'top level',
            ]);
        });

        return $folder;
    }
}
