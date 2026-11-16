<?php

namespace App\Support\Menus;

/**
 * CMS-NAV-002 link types. The first five point at an entity by ID (resolved when the page is
 * rendered, CMS-NAV-004); `url` is any address; `anchor` is a section of a page.
 */
enum LinkType: string
{
    case Page = 'page';
    case Project = 'project';
    case Location = 'location';
    case Post = 'post';
    case Category = 'category';
    case Url = 'url';
    case Anchor = 'anchor';

    public function isEntity(): bool
    {
        return ! in_array($this, [self::Url, self::Anchor], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::Project => 'Project',
            self::Location => 'Area',
            self::Post => 'News article',
            self::Category => 'News category',
            self::Url => 'Web address',
            self::Anchor => 'Section of a page',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
