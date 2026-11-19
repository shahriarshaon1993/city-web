<?php

namespace App\Enums;

/**
 * FR-CAR-001 `employment_type`. The careers page filters by the hyphenated form (`full-time`, DEC-67).
 */
enum EmploymentType: string implements Labelled
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Internship = 'internship';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::Contract => 'Contract',
            self::Internship => 'Internship',
        };
    }

    /** `full_time` → `full-time`: the value used in `/careers?type=…`. */
    public function urlValue(): string
    {
        return str_replace('_', '-', $this->value);
    }
}
