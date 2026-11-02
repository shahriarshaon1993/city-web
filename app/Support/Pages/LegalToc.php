<?php

namespace App\Support\Pages;

use Illuminate\Support\Str;

/**
 * Table of contents of a legal page (LAY-LEGAL): every H2 of the sanitised
 * body gets an `id` and becomes one entry. Part B runs the same pass when a
 * legal page is rendered from its rich text block.
 *
 * @phpstan-type TocItem array{id: string, label: string}
 */
final class LegalToc
{
    /**
     * @return array{body: string, toc: list<TocItem>}
     */
    public static function build(string $html): array
    {
        $toc = [];
        $used = [];

        $body = preg_replace_callback(
            '~<h2(?<attributes>[^>]*)>(?<inner>.*?)</h2>~is',
            function (array $match) use (&$toc, &$used): string {
                $label = trim(html_entity_decode(strip_tags($match['inner']), ENT_QUOTES | ENT_HTML5));
                $base = Str::slug($label) ?: 'section';
                $id = $base;

                for ($suffix = 2; in_array($id, $used, true); $suffix++) {
                    $id = $base.'-'.$suffix;
                }

                $used[] = $id;
                $toc[] = ['id' => $id, 'label' => $label];

                return '<h2 id="'.$id.'"'.$match['attributes'].'>'.$match['inner'].'</h2>';
            },
            $html,
        );

        return ['body' => $body ?? $html, 'toc' => $toc];
    }
}
