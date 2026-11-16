<?php

namespace App\Enums;

/** DM-PDOC `kind`. */
enum DocumentKind: string
{
    case Brochure = 'brochure';
    case PriceList = 'price_list';
    case Specification = 'specification';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Brochure => 'Brochure',
            self::PriceList => 'Price list',
            self::Specification => 'Specification',
            self::Other => 'Document',
        };
    }
}
