<?php

namespace App\Support\Menus;

/**
 * CMS-NAV-002: the icons a menu item may carry, by Lucide name. The same list is bundled in
 * resources/js/lib/icons.ts (only these icons ship to visitors); keep the two in step.
 */
final class MenuIcons
{
    public const array NAMES = [
        'house', 'building', 'layers', 'land-plot', 'map-pin', 'phone', 'newspaper',
        'briefcase', 'users', 'file-text', 'info', 'circle-help',
    ];
}
