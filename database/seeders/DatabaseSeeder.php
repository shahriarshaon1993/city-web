<?php

namespace Database\Seeders;

use App\Actions\Users\SyncSystemRoles;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Roles and permissions are seeded everywhere. One sign-in per role
     * (`{role-slug}@gemcon.test`, password `password`) is created only
     * outside production, for the manual role checks; production users are
     * invited with `php artisan user:invite`.
     */
    public function run(SyncSystemRoles $syncRoles): void
    {
        $syncRoles->handle();

        if (app()->isProduction()) {
            return;
        }

        foreach (SystemRole::cases() as $role) {
            $user = User::query()->firstOrNew(['email' => Str::slug($role->value).'@gemcon.test']);

            if (! $user->exists) {
                $user->forceFill([
                    'name' => 'Test '.$role->value,
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ])->save();
            }

            $user->syncRoles($role->value);
        }
    }
}
