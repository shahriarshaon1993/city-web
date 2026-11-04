<?php

namespace App\Support\Auth;

/**
 * The six seeded roles (DEC-19, spec/admin.md §10.2). Custom roles are
 * created by a Super Admin from the same permissions (10.3).
 */
enum SystemRole: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case ContentEditor = 'Content Editor';
    case SalesManager = 'Sales Manager';
    case SalesExecutive = 'Sales Executive';
    case Viewer = 'Viewer';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
