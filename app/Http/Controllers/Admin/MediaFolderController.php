<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\CreateMediaFolder;
use App\Actions\Media\DeleteMediaFolder;
use App\Actions\Media\MoveMediaFolder;
use App\Actions\Media\RenameMediaFolder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMediaFolderRequest;
use App\Models\MediaFolder;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-002 folders, edited inline in the library's folder tree (CRUD matrix: Inline, Hard
 * delete only when empty). Depth / name refusals come back as field errors for the inline input.
 */
class MediaFolderController extends Controller
{
    public function store(SaveMediaFolderRequest $request, CreateMediaFolder $createMediaFolder): RedirectResponse
    {
        $folder = $createMediaFolder->handle(
            $request->user(),
            $request->string('name')->toString(),
            $request->integer('parent_id') ?: null,
        );

        Toast::success('Folder created')->description($folder->name)->flash();

        return back();
    }

    public function rename(SaveMediaFolderRequest $request, MediaFolder $folder, RenameMediaFolder $renameMediaFolder): RedirectResponse
    {
        $old = $folder->name;
        $renameMediaFolder->handle($request->user(), $folder, $request->string('name')->toString());

        $old === $folder->name
            ? Toast::info('No changes to save')->flash()
            : Toast::success('Folder renamed')->description("“{$old}” → “{$folder->name}”")->flash();

        return back();
    }

    public function move(SaveMediaFolderRequest $request, MediaFolder $folder, MoveMediaFolder $moveMediaFolder): RedirectResponse
    {
        $moveMediaFolder->handle($request->user(), $folder, $request->integer('parent_id') ?: null);

        Toast::success('Folder moved')
            ->description($folder->name.' → '.($folder->parent->name ?? 'top level'))
            ->flash();

        return back();
    }

    /** DEL-09: deleted for good; the confirm dialog says so. */
    public function destroy(Request $request, MediaFolder $folder, DeleteMediaFolder $deleteMediaFolder): RedirectResponse
    {
        abort_unless($request->user()->can('media.update'), 403);

        try {
            $deleteMediaFolder->handle($request->user(), $folder);
        } catch (ValidationException $exception) {
            Toast::error($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success('Folder deleted')->description($folder->name)->flash();

        return back();
    }
}
