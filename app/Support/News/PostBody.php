<?php

namespace App\Support\News;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\MediaAsset;
use Dom\Element;
use Dom\HTMLDocument;
use Stevebauman\Purify\Facades\Purify;

/**
 * The body of a post (FR-NEWS-001/005/006, NFR-SEC-03): the HTML the Tiptap editor stores. It is purified
 * on save (`clean`), checked for the heading order and image alt text (`problems`), and expanded for
 * the public page (`render`): library images become responsive `<picture>`s from the asset's current
 * conversions, and a YouTube embed becomes a link that loads the player only on click.
 */
final class PostBody
{
    /** FR-NEWS-004. */
    public const int WORDS_PER_MINUTE = 200;

    /** The `sizes` of an image inside the 68ch article column (design/foundations.md §5.3 `article-body`). */
    private const string IMAGE_SIZES = '(min-width:1024px) 700px, 92vw';

    /** NFR-SEC-03: the allowlist lives in config/purify.php (`article`). */
    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $purified = Purify::config('article')->clean($html);
        // An embed from another host loses its address in the purifier; what is left is an empty frame.
        $clean = is_string($purified) ? trim((string) preg_replace('#<iframe>\s*</iframe>#i', '', $purified)) : '';

        // An editor that was cleared leaves "<p></p>": nothing visible, nothing stored.
        return self::hasContent($clean) ? $clean : '';
    }

    public static function hasContent(string $html): bool
    {
        return trim(strip_tags($html, '<img><iframe><hr>')) !== '';
    }

    public static function plainText(?string $html): string
    {
        $text = str_replace(['</p>', '</li>', '</h2>', '</h3>', '</h4>', '</tr>', '</blockquote>', '<br>', '<br/>', '</figcaption>'], ' ', (string) $html);

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5)));
    }

    public static function wordCount(?string $html): int
    {
        $text = self::plainText($html);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    /** FR-NEWS-004: ceil(words / 200), at least one minute. */
    public static function readMinutes(?string $html): int
    {
        return min(255, max(1, (int) ceil(self::wordCount($html) / self::WORDS_PER_MINUTE)));
    }

    /**
     * FR-NEWS-005 and AC-NEWS-02, as the sentences the editor sees.
     *
     * @return list<string>
     */
    public static function problems(?string $html): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return [];
        }

        $problems = [];
        $document = self::parse($html);
        $previous = 1;
        $image = 0;

        foreach ($document->body?->querySelectorAll('h1,h2,h3,h4,h5,h6,img') ?? [] as $node) {
            $tag = strtolower($node->localName);

            if ($tag === 'img') {
                $image++;

                if (trim((string) $node->getAttribute('alt')) === '' && $node->getAttribute('data-decorative') !== 'true') {
                    $problems[] = "Image {$image} needs alt text or must be marked decorative.";
                }

                continue;
            }

            $level = (int) substr($tag, 1);

            if ($level === 1 || $level > 4) {
                $problems[] = 'Headings in the body start at Heading 2 and stop at Heading 4; the title is the only Heading 1.';
            } elseif ($level > $previous + 1) {
                $problems[] = 'Heading '.$level.' “'.self::snippet($node->textContent).'” needs a Heading '.($level - 1).' before it.';
            }

            $previous = max($level, 1);
        }

        return array_values(array_unique($problems));
    }

    /**
     * The library assets the body shows (`img[data-asset-id]`), in order, each once.
     *
     * @return list<int>
     */
    public static function assetIds(?string $html): array
    {
        if (trim((string) $html) === '') {
            return [];
        }

        $ids = [];

        foreach (self::parse((string) $html)->body?->querySelectorAll('img[data-asset-id]') ?? [] as $image) {
            $ids[] = (int) $image->getAttribute('data-asset-id');
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * "Replace everywhere" (DEC-78): the same body with one asset swapped for another.
     */
    public static function replaceAsset(string $html, int $from, int $to): string
    {
        return str_replace('data-asset-id="'.$from.'"', 'data-asset-id="'.$to.'"', $html);
    }

    /**
     * The public HTML of a body: responsive images from the library, click-to-load YouTube.
     */
    public static function render(?string $html, string $watchLabel = 'Watch on YouTube'): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = self::parse($html);
        $body = $document->body;

        if ($body === null) {
            return '';
        }

        $ids = self::assetIds($html);
        $assets = $ids === [] ? collect() : MediaAsset::query()->whereKey($ids)->with('media')->get()->keyBy('id');

        foreach (iterator_to_array($body->querySelectorAll('img[data-asset-id]')) as $image) {
            $asset = $assets->get((int) $image->getAttribute('data-asset-id'));
            $target = $image->parentNode instanceof Element && $image->parentNode->localName === 'figure' ? $image->parentNode : $image;

            if ($asset === null) {
                $target->remove();

                continue;
            }

            $decorative = $image->getAttribute('data-decorative') === 'true';
            $alt = $decorative ? '' : (trim((string) $image->getAttribute('alt')) ?: (string) $asset->alt);
            $wrapper = $document->createElement('div');
            $wrapper->innerHTML = self::picture((new MediaImageResource($asset))->resolve(), $alt);

            $picture = $wrapper->firstElementChild;

            if ($picture !== null) {
                $image->replaceWith($picture);
            }
        }

        foreach (iterator_to_array($body->querySelectorAll('div[data-youtube-video]')) as $embed) {
            $id = self::youtubeId((string) $embed->querySelector('iframe')?->getAttribute('src'));

            if ($id === null) {
                $embed->remove();

                continue;
            }

            $holder = $document->createElement('figure');
            $holder->setAttribute('class', 'video-embed');
            $holder->innerHTML = '<a href="https://www.youtube.com/watch?v='.e($id).'" data-youtube-id="'.e($id).'" target="_blank" rel="noopener">'.e($watchLabel).'</a>';
            $embed->replaceWith($holder);
        }

        foreach ($body->querySelectorAll('a[target="_blank"]') as $link) {
            $link->setAttribute('rel', 'noopener');
        }

        return trim($body->innerHTML);
    }

    public static function youtubeId(string $src): ?string
    {
        return preg_match('#^https://www\.youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,20})#', $src, $match) === 1 ? $match[1] : null;
    }

    /**
     * @param  array<string, mixed>  $image  the `MediaImage` shape
     */
    private static function picture(array $image, string $alt): string
    {
        $sources = '';

        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $type) {
            $set = $image['sources'][$format] ?? null;

            if (is_string($set) && $set !== '') {
                $sources .= '<source type="'.$type.'" srcset="'.e($set).'" sizes="'.self::IMAGE_SIZES.'">';
            }
        }

        $img = '<img src="'.e((string) $image['fallback']).'" alt="'.e($alt).'" width="'.(int) $image['width'].'" height="'.(int) $image['height'].'" loading="lazy" decoding="async">';

        return '<picture>'.$sources.$img.'</picture>';
    }

    private static function snippet(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > 40 ? mb_substr($text, 0, 40).'…' : $text;
    }

    private static function parse(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR);
    }
}
