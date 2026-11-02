<?php

namespace App\Support\Forms;

use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoProjects;
use Carbon\CarbonImmutable;

/**
 * Choice lists, limits and consent copy of the lead forms (spec/leads.md
 * "Lead types and forms"). One source for the Form Requests (`in:` rules) and
 * the `forms` shared prop, so the client never drifts from the server. The
 * wording of options the spec leaves open (budget bands, facings) is a demo
 * assumption the client confirms; copy moves to Settings → Forms in 12.1.
 *
 * @phpstan-type Option array{value: string, label: string}
 */
final class FormOptions
{
    public const string TIMEZONE = 'Asia/Dhaka';

    /** FR-LEAD / careers: private uploads (FR-LEAD-011). */
    public const int MAX_FILES = 3;

    public const int MAX_FILE_KB = 5120;

    public const int MESSAGE_MAX = 2000;

    public const int CONTACT_MESSAGE_MIN = 20;

    public const int VISIT_WINDOW_DAYS = 60;

    /** @var list<string> */
    public const array FILE_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * @var array<string, array<string, string>>
     */
    private const array CHOICES = [
        'timeline' => [
            'immediately' => 'Immediately',
            '1_3_months' => '1–3 months',
            '3_6_months' => '3–6 months',
            '6_plus_months' => '6+ months',
        ],
        'purpose' => [
            'own_residence' => 'Own residence',
            'investment' => 'Investment',
            'business_use' => 'Business use',
        ],
        'contact_time' => [
            'morning' => 'Morning, 9:00–11:30',
            'afternoon' => 'Afternoon, 12:00–4:00',
            'evening' => 'Evening, 4:00–7:00',
        ],
        'subject' => [
            'general' => 'General question',
            'sales' => 'Sales',
            'after_sales' => 'After-sales',
            'media' => 'Media',
            'other' => 'Other',
        ],
        'ownership' => [
            'single' => 'Single owner',
            'joint' => 'Joint owners',
            'other' => 'Other',
        ],
        'facing' => [
            'north' => 'North',
            'south' => 'South',
            'east' => 'East',
            'west' => 'West',
            'north_east' => 'North-east',
            'north_west' => 'North-west',
            'south_east' => 'South-east',
            'south_west' => 'South-west',
        ],
        'budget' => [
            'under_50_lakh' => 'Under ৳ 50 lakh',
            '50_lakh_1_crore' => '৳ 50 lakh – 1 crore',
            '1_2_crore' => '৳ 1 – 2 crore',
            '2_5_crore' => '৳ 2 – 5 crore',
            'over_5_crore' => 'Over ৳ 5 crore',
        ],
    ];

    /**
     * Allowed values of one choice list, for `Rule::in()`.
     *
     * @return list<string>
     */
    public static function values(string $list): array
    {
        return array_map(strval(...), array_keys(self::CHOICES[$list]));
    }

    /**
     * Site-visit window (FR-LEAD): tomorrow … +60 days in Dhaka time, Fridays allowed.
     *
     * @return array{min: CarbonImmutable, max: CarbonImmutable}
     */
    public static function visitWindow(): array
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        return ['min' => $today->addDay(), 'max' => $today->addDays(self::VISIT_WINDOW_DAYS)];
    }

    /**
     * The `forms` shared prop (`FormsShared` in types/forms.ts).
     *
     * @return array<string, mixed>
     */
    public static function shared(): array
    {
        $settings = DemoContent::settings('forms');
        $window = self::visitWindow();

        $choices = [];

        foreach (self::CHOICES as $list => $items) {
            $choices[$list] = array_map(
                fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
                array_map(strval(...), array_keys($items)),
                array_values($items),
            );
        }

        return [
            'choices' => [
                ...$choices,
                'areas' => array_map(
                    fn (array $area): array => ['value' => $area['name'], 'label' => $area['city']],
                    DemoProjects::areas(),
                ),
            ],
            'countries' => Countries::all(),
            'default_country' => Countries::DEFAULT,
            'limits' => [
                'message_max' => self::MESSAGE_MAX,
                'contact_message_min' => self::CONTACT_MESSAGE_MIN,
                'files' => [
                    'max' => self::MAX_FILES,
                    'max_mb' => self::MAX_FILE_KB / 1024,
                    'mimes' => self::FILE_MIMES,
                    'label' => 'PDF, JPG or PNG',
                ],
                'visitors' => ['min' => 1, 'max' => 10],
            ],
            'visit_window' => [
                'min' => $window['min']->toDateString(),
                'max' => $window['max']->toDateString(),
                'timezone' => self::TIMEZONE,
            ],
            'callback_dialog' => $settings['callback_dialog'],
            'consent' => $settings['consent'],
            'privacy_url' => $settings['privacy_url'],
        ];
    }
}
