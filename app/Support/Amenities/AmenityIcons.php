<?php

namespace App\Support\Amenities;

/**
 * DM-AMEN `icon`: the Lucide names an amenity may use. The same list drives the icon picker of the
 * amenities admin and the public renderer (`resources/js/lib/amenity-icons.ts`); keep them equal.
 */
final class AmenityIcons
{
    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'arrow-up-down', 'baby', 'bike', 'book-open', 'camera', 'car', 'cctv', 'concierge-bell', 'droplets',
            'dumbbell', 'fence', 'fire-extinguisher', 'flame', 'heart-pulse', 'key-round', 'leaf', 'moon', 'phone',
            'shield-check', 'square-parking', 'store', 'sun', 'trees', 'users', 'utensils', 'waves-ladder', 'wifi', 'zap',
        ];
    }
}
