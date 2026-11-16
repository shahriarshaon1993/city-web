<?php

namespace App\Support\Menus;

/**
 * CMS-NAV-001: the seven fixed menus. Each says how deep it may go and how many items it holds
 * (CMS-NAV-002: the header has 2 levels, footers 1; DEC-40: at most 6 header items).
 */
enum MenuKey: string
{
    case Header = 'header';
    case Footer1 = 'footer_1';
    case Footer2 = 'footer_2';
    case Footer3 = 'footer_3';
    case Footer4 = 'footer_4';
    case Legal = 'legal';
    case MobileExtra = 'mobile_extra';

    public function defaultName(): string
    {
        return match ($this) {
            self::Header => 'Header',
            self::Footer1 => 'Projects',
            self::Footer2 => 'Company',
            self::Footer3 => 'Buyers & landowners',
            self::Footer4 => 'Tools',
            self::Legal => 'Legal',
            self::MobileExtra => 'Mobile extras',
        };
    }

    /** Levels of items: 2 = items with children. */
    public function maxDepth(): int
    {
        return $this === self::Header ? 2 : 1;
    }

    /** Top-level items. */
    public function maxItems(): int
    {
        return match ($this) {
            self::Header => 6,
            self::Legal, self::MobileExtra => 6,
            default => 12,
        };
    }

    /** Children per parent (header only). */
    public function maxChildren(): int
    {
        return 10;
    }

    public function supportsMega(): bool
    {
        return $this === self::Header;
    }

    public function isFooterColumn(): bool
    {
        return in_array($this, [self::Footer1, self::Footer2, self::Footer3, self::Footer4], true);
    }

    /** Where the menu appears, for the admin's helper line (HELP-03). */
    public function appearsIn(): string
    {
        return match ($this) {
            self::Header => 'The main navigation at the top of every page and in the phone menu.',
            self::Footer1, self::Footer2, self::Footer3, self::Footer4 => 'A link column in the footer. The menu name is the column heading.',
            self::Legal => 'The small links beside the copyright line in the footer.',
            self::MobileExtra => 'Extra links at the bottom of the phone menu.',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $key): string => $key->value, self::cases());
    }
}
