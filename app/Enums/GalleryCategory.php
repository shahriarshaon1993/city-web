<?php

namespace App\Enums;

/** FR-PROJ gallery item category (inside `projects.gallery`), in lightbox order (CMP-GAL-01). */
enum GalleryCategory: string
{
    case Exterior = 'exterior';
    case Interior = 'interior';
    case Amenity = 'amenity';
    case Site = 'site';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Exterior => 'Exterior',
            self::Interior => 'Interior',
            self::Amenity => 'Amenity',
            self::Site => 'Site',
            self::Night => 'Night',
        };
    }
}
