<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\UploadMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Http\Resources\Admin\MediaAssetResource;
use Illuminate\Http\JsonResponse;

/**
 * Media library endpoints (ADM-SCR-13). 11.1 holds the upload; the library screen, picker and folder
 * management arrive in 11.2.
 */
class MediaController extends Controller
{
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

        return (new MediaAssetResource($asset))->response()->setStatusCode(201);
    }
}
