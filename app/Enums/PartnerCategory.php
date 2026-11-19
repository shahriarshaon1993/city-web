<?php

namespace App\Enums;

/** FR-SOC-002 partner categories. */
enum PartnerCategory: string implements Labelled
{
    case Bank = 'bank';
    case Supplier = 'supplier';
    case Consultant = 'consultant';
    case GroupCompany = 'group_company';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank',
            self::Supplier => 'Supplier',
            self::Consultant => 'Consultant',
            self::GroupCompany => 'Group company',
        };
    }
}
