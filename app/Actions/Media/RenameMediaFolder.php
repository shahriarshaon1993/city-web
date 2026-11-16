<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaFolder;
use App\Models\User;
use App\Support\Media\FolderRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-002 rename (inline in the folder tree, CRUD matrix "Inline").
 */
final class RenameMediaFolder
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException on `name`
     */
    public function handle(User $actor, MediaFolder $folder, string $name): MediaFolder
    {
        $name = FolderRules::cleanName($name);

        if ($name === $folder->name) {
            return $folder;
        }

        FolderRules::ensureNameIsFree($name, $folder->parent_id, $folder->id);

        DB::transaction(function () use ($actor, $folder, $name): void {
            $old = $folder->name;
            $folder->update(['name' => $name]);

            $this->logActivity->handle('media', 'folder_renamed', 'Media folder renamed', $actor, $folder, ['from' => $old, 'to' => $name]);
        });

        return $folder;
    }
}
