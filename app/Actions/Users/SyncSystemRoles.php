<?php

namespace App\Actions\Users;

use App\Support\Auth\PermissionMatrix;
use App\Support\Auth\SystemRole;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the seeded permissions and the six roles of spec/admin.md §10.2 and
 * re-syncs each system role to the matrix. Idempotent: custom roles and
 * permissions are left alone.
 */
final class SyncSystemRoles
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            foreach (PermissionMatrix::all() as $name) {
                Permission::findOrCreate($name, 'web');
            }

            foreach (SystemRole::cases() as $systemRole) {
                Role::findOrCreate($systemRole->value, 'web')
                    ->syncPermissions(PermissionMatrix::forRole($systemRole));
            }
        });

        $this->registrar->forgetCachedPermissions();
    }
}
