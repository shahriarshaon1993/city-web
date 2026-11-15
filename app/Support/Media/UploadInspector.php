<?php

namespace App\Support\Media;

use App\Support\Format;
use getID3;
use Illuminate\Validation\ValidationException;

/**
 * Reads an uploaded file's real type, size and dimensions from its bytes and refuses what the
 * library or the hero profile does not accept (CMS-MED-003, CMS-MED-006, HERO-010, HERO-022,
 * NFR-SEC-12). Every refusal names the limit that was exceeded.
 */
final class UploadInspector
{
    /**
     * @throws ValidationException on the `file` field
     */
    public function inspect(string $path, string $originalName, UploadPurpose $purpose = UploadPurpose::Library): InspectedUpload
    {
        $bytes = (int) filesize($path);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $this->sniffMime($path, $extension);

        $allowed = MediaLimits::TYPES[$mime] ?? null;

        if ($allowed === null) {
            $this->refuse("“{$originalName}” is not a file type the library accepts. Upload JPEG, PNG, WebP, AVIF, SVG, MP4, WebM or PDF files.");
        }

        if (! in_array($extension, $allowed, true)) {
            $this->refuse("“{$originalName}” has the extension .{$extension} but its content is ".MediaLimits::TYPE_NAMES[$mime].'. Rename it to .'.$allowed[0].' or export it again.');
        }

        $kind = MediaLimits::kindOf($mime);

        $this->checkPurpose($purpose, $kind, $mime);
        $this->checkSize($purpose, $kind, $mime, $bytes);

        return match ($kind) {
            MediaKind::Image => $this->inspectImage($path, $mime, $extension, $bytes, $purpose),
            MediaKind::Video => $this->inspectVideo($path, $mime, $extension, $bytes, $purpose),
            MediaKind::Svg, MediaKind::Document => new InspectedUpload($kind, $mime, $extension, $bytes, null, null, null),
        };
    }

    /**
     * Content type by `finfo`, with the cases libmagic reports loosely (SVG as XML or text, AVIF
     * and WebM as generic containers) settled by looking at the bytes themselves.
     */
    private function sniffMime(string $path, string $extension): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $mime = $mime === false ? 'application/octet-stream' : $mime;

        $head = (string) file_get_contents($path, false, null, 0, 4096);

        if ($extension === 'svg' && in_array($mime, ['text/xml', 'text/plain', 'application/xml', 'image/svg+xml', 'text/html'], true)
            && preg_match('/<svg[\s>]/i', $head) === 1) {
            return 'image/svg+xml';
        }

        if ($extension === 'avif' && in_array($mime, ['application/octet-stream', 'image/heif', 'image/avif', 'image/avif-sequence'], true)
            && in_array(substr($head, 4, 8), ['ftypavif', 'ftypavis'], true)) {
            return 'image/avif';
        }

        if ($extension === 'webm' && $mime === 'video/x-matroska') {
            return 'video/webm';
        }

