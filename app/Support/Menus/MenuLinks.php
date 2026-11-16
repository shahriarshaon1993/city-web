<?php

namespace App\Support\Menus;

/**
 * Turns a stored link (type + target) into its address at render time (CMS-NAV-004) and says whether
 * it may be shown: `ok`, `unpublished` (the entity exists but is not public yet) or `missing` (the
 * entity is gone, or the link is incomplete). The public site hides anything that is not `ok`; the
 * admin flags it.
 */
final class MenuLinks
{
    public const string OK = 'ok';

    public const string UNPUBLISHED = 'unpublished';

    public const string MISSING = 'missing';

    /**
     * @param  array<string, mixed>  $link  `link_type`, `linkable_id`, `url`, `anchor`
     * @return array{status: string, url: string|null, target: LinkTarget|null}
     */
    public static function resolve(array $link): array
    {
        $type = LinkType::tryFrom((string) ($link['link_type'] ?? ''));

        if ($type === null) {
            return self::missing();
        }

        if ($type->isEntity()) {
            $target = is_numeric($link['linkable_id'] ?? null) ? LinkTargets::find($type, (int) $link['linkable_id']) : null;

            if ($target === null) {
                return self::missing();
            }

            return ['status' => $target->published ? self::OK : self::UNPUBLISHED, 'url' => $target->url, 'target' => $target];
        }

        $url = trim((string) ($link['url'] ?? ''));

        if ($type === LinkType::Url) {
            return $url === '' ? self::missing() : ['status' => self::OK, 'url' => $url, 'target' => null];
        }

        $anchor = trim((string) ($link['anchor'] ?? ''), '# ');

        if ($anchor === '') {
            return self::missing();
        }

        return ['status' => self::OK, 'url' => ($url === '' ? '/' : $url).'#'.$anchor, 'target' => null];
    }

    /**
     * @return array{status: string, url: null, target: null}
     */
    private static function missing(): array
    {
        return ['status' => self::MISSING, 'url' => null, 'target' => null];
    }
}
