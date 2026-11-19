<?php

namespace App\Enums;

/** FR-SOC-003 FAQ categories, in the order the FAQ page shows them. */
enum FaqCategory: string implements Labelled
{
    case Buying = 'buying';
    case Payment = 'payment';
    case Legal = 'legal';
    case Nrb = 'nrb';
    case Landowner = 'landowner';
    case AfterSales = 'after_sales';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Buying => 'Buying',
            self::Payment => 'Payments',
            self::Legal => 'Legal and documents',
            self::Nrb => 'Buying from abroad',
            self::Landowner => 'Landowners',
            self::AfterSales => 'After-sales',
            self::General => 'General',
        };
    }

    public function position(): int
    {
        return (int) array_search($this, self::cases(), true);
    }
}
