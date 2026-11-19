<?php

namespace App\Enums;

/** FR-SOC-003 `show_on`: where a question may appear. */
enum FaqPlacement: string
{
    case FaqPage = 'faq';
    case ProjectDetail = 'project_detail';
    case Location = 'location';
    case Landowner = 'landowner';
    case Nrb = 'nrb';

    public function label(): string
    {
        return match ($this) {
            self::FaqPage => 'FAQ page',
            self::ProjectDetail => 'Project pages',
            self::Location => 'Location pages',
            self::Landowner => 'Landowner page',
            self::Nrb => 'NRB page',
        };
    }
}
