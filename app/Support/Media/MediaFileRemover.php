<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;

/**
 * Spatie removes the original and the conversions it knows about. `GenerateConversions` writes its
 * own files next to the original (CMS-MED-011), so deleting a file removes its whole folder
 * `media/{id}/` — that folder holds nothing else (see MediaPathGenerator).
 */
final class MediaFileRemover extends DefaultFileRemover
{
    public function removeAllFiles(Media $media): void
    {
        try {
            $this->filesystem->disk($media->disk)->deleteDirectory($this->mediaFileSystem->getMediaDirectory($media));
        } catch (\Throwable $exception) {
            report($exception);
        }

        parent::removeAllFiles($media);
    }
}
