<?php

namespace App\Support\Blocks;

use App\Models\Page;
use App\Models\PageBlock;
use App\Support\Media\AssetMap;
use App\Support\Menus\MenuLinks;
use App\Support\Seo\Breadcrumbs;

/**
 * What a schema needs to turn stored block data into public props: the page, every library asset of
 * the page loaded in one go, and the resolver for stored links (entity links by ID, CMS-NAV-004).
 */
final class BlockContext
{
    private function __construct(
        public readonly Page $page,
        private readonly AssetMap $assets,
    ) {}

    /**
     * @param  iterable<PageBlock>  $blocks
     */
    public static function for(Page $page, iterable $blocks): self
    {
        $ids = [];

        foreach ($blocks as $block) {
            $type = $block->blockType();

            if ($type !== null) {
                array_push($ids, ...MediaPaths::ids($type, $block->data));
            }
        }

        return new self($page, AssetMap::load($ids));
    }

    /**
     * The `MediaImage` prop of a library asset id.
     *
     * @return array<string, mixed>|null
     */
    public function image(mixed $id, ?string $caption = null): ?array
    {
        return is_numeric($id) ? $this->assets->image((int) $id, $caption) : null;
    }

    /**
     * @param  mixed  $ids  a list of asset ids
     * @return list<array<string, mixed>> the ones that still exist, in order
     */
    public function images(mixed $ids): array
    {
        $images = [];

        foreach (is_array($ids) ? $ids : [] as $id) {
            $image = $this->image($id);

            if ($image !== null) {
                $images[] = $image;
            }
        }

        return $images;
    }

    public function url(mixed $id): ?string
    {
        return is_numeric($id) ? $this->assets->url((int) $id) : null;
    }

    /**
     * A stored link as the `BlockLink` prop; null when it has no label or its target is gone or not
     * public (the public site hides what is not `ok`).
     *
     * @param  array<string, mixed>|null  $link
     * @return array{label: string, url: string, new_tab: bool}|null
     */
    public function link(?array $link): ?array
    {
        $label = trim((string) ($link['label'] ?? ''));

        if ($link === null || $label === '') {
            return null;
        }

        $resolved = MenuLinks::resolve($link);

        return $resolved['status'] === MenuLinks::OK && $resolved['url'] !== null
            ? ['label' => $label, 'url' => $resolved['url'], 'new_tab' => (bool) ($link['new_tab'] ?? false)]
            : null;
    }

    /**
     * The trail of the page header (CMP-BREAD-01).
     *
     * @return list<array{label: string, url: string|null}>
     */
    public function breadcrumbs(): array
    {
        return Breadcrumbs::to($this->page->title, $this->page->breadcrumbParents());
    }
}
