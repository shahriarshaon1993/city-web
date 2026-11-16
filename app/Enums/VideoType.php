<?php

namespace App\Enums;

/** DM-PROJ `video_type`. */
enum VideoType: string
{
    case None = 'none';
    case Youtube = 'youtube';
    case Upload = 'upload';
}
