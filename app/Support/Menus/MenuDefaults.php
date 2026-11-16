<?php

namespace App\Support\Menus;

/**
 * The menus a fresh install starts with: the navigation of the approved frontend (spec/pages.md
 * §5.1). Used by `MenuSeeder` only; after that the admin owns the menus. Items use the same shape
 * `SaveMenu` takes. Pages are entity links, system pages (listing, news, contact…) plain addresses.
 */
final class MenuDefaults
{
    /**
     * @return array<string, list<array<string, mixed>>> items per menu key
     */
    public static function items(): array
    {
        return [
            MenuKey::Header->value => [
                self::url('Projects', '/projects', [
                    'is_mega' => true,
                    'mega_columns' => [
                        ['kind' => 'segments', 'heading' => 'By type', 'items' => []],
                        ['kind' => 'links', 'heading' => 'By status', 'items' => [
                            self::url('Ongoing', '/projects/status/ongoing'),
                            self::url('Upcoming', '/projects/status/upcoming'),
                            self::url('Ready', '/projects/status/ready'),
                            self::url('Handed over', '/projects/status/handed-over'),
                            self::url('All projects', '/projects'),
                        ]],
                        ['kind' => 'locations', 'heading' => 'By location', 'items' => []],
                        ['kind' => 'featured_projects', 'heading' => 'Featured', 'items' => []],
                    ],
                ]),
                self::url('Locations', '/locations'),
                self::page('Landowners', '/landowner'),
                self::page('NRB', '/nrb'),
                self::page('About', '/about', children: [
                    self::page('About us', '/about'),
                    self::page('Leadership', '/about/leadership'),
                    self::page('Services', '/services'),
                    self::url('Careers', '/careers'),
                ]),
                self::url('News', '/news'),
            ],
            MenuKey::Footer1->value => [
                self::url('All projects', '/projects'),
                self::url('Residential', '/projects/type/residential'),
                self::url('Commercial', '/projects/type/commercial'),
                self::url('Land', '/projects/type/land'),
            ],
            MenuKey::Footer2->value => [
                self::page('About us', '/about'),
                self::page('Leadership', '/about/leadership'),
                self::page('Services', '/services'),
                self::url('Careers', '/careers'),
                self::url('News', '/news'),
            ],
            MenuKey::Footer3->value => [
                self::page('Landowners', '/landowner'),
                self::page('NRB buyers', '/nrb'),
                self::url('FAQ', '/faq'),
                self::url('Contact', '/contact'),
            ],
            MenuKey::Footer4->value => [
                self::url('Shortlist', '/shortlist'),
                self::url('Compare', '/compare'),
                self::url('Search', '/search'),
            ],
            MenuKey::Legal->value => [
                self::page('Privacy policy', '/privacy-policy'),
                self::page('Terms', '/terms'),
            ],
            MenuKey::MobileExtra->value => [
                self::url('Contact', '/contact'),
                self::url('Book a site visit', '/contact'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function url(string $label, string $url, array $extra = []): array
    {
        return self::base($label, LinkType::Url, ['url' => $url, ...$extra]);
    }

    /**
     * A page by its address; falls back to a plain address when no such page exists.
     *
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private static function page(string $label, string $url, array $children = []): array
    {
        foreach (LinkTargets::search(LinkType::Page, limit: 100) as $target) {
            if ($target->url === $url) {
                return self::base($label, LinkType::Page, ['linkable_id' => $target->id, 'children' => $children]);
            }
        }

        return self::base($label, LinkType::Url, ['url' => $url, 'children' => $children]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function base(string $label, LinkType $type, array $values): array
    {
        return [
            'id' => null,
            'label' => $label,
            'link_type' => $type->value,
            'linkable_id' => null,
            'url' => null,
            'anchor' => null,
            'new_tab' => false,
            'icon' => null,
            'description' => null,
            'is_mega' => false,
            'mega_columns' => [],
            'children' => [],
            ...$values,
        ];
    }
}
