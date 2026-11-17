<?php

namespace App\Support\Html;

/**
 * Interim rich-text handling until the Tiptap editor and the purifier arrive in 16.3 (DEC-83):
 * plain text becomes paragraphs; HTML keeps only a short allowlist of tags (p, br, h2–h4, lists,
 * strong, em, blockquote, a[href]) with every other attribute removed (NFR-SEC-03).
 */
final class BasicHtml
{
    private const string ALLOWED_TAGS = '<p><br><h2><h3><h4><ul><ol><li><strong><b><em><i><a><blockquote>';

    public static function clean(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if ($input === strip_tags($input)) {
            $paragraphs = preg_split('/\R{2,}/', $input) ?: [$input];

            return implode("\n", array_map(
                fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph)), false).'</p>',
                array_filter($paragraphs, fn (string $paragraph): bool => trim($paragraph) !== ''),
            ));
        }

        $html = strip_tags($input, self::ALLOWED_TAGS);
        $html = (string) preg_replace_callback('/<(\/?)([a-z0-9]+)([^>]*)>/i', function (array $match): string {
            [, $closing, $tag, $attributes] = $match;
            $tag = strtolower($tag);

            if ($closing !== '') {
                return "</{$tag}>";
            }

            if ($tag === 'a' && preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $attributes, $href) === 1 && self::safeUrl($href[2])) {
                return '<a href="'.e(html_entity_decode($href[2])).'" rel="noopener">';
            }

            return $tag === 'a' ? '<a>' : "<{$tag}>";
        }, $html);

        // An editor that was cleared leaves "<p></p>": no visible text means no value.
        return trim(strip_tags($html)) === '' ? null : trim($html);
    }

    /** Words of the visible text, for "overview ≥ 120 words" and "intro ≥ 150 words". */
    public static function wordCount(?string $html): int
    {
        $text = trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '</li>'], ' ', (string) $html))));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    private static function safeUrl(string $url): bool
    {
        $url = trim(html_entity_decode($url));

        return preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $url) === 1;
    }
}
