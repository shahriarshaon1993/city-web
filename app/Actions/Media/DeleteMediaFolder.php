<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaFolder;
use App\Models\User;
use App\Support\Media\FolderRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-002 / DEL-09: a folder is deleted for good, and only when it is empty. Files waiting in
 * the Recycle bin do not count; restoring one later puts it in "Unfiled" (the FK nulls).
 */
final class DeleteMediaFolder
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException on `folder` when it still holds something
     */
    public function handle(User $actor, MediaFolder $folder): void
    {
        $block = FolderRules::deleteBlock($folder);

        if ($block !== null) {
            throw ValidationException::withMessages(['folder' => $block]);
        }

        DB::transaction(function () use ($actor, $folder): void {
            $this->logActivity->handle('media', 'folder_deleted', 'Media folder deleted', $actor, null, [
                'name' => $folder->name,
                'id' => $folder->id,
            ]);

            $folder->delete();
        });
    }
}