        return $mime;
    }

    private function checkPurpose(UploadPurpose $purpose, MediaKind $kind, string $mime): void
    {
        if ($purpose === UploadPurpose::HeroImage && $kind !== MediaKind::Image) {
            $this->refuse('A hero image must be a JPEG, PNG, WebP or AVIF photo; this file is '.MediaLimits::TYPE_NAMES[$mime].'.');
        }

        if ($purpose === UploadPurpose::HeroVideo && ! in_array($mime, ['video/mp4', 'video/webm'], true)) {
            $this->refuse('A hero video must be an MP4 or WebM file; this file is '.MediaLimits::TYPE_NAMES[$mime].'.');
        }
    }

    private function checkSize(UploadPurpose $purpose, MediaKind $kind, string $mime, int $bytes): void
    {
        $limit = match (true) {
            $purpose === UploadPurpose::HeroVideo && $mime === 'video/mp4' => MediaLimits::HERO_MP4_BYTES,
            $purpose === UploadPurpose::HeroVideo => MediaLimits::HERO_WEBM_BYTES,
            default => MediaLimits::maxBytes($kind),
        };

        if ($bytes > $limit) {
            $what = $purpose === UploadPurpose::HeroVideo ? 'Hero '.MediaLimits::TYPE_NAMES[$mime].' videos' : $kind->label().' files';
            $hint = $purpose === UploadPurpose::HeroVideo ? ' Compress it with the ffmpeg command shown in the upload dialog.' : '';
            $this->refuse("{$what} can be at most {$this->size($limit)}; this file is {$this->size($bytes)}.{$hint}");
        }
    }

    private function inspectImage(string $path, string $mime, string $extension, int $bytes, UploadPurpose $purpose): InspectedUpload
    {
        $size = @getimagesize($path);

        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            $this->refuse('This image is damaged or cannot be read. Export it again and upload the new file.');
        }

        [$width, $height] = $this->orient($path, $mime, $size[0], $size[1]);

        $pixels = $width * $height;

        if ($pixels > MediaLimits::MAX_PIXELS) {
            $this->refuse('Images can have at most '.(MediaLimits::MAX_PIXELS / 1_000_000)." megapixels; this one is {$this->megapixels($pixels)} ({$width} × {$height} px). Resize it before uploading.");
        }

        if ($mime === 'image/avif' && ! $this->gdReadsAvif()) {
            $this->refuse('This server cannot process AVIF images yet. Upload the photo as JPEG, PNG or WebP.');
        }

        if ($purpose === UploadPurpose::HeroImage
            && ($width < MediaLimits::HERO_IMAGE_MIN_WIDTH || $height < MediaLimits::HERO_IMAGE_MIN_HEIGHT)) {
            $this->refuse('A hero image must be at least '.MediaLimits::HERO_IMAGE_MIN_WIDTH.' × '.MediaLimits::HERO_IMAGE_MIN_HEIGHT." px; this one is {$width} × {$height} px.");
        }

        return new InspectedUpload(MediaKind::Image, $mime, $extension, $bytes, $width, $height, null);
    }

    private function inspectVideo(string $path, string $mime, string $extension, int $bytes, UploadPurpose $purpose): InspectedUpload
    {
        // HERO-022: duration, size and frame rate come from the file header (getID3, pure PHP, no ffmpeg).
        $info = (new getID3)->analyze($path);
        $video = $info['video'] ?? null;

        if (! is_array($video) || ! isset($video['resolution_x'], $video['resolution_y'])) {
            $this->refuse('This video cannot be read. Export it again as H.264 MP4 or VP9 WebM.');
        }

        $width = (int) $video['resolution_x'];
        $height = (int) $video['resolution_y'];
        $seconds = isset($info['playtime_seconds']) ? round((float) $info['playtime_seconds'], 1) : null;
        $fps = isset($video['frame_rate']) ? round((float) $video['frame_rate'], 2) : null;

        if ($purpose === UploadPurpose::HeroVideo) {
            if ($width > MediaLimits::HERO_VIDEO_MAX_WIDTH || $height > MediaLimits::HERO_VIDEO_MAX_HEIGHT) {
                $this->refuse('A hero video can be at most '.MediaLimits::HERO_VIDEO_MAX_WIDTH.' × '.MediaLimits::HERO_VIDEO_MAX_HEIGHT." px; this one is {$width} × {$height} px.");
            }

            if ($seconds !== null && $seconds > MediaLimits::HERO_VIDEO_MAX_SECONDS) {
                $this->refuse('A hero video can be at most '.MediaLimits::HERO_VIDEO_MAX_SECONDS." seconds long; this one is {$seconds} s.");
            }

            if ($fps !== null && $fps > MediaLimits::HERO_VIDEO_MAX_FPS + 0.01) {
                $this->refuse('A hero video can run at most '.MediaLimits::HERO_VIDEO_MAX_FPS." frames per second; this one runs at {$fps}.");
            }
        }

        return new InspectedUpload(MediaKind::Video, $mime, $extension, $bytes, $width, $height, $seconds);
    }

    /**
     * Phone photos store their rotation in EXIF; the library shows (and converts) the rotated image,
     * so its width and height swap for the orientations that turn it on its side.
     *
     * @return array{int, int}
     */
    private function orient(string $path, string $mime, int $width, int $height): array
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return [$width, $height];
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return $orientation >= 5 && $orientation <= 8 ? [$height, $width] : [$width, $height];
    }

    private function gdReadsAvif(): bool
    {
        return function_exists('imagecreatefromavif') && ((gd_info()['AVIF Support'] ?? false) === true);
    }

    private function megapixels(int $pixels): string
    {
        return number_format($pixels / 1_000_000, 1).' megapixels';
    }

    private function size(int $bytes): string
    {
        return str_replace("\u{00A0}", ' ', Format::fileSize($bytes));
    }

    /**
     * @throws ValidationException
     */
    private function refuse(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
