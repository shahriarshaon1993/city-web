<?php

namespace App\Support\Media;

/**
 * What the upload inspector learned from the file's bytes.
 */
final readonly class InspectedUpload
{
    public function __construct(
        public MediaKind $kind,
        public string $mime,
        public string $extension,
        public int $bytes,
        public ?int $width,
        public ?int $height,
        public ?float $durationSeconds,
    ) {}
}
