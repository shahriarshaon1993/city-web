<?php

namespace App\Support\Site;

use App\Queries\Site\AreaDirectory;
use App\Queries\Site\SocialContent;
use App\Support\Menus\PublicMenus;
use App\Support\Settings\Settings;

/**
 * The `footer` shared prop (CMP-FOOT-01, `FooterInfo` in types/site.ts): headings of the `footer_1…4`
 * menus, the offices and the top 8 areas (SEO-ONP-005).
 */
final class FooterData
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $footer = Settings::group('footer');
        $id = 1000;

        return [
            'columns' => PublicMenus::footerColumns(),
            'offices_heading' => $footer['offices_heading'],
            'offices' => SocialContent::offices(),
            'locations_heading' => $footer['locations_heading'],
            'locations' => array_map(
                fn (array $area): array => [
                    'id' => ++$id,
                    'label' => $area['name'],
                    'url' => '/locations/'.$area['slug'],
                    'new_tab' => false,
                    'icon' => null,
                    'description' => null,
                    'count' => $area['count'],
                    'children' => [],
                    'mega' => null,
                ],
                AreaDirectory::top(),
            ),
        ];
    }
}
