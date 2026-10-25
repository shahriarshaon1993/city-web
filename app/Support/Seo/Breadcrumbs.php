<?php

namespace App\Support\Seo;

/**
 * The `breadcrumbs` page prop (CMP-BREAD-01, SEO-ONP-005): Home first, the
 * current page last without a URL. 19.1 turns the same trail into
 * `BreadcrumbList` JSON-LD.
 *
 * @phpstan-type Crumb array{label: string, url: string|null}
 */
final class Breadcrumbs
{
    /**
     * @param  list<array{0: string, 1: string}>  $parents  [label, root-relative URL] between Home and the page
     * @return list<Crumb>
     */
    public static function to(string $current, array $parents = []): array
    {
        $trail = [['label' => 'Home', 'url' => '/']];

        foreach ($parents as [$label, $url]) {
            $trail[] = ['label' => $label, 'url' => $url];
        }

        $trail[] = ['label' => $current, 'url' => null];

        return $trail;
    }
}
