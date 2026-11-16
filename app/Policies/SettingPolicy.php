<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Settings\SettingsGroup;

/**
 * Settings tabs (spec/admin.md §10.2): Admin and up open the company, site and form tabs, Super
 * Admin alone opens Analytics, Scripts, Webhook, Notifications and Maintenance. Viewing and saving
 * a tab need the same permission.
 */
class SettingPolicy
{
    public function viewGroup(User $user, SettingsGroup $group): bool
    {
        return $user->can($group->permission);
    }

    public function updateGroup(User $user, SettingsGroup $group): bool
    {
        return $user->can($group->permission);
    }
}
