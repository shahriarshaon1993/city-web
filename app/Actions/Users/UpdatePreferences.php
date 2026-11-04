<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * Per-user display preferences (ADM-SCR-20, ADM-TBL-013): theme and table
 * density. Only the keys that are passed change.
 */
final class UpdatePreferences
{
    public const array THEMES = ['light', 'dark', 'system'];

    public const array DENSITIES = ['comfortable', 'compact'];

    /**
     * @param  array{theme?: string, table_density?: string}  $preferences
     */
    public function handle(User $user, array $preferences): void
    {
        // Not mass-assignable on purpose; this Action is the only writer of these two columns.
        $user->forceFill(array_intersect_key($preferences, array_flip(['theme', 'table_density'])))->save();
    }
}
