<?php

namespace App\Queries\Admin;

/**
 * Registry of the admin tables by key, for the generic endpoints (export, saved views). A phase
 * that adds a list registers its AdminTable here.
 */
final class AdminTables
{
    /**
     * @var list<class-string<AdminTable<*>>>
     */
    private const array TABLES = [
        UsersTable::class,
        RolesTable::class,
        ActivityLogTable::class,
    ];

    /**
     * @return AdminTable<*>|null
     */
    public static function find(string $key): ?AdminTable
    {
        foreach (self::TABLES as $class) {
            $table = app($class);

            if ($table->key() === $key) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (string $class): string => app($class)->key(), self::TABLES);
    }
}
