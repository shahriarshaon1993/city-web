<?php

namespace App\Support\Settings;

use App\Models\User;
use App\Support\Settings\Definitions\FormGroups;
use App\Support\Settings\Definitions\SiteGroups;
use App\Support\Settings\Definitions\SystemGroups;

/**
 * Every settings group, key, default and limit in one place (spec/cms.md §8.2, DM-SET). The admin
 * screen, validation, storage and the site's reads all follow from it, so adding a setting is
 * one `Field` here.
 */
final class SettingsRegistry
{
    /** @var array<string, SettingsGroup>|null */
    private static ?array $groups = null;

    /**
     * @return array<string, SettingsGroup> keyed by group, in tab order
     */
    public static function groups(): array
    {
        if (self::$groups === null) {
            self::$groups = [];

            foreach ([...SiteGroups::all(), ...FormGroups::all(), ...SystemGroups::all()] as $group) {
                self::$groups[$group->key] = $group;
            }
        }

        return self::$groups;
    }

    public static function find(string $key): ?SettingsGroup
    {
        return self::groups()[$key] ?? null;
    }

    /**
     * The groups this user may open, in tab order.
     *
     * @return list<SettingsGroup>
     */
    public static function forUser(User $user): array
    {
        return array_values(array_filter(
            self::groups(),
            fn (SettingsGroup $group): bool => $user->can($group->permission),
        ));
    }

    public static function forget(): void
    {
        self::$groups = null;
    }
}
