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

        // The placeholder offices, quotes, logos, team and FAQs come first: the system pages' blocks use them
        // (a page whose blocks are incomplete stays a draft). Production starts without them (16.4).
        if (! app()->isProduction()) {
            $this->call([SocialSeeder::class, FaqSeeder::class]);
        }

        // Pages first: the default menus link to them by id.
        $this->call([PageSeeder::class, MenuSeeder::class, CatalogSeeder::class, NewsCategorySeeder::class]);

        if (app()->isProduction()) {
            return;
        }

        // 13.1: the demo cities, areas and projects as real rows.
        $this->call([DemoLocationSeeder::class, DemoProjectSeeder::class, DemoNewsSeeder::class]);
        // The questions that belong to single areas, project segments and listing pages.
        app(FaqSeeder::class)->attach();
        // Again: pages whose blocks need projects (featured, progress) can be published now.
        $this->call(PageSeeder::class);

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
