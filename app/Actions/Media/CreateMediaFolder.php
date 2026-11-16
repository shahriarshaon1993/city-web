<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaFolder;
use App\Models\User;
use App\Support\Media\FolderRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-002: a new folder at the root or inside another, never deeper than 3 levels, with a
 * name that is unique among its siblings.
 */
final class CreateMediaFolder
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException on `name` or `parent_id`
     */
    public function handle(User $actor, string $name, ?int $parentId): MediaFolder
    {
        $name = FolderRules::cleanName($name);
        $parent = $parentId === null ? null : MediaFolder::query()->findOrFail($parentId);

        if ($parent !== null && $parent->depth() >= MediaFolder::MAX_DEPTH) {
            throw ValidationException::withMessages([
                'parent_id' => 'Folders go 3 levels deep at most. Create it one level higher.',
            ]);
        }

        FolderRules::ensureNameIsFree($name, $parent?->id);

        return DB::transaction(function () use ($actor, $name, $parent): MediaFolder {
            $folder = MediaFolder::query()->create([
                'parent_id' => $parent?->id,
                'name' => $name,
                'sort_order' => (int) MediaFolder::query()->where('parent_id', $parent?->id)->max('sort_order') + 1,
            ]);

            $this->logActivity->handle('media', 'folder_created', 'Media folder created', $actor, $folder, ['name' => $name]);

            return $folder;
        });
    }
}
