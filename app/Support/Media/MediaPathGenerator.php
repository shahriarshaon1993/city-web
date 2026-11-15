<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * CMS-MED-011: the original and its conversions live together in `media/{media id}/`. The folder
 * is keyed by the file row (not the library item), so a replaced file never shares a folder with
 * the one it replaces and deleting a file removes exactly its own folder.
 */
final class MediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return 'media/'.$media->getKey().'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}
