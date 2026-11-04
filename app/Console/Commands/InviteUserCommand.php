<?php

namespace App\Console\Commands;

use App\Actions\Users\InviteUser;
use App\Support\Auth\SystemRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

#[Signature('user:invite {email : Email address of the invitee} {name : Full name} {role=Viewer : Role name, e.g. "Super Admin"}')]
#[Description('Invite an admin user: creates the account and emails the 72-hour set-password link (ADM-AUTH-006)')]
class InviteUserCommand extends Command
{
    public function handle(InviteUser $invite): int
    {
        $role = (string) $this->argument('role');

        if (! Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->error(sprintf('Unknown role "%s". System roles: %s.', $role, implode(', ', SystemRole::names())));

            return self::FAILURE;
        }

        $user = $invite->handle((string) $this->argument('name'), (string) $this->argument('email'), $role);

        $this->info(sprintf('Invitation sent to %s as %s.', $user->email, $role));

        return self::SUCCESS;
    }
}
