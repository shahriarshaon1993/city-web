<?php

namespace App\Support\Admin;

/**
 * One sidebar entry (CMP-ADM-SIDEBAR). `permissions` is any-of: the item is
 * shown, and its route reachable, when the user holds at least one. While
 * `placeholder` is true the route renders the shared "coming in phase X"
 * page; the phase that builds the real screen registers its own routes and
 * flips the flag.
 */
final readonly class NavItem
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $path,
        public string $icon,
        public array $permissions,
        public string $description,
        public string $phase,
        public bool $placeholder = true,
    ) {}

    public function routeName(): string
    {
        return "admin.{$this->key}.index";
    }
}
