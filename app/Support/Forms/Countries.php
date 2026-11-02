<?php

namespace App\Support\Forms;

/**
 * Countries offered by the phone selector and the NRB "country of residence"
 * (FR-LEAD-002). Bangladesh first, then the main destinations of the
 * diaspora. ISO 3166-1 alpha-2 code, name and the dial code without `+`.
 */
final class Countries
{
    public const string DEFAULT = 'BD';

    /**
     * @var array<string, array{name: string, dial: string}>
     */
    private const array LIST = [
        'BD' => ['name' => 'Bangladesh', 'dial' => '88'],
        'AE' => ['name' => 'United Arab Emirates', 'dial' => '971'],
        'SA' => ['name' => 'Saudi Arabia', 'dial' => '966'],
        'QA' => ['name' => 'Qatar', 'dial' => '974'],
        'KW' => ['name' => 'Kuwait', 'dial' => '965'],
        'OM' => ['name' => 'Oman', 'dial' => '968'],
        'BH' => ['name' => 'Bahrain', 'dial' => '973'],
        'MY' => ['name' => 'Malaysia', 'dial' => '60'],
        'SG' => ['name' => 'Singapore', 'dial' => '65'],
        'GB' => ['name' => 'United Kingdom', 'dial' => '44'],
        'US' => ['name' => 'United States', 'dial' => '1'],
        'CA' => ['name' => 'Canada', 'dial' => '1'],
        'AU' => ['name' => 'Australia', 'dial' => '61'],
        'NZ' => ['name' => 'New Zealand', 'dial' => '64'],
        'IT' => ['name' => 'Italy', 'dial' => '39'],
        'DE' => ['name' => 'Germany', 'dial' => '49'],
        'FR' => ['name' => 'France', 'dial' => '33'],
        'ES' => ['name' => 'Spain', 'dial' => '34'],
        'SE' => ['name' => 'Sweden', 'dial' => '46'],
        'NL' => ['name' => 'Netherlands', 'dial' => '31'],
        'JP' => ['name' => 'Japan', 'dial' => '81'],
        'KR' => ['name' => 'South Korea', 'dial' => '82'],
        'HK' => ['name' => 'Hong Kong', 'dial' => '852'],
        'IN' => ['name' => 'India', 'dial' => '91'],
        'MV' => ['name' => 'Maldives', 'dial' => '960'],
        'LK' => ['name' => 'Sri Lanka', 'dial' => '94'],
        'ZA' => ['name' => 'South Africa', 'dial' => '27'],
    ];

    /**
     * @return list<array{code: string, name: string, dial: string}>
     */
    public static function all(): array
    {
        $countries = [];

        foreach (self::LIST as $code => $country) {
            $countries[] = ['code' => $code, ...$country];
        }

        return $countries;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::LIST);
    }

    public static function dial(string $code): ?string
    {
        return self::LIST[$code]['dial'] ?? null;
    }
}
