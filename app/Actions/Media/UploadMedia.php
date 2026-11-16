<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Jobs\Media\GenerateMediaConversions;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Media\MediaKind;
use App\Support\Media\SvgSanitizer;
use App\Support\Media\UploadInspector;
use App\Support\Media\UploadPurpose;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CMS-MED-003 / 004 / 011: stores one upload as a library item. The file is inspected by its
 * bytes (type, size, pixels, hero and video limits), an SVG is sanitised, the original is kept
 * under `media/{id}/` with a content-hash name, the metadata is read automatically and an image's
 * conversions are queued on the `media` queue (GenerateConversions).
 */
final class UploadMedia
{
    public function __construct(
        private readonly UploadInspector $inspector,
        private readonly SvgSanitizer $svgSanitizer,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  User|null  $actor  null for seeded and imported files, which have no uploader
     *
     * @throws ValidationException naming the limit the file exceeds
     */
    public function handle(?User $actor, UploadedFile $file, UploadPurpose $purpose = UploadPurpose::Library, ?int $folderId = null): MediaAsset
    {
        $originalName = $file->getClientOriginalName();
        $inspected = $this->inspector->inspect($file->getRealPath(), $originalName, $purpose);

        $path = $file->getRealPath();
        $width = $inspected->width;
        $height = $inspected->height;
        $temporary = null;

        if ($inspected->kind === MediaKind::Svg) {
            $clean = $this->svgSanitizer->clean($path);
            $temporary = tempnam(sys_get_temp_dir(), 'svg');
            File::put($temporary, $clean['svg']);
            $path = $temporary;
            $width = $clean['width'] ?: null;
            $height = $clean['height'] ?: null;
        }

        try {
            // NFR-SEC-12 / CMS-MED-011: a random-looking storage name that carries the content hash.
            $hash = substr((string) hash_file('sha1', $path), 0, 12);
            $title = Str::limit(trim(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'Untitled', 150, '');

            $asset = DB::transaction(function () use ($actor, $inspected, $path, $width, $height, $hash, $title, $originalName, $purpose, $folderId): MediaAsset {
                $asset = MediaAsset::query()->create([
                    'folder_id' => $folderId,
                    'kind' => $inspected->kind,
                    'title' => $title,
                    'is_decorative' => false,
                    'focal_x' => 50,
                    'focal_y' => 50,
                    'width' => $width,
                    'height' => $height,
                    'duration_s' => $inspected->durationSeconds === null ? null : (int) ceil($inspected->durationSeconds),
                    'conversions_status' => $inspected->kind === MediaKind::Image ? MediaAsset::STATUS_PENDING : MediaAsset::STATUS_READY,
                    'uploaded_by' => $actor?->getKey(),
                ]);

                $media = $asset->addMedia($path)
                    ->preservingOriginal()
                    ->usingName($title)
                    ->usingFileName("{$hash}.{$inspected->extension}")
                    ->withCustomProperties(['original_name' => $originalName, 'purpose' => $purpose->value])
                    ->toMediaCollection(MediaAsset::COLLECTION);

                $this->logActivity->handle('media', 'uploaded', 'Media uploaded', $actor, $asset, [
                    'title' => $title,
                    'kind' => $inspected->kind->value,
                    'bytes' => $inspected->bytes,
                ]);

                if ($inspected->kind === MediaKind::Image) {
                    GenerateMediaConversions::dispatch($media);
                }

                return $asset;
            });
        } finally {
            if ($temporary !== null) {
                File::delete($temporary);
            }
        }

        return $asset->load('media');
    }
}
