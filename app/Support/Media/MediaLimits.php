<?php

namespace App\Support\Media;

/**
 * Upload limits of CMS-MED-003 / CMS-MED-006 and the hero limits of HERO-010 / HERO-022.
 * Sizes are bytes. The 11.2 dropzone reads `toArray()` to reject files before uploading (VAL-03).
 */
final class MediaLimits
{
    public const int MB = 1024 * 1024;

    /** CMS-MED-003: per kind. */
    public const int IMAGE_BYTES = 12 * self::MB;

    public const int VIDEO_BYTES = 50 * self::MB;

    public const int PDF_BYTES = 20 * self::MB;

    public const int SVG_BYTES = self::MB;

    /** CMS-MED-006: sources above this are refused (the GD decode needs ~4 bytes per pixel). */
    public const int MAX_PIXELS = 40_000_000;

    /** HERO-010. */
    public const int HERO_IMAGE_MIN_WIDTH = 1920;

    public const int HERO_IMAGE_MIN_HEIGHT = 1080;

    /** HERO-022. */
    public const int HERO_MP4_BYTES = 6 * self::MB;

    public const int HERO_WEBM_BYTES = 5 * self::MB;

    public const int HERO_VIDEO_MAX_WIDTH = 1920;

    public const int HERO_VIDEO_MAX_HEIGHT = 1080;

    public const int HERO_VIDEO_MAX_SECONDS = 20;

    public const int HERO_VIDEO_MAX_FPS = 30;

    /**
     * Allowed content types (sniffed from the bytes, never from the extension) and the extensions
     * each may carry (NFR-SEC-12).
     *
     * @var array<string, list<string>>
     */
    public const array TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
        'image/svg+xml' => ['svg'],
        'video/mp4' => ['mp4'],
        'video/webm' => ['webm'],
        'application/pdf' => ['pdf'],
    ];

    /** Plain-words names for the messages. */
    public const array TYPE_NAMES = [
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        'image/webp' => 'WebP',
        'image/avif' => 'AVIF',
        'image/svg+xml' => 'SVG',
        'video/mp4' => 'MP4',
        'video/webm' => 'WebM',
        'application/pdf' => 'PDF',
    ];

    public static function kindOf(string $mime): MediaKind
    {
        return match (true) {
            $mime === 'image/svg+xml' => MediaKind::Svg,
            str_starts_with($mime, 'image/') => MediaKind::Image,
            str_starts_with($mime, 'video/') => MediaKind::Video,
            default => MediaKind::Document,
        };
    }

    public static function maxBytes(MediaKind $kind): int
    {
        return match ($kind) {
            MediaKind::Image => self::IMAGE_BYTES,
            MediaKind::Svg => self::SVG_BYTES,
            MediaKind::Video => self::VIDEO_BYTES,
            MediaKind::Document => self::PDF_BYTES,
        };
    }

    /**
     * For the client-side checks of 11.2.
     *
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            'max_bytes' => [
                'image' => self::IMAGE_BYTES,
                'svg' => self::SVG_BYTES,
                'video' => self::VIDEO_BYTES,
                'document' => self::PDF_BYTES,
            ],
            'max_pixels' => self::MAX_PIXELS,
            'mime_types' => array_keys(self::TYPES),
            'hero' => [
                'image_min' => ['width' => self::HERO_IMAGE_MIN_WIDTH, 'height' => self::HERO_IMAGE_MIN_HEIGHT],
                'mp4_bytes' => self::HERO_MP4_BYTES,
                'webm_bytes' => self::HERO_WEBM_BYTES,
                'video_max' => [
                    'width' => self::HERO_VIDEO_MAX_WIDTH,
                    'height' => self::HERO_VIDEO_MAX_HEIGHT,
                    'seconds' => self::HERO_VIDEO_MAX_SECONDS,
                    'fps' => self::HERO_VIDEO_MAX_FPS,
                ],
            ],
        ];
    }
}
