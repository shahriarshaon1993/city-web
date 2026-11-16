<?php

namespace App\Support\Menus;

/**
 * One thing a menu item can point at (CMS-NAV-004): a project, area, page, article or category.
 * `published` false = not public yet (hidden in the menu, flagged in the admin).
 */
final readonly class LinkTarget
{
    public function __construct(
        public LinkType $type,
        public int $id,
        public string $label,
        public string $url,
        public bool $published,
        /** Small grey text next to the label in the picker (city, status, date). */
        public ?string $hint = null,
    ) {}

    /**
     * @return array{id: int, label: string, url: string, published: bool, hint: string|null}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'label' => $this->label, 'url' => $this->url, 'published' => $this->published, 'hint' => $this->hint];
    }
}
