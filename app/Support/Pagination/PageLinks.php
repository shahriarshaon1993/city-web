<?php

namespace App\Support\Pagination;

use Closure;

/**
 * CMP-PAGI-01 links for a `?page=n` list (SEO-TEC-005): first, last, the
 * current page ±1 and `null` for each gap (an ellipsis). Page 1 has no query.
 */
final class PageLinks
{
    /**
     * @param  Closure(int): string  $url  the URL of page n
     * @return array{prev: string|null, next: string|null, pages: list<array{page: int, url: string}|null>}
     */
    public static function build(int $page, int $lastPage, Closure $url): array
    {
        $numbers = array_unique(array_filter(
            [1, $page - 1, $page, $page + 1, $lastPage],
            fn (int $number): bool => $number >= 1 && $number <= $lastPage,
        ));
        sort($numbers);

        $pages = [];
        $previous = 0;

        foreach ($numbers as $number) {
            if ($number - $previous > 1) {
                $pages[] = null;
            }

            $pages[] = ['page' => $number, 'url' => $url($number)];
            $previous = $number;
        }

        return [
            'prev' => $page > 1 ? $url($page - 1) : null,
            'next' => $page < $lastPage ? $url($page + 1) : null,
            'pages' => $lastPage > 1 ? $pages : [],
        ];
    }

    /**
     * `/news` for page 1, `/news?page=2` after that.
     */
    public static function pageUrl(string $base, int $page): string
    {
        return $page > 1 ? $base.'?page='.$page : $base;
    }
}
