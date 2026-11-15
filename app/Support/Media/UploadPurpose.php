<?php

namespace App\Support\Media;

/**
 * Why a file is uploaded. `Library` follows CMS-MED-003; the hero purposes add the stricter
 * limits of HERO-010 (image) and HERO-022 (video), so a file that cannot be a hero is refused
 * with the exceeded limit named.
 */
enum UploadPurpose: string
{
    case Library = 'library';
    case HeroImage = 'hero_image';
    case HeroVideo = 'hero_video';
}
