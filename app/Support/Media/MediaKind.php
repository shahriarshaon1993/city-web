<?php

namespace App\Support\Media;

/**
 * What a library item is (`media_assets.kind`); drives the type filter of CMS-MED-009 and which
 * conversions and limits apply.
 */
enum MediaKind: string
{
    case Image = 'image';
    case Svg = 'svg';
    case Video = 'video';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Svg => 'SVG',
            self::Video => 'Video',
            self::Document => 'PDF',
        };
    }
}
