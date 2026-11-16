<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\MoveMediaAssets;
use App\Actions\Media\ReplaceMediaEverywhere;
use App\Actions\Media\UpdateMediaAsset;
use App\Actions\Media\UploadMedia;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkMediaRequest;
use App\Http\Requests\Admin\ReplaceMediaRequest;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Http\Resources\Admin\MediaAssetDetailResource;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Queries\Admin\MediaTable;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Media\FolderTree;
use App\Support\Media\MediaLimits;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-13 media library (CMP-ADM-MEDIA): the screen, the picker's JSON feed (CMP-ADM-PICKER),
 * the detail drawer, upload, metadata, bulk move / delete and "Replace everywhere". Controllers
 * only authorise, validate, call an Action and respond (DEC-32); one toast per mutation.
 */
class MediaController extends Controller
{
    public function index(Request $request, MediaTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', MediaAsset::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/media/Index', [
            ...$table->props($request, $actor),
            'folders' => fn (): array => FolderTree::build(),
            'limits' => MediaLimits::toArray(),
            'openAsset' => $request->integer('asset') ?: null,
            'canShareViews' => $actor->can('views.share'),
            'binCount' => fn (): int => RecycleBinTable::countFor($actor, MediaAsset::binType()),
            'breadcrumbs' => [
                ['title' => 'Library', 'href' => null],
                ['title' => 'Media', 'href' => null],
            ],
        ]);
    }

    /**
     * CMP-ADM-PICKER: the same list as the screen, as JSON, so any editor can open the picker
     * without leaving its page. Accepts the table parameters (search, filter, sort, page, per_page).
     */
    public function browse(Request $request, MediaTable $table): JsonResponse
    {
        Gate::authorize('viewAny', MediaAsset::class);

        $props = $table->props($request, $request->user());

        return response()->json([
            'rows' => $props['rows'],
            'meta' => $props['meta'],
            'folders' => $request->boolean('with_folders') ? FolderTree::build() : null,
            'limits' => $request->boolean('with_folders') ? MediaLimits::toArray() : null,
        ]);
    }

    /** The detail drawer: the item, its usages (CMS-MED-007) and the `og` crop. */
    public function show(MediaAsset $asset): MediaAssetDetailResource
    {
        Gate::authorize('viewAny', MediaAsset::class);

        return new MediaAssetDetailResource($asset->load(['media', 'folder:id,name', 'uploader:id,name'])->loadCount('usages'));
    }

    /**
     * CMS-MED-003: stores one file and answers with the new item; conversions follow on the `media` queue.
     */
    public function store(UploadMediaRequest $request, UploadMedia $uploadMedia): JsonResponse
    {
        $asset = $uploadMedia->handle(
            $request->user(),
            $request->file('file'),
            $request->purpose(),
            $request->integer('folder_id') ?: null,
        );

        return (new MediaAssetResource($asset->loadCount('usages')))->response()->setStatusCode(201);
    }

    /**
     * TOAST-05: the dropzone sends files one by one, then asks for this single summary toast.
     */
    public function uploaded(Request $request): RedirectResponse
    {
        Gate::authorize('create', MediaAsset::class);

        $count = max(0, $request->integer('count'));
        $images = max(0, $request->integer('images'));
        $failed = max(0, $request->integer('failed'));

        $title = $count === 1 ? '1 file uploaded' : "{$count} files uploaded";
        $toast = $failed > 0 ? Toast::warning("{$title} · {$failed} refused") : Toast::success($title);

        if ($images > 0) {
            $toast->description('Image sizes are being prepared in the background; tiles update when they are ready.');
        } elseif ($failed > 0) {
            $toast->description('The upload list says why each refused file was not accepted.');
        }

        $toast->flash();

        return back();
    }

    public function update(UpdateMediaAssetRequest $request, MediaAsset $asset, UpdateMediaAsset $updateMediaAsset): RedirectResponse
    {
        $changed = $updateMediaAsset->handle($request->user(), $asset, $request->metadata());

        $toast = $changed === [] ? Toast::info('No changes to save') : Toast::success('Media saved')->description($asset->title);

        if (array_intersect($changed, ['focal_x', 'focal_y']) !== [] && $asset->kind->value === 'image') {
            $toast->description('The social-share crop is being redone around the new focal point.');
        }

        $toast->flash();

        return back();
    }

    /** CMS-MED-008 / DEL-02: an unused file moves to the Recycle bin for 30 days, with Undo. */
    public function destroy(Request $request, MediaAsset $asset, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $asset);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $asset);
        } catch (ValidationException $exception) {
            return $this->refused($exception);
        }

        Toast::success('File moved to Recycle bin')
            ->description($asset->title.' · removed for good after 30 days')
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function bulk(BulkMediaRequest $request, MediaTable $table, MoveMediaAssets $moveMediaAssets, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $assets = $table->selection($request, $request->user())->limit(BulkMediaRequest::MAX_IDS * 20)->get();
        $noun = fn (int $count): string => $count === 1 ? 'file' : 'files';

        if ($request->input('action') === 'move') {
            $folder = $request->filled('folder_id') ? MediaFolder::query()->findOrFail($request->integer('folder_id')) : null;
            $moved = $moveMediaAssets->handle($request->user(), $assets, $folder);

            Toast::success($moved === 0 ? 'No files were moved' : "{$moved} {$noun($moved)} moved")
                ->description('To '.($folder->name ?? 'Unfiled'))
                ->flash();

            return back();
        }

        $result = $moveMany->handle($request->user(), $assets);
        $count = count($result['entries']);
        $title = $count === 0 ? 'No files were deleted' : "{$count} {$noun($count)} moved to Recycle bin";

        // TOAST-06: partial success names the skipped (used) files.
        $toast = $result['refused'] === []
            ? Toast::success($title)
            : Toast::warning($title.' · '.count($result['refused']).' skipped')->description(implode(' ', array_slice($result['refused'], 0, 2)));

        if ($count > 0) {
            $toast->undo(route('admin.recycle-bin.restore-many'), [
                'ids' => array_map(fn ($entry): int => $entry->id, $result['entries']),
                'undo' => true,
            ]);
        }

        $toast->flash();

        return back();
    }

    /**
     * CMS-MED-008 "Replace everywhere"; with `delete_after` (the delete dialog's path) the freed
     * file then moves to the Recycle bin.
     */
    public function replace(ReplaceMediaRequest $request, MediaAsset $asset, ReplaceMediaEverywhere $replace, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        $replacement = MediaAsset::query()->findOrFail($request->integer('replacement_id'));

        $moved = $replace->handle($request->user(), $asset, $replacement);
        $places = $moved === 1 ? '1 place' : "{$moved} places";

        if (! $request->boolean('delete_after')) {
            Toast::success("Replaced in {$places}")->description("“{$asset->title}” → “{$replacement->title}”")->flash();

            return back();
        }

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $asset->refresh());
        } catch (ValidationException $exception) {
            Toast::warning("Replaced in {$places}, but not deleted")->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success("Replaced in {$places} and moved to Recycle bin")
            ->description("“{$asset->title}” → “{$replacement->title}”")
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    /** A guard refusal the button should already have explained; shown as an error toast (TOAST-03). */
    private function refused(ValidationException $exception): RedirectResponse
    {
        Toast::error($exception->validator->errors()->first())->flash();

        return back();
    }
}
