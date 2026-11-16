<?php

namespace App\Support\Settings;

use Closure;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Settings → Scripts (spec/cms.md §8.2, Super Admin only): the custom head / end-of-body code is
 * limited to `<script src="https://…">` tags from an allowlist of hosts and `<noscript>` blocks
 * holding a tracking pixel. Anything else is refused when saving, and rebuilt from the parsed
 * markup when rendering, so the stored value can never inject more than this.
 */
final class ScriptSnippet
{
    /** Hosts (and their subdomains) a script or pixel may come from. */
    public const array HOSTS = [
        'googletagmanager.com',
        'google-analytics.com',
        'googleadservices.com',
        'doubleclick.net',
        'connect.facebook.net',
        'facebook.com',
        'clarity.ms',
        'hotjar.com',
        'licdn.com',
        'linkedin.com',
        'tiktok.com',
        'ads-twitter.com',
    ];

    /** Shown in the field's help line. */
    public const array EXAMPLE_HOSTS = ['googletagmanager.com', 'connect.facebook.net', 'clarity.ms'];

    private const array SCRIPT_ATTRIBUTES = ['src', 'async', 'defer', 'crossorigin', 'integrity', 'referrerpolicy'];

    private const array PIXEL_ATTRIBUTES = ['src', 'width', 'height', 'alt', 'style', 'loading'];

    /** @var array<string, string> */
    private static array $rendered = [];

    /**
     * A Form Request rule for the two code fields.
     */
    public static function rule(string $attribute, mixed $value, Closure $fail): void
    {
        $problems = self::inspect((string) $value)['problems'];

        if ($problems !== []) {
            $fail($problems[0]);
        }
    }

    /**
     * The markup that is safe to print into the page.
     */
    public static function render(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        return self::$rendered[md5($html)] ??= self::inspect($html)['html'];
    }

    /**
     * @return array{html: string, problems: list<string>}
     */
    public static function inspect(string $html): array
    {
        $html = trim($html);

        if ($html === '') {
            return ['html' => '', 'problems' => []];
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);
        $problems = [];
        $kept = [];

        foreach ($body instanceof DOMNode ? iterator_to_array($body->childNodes) : [] as $node) {
            if ($node instanceof DOMComment) {
                continue;
            }

            if ($node instanceof DOMText) {
                if (trim($node->textContent) !== '') {
                    $problems[] = 'Plain text is not allowed here; only <script src> and <noscript> tags are accepted.';
                }

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $before = count($problems);
            $problems = [...$problems, ...self::inspectElement($node)];

            if (count($problems) === $before) {
                $kept[] = (string) $dom->saveHTML($node);
            }
        }

        return ['html' => implode("\n", $kept), 'problems' => $problems];
    }

    /**
     * @return list<string>
     */
    private static function inspectElement(DOMElement $element): array
    {
        $tag = strtolower($element->tagName);

        if ($tag === 'script') {
            return self::inspectScript($element);
        }

        if ($tag === 'noscript') {
            $problems = [];

            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMText && trim($child->textContent) === '') {
                    continue;
                }

                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['img', 'iframe'], true)) {
                    $problems = [...$problems, ...self::inspectPixel($child)];

                    continue;
                }

                $problems[] = 'A <noscript> block may only hold an <img> or <iframe> from an approved host.';
            }

            return $problems;
        }

        return ["<{$tag}> is not allowed here; only <script src> and <noscript> tags are accepted."];
    }

    /**
     * @return list<string>
     */
    private static function inspectScript(DOMElement $script): array
    {
        if (trim($script->textContent) !== '' || ! $script->hasAttribute('src')) {
            return ['Inline scripts are not allowed. Use a <script src="https://…"> tag, or the Analytics tab for Google Tag Manager.'];
        }

        $unknown = self::unknownAttribute($script, self::SCRIPT_ATTRIBUTES);

        if ($unknown !== null) {
            return ["The \"{$unknown}\" attribute is not allowed on a script. You can use src, async, defer, crossorigin, integrity and referrerpolicy."];
        }

        return self::inspectSource($script->getAttribute('src'));
    }

    /**
     * @return list<string>
     */
    private static function inspectPixel(DOMElement $pixel): array
    {
        $unknown = self::unknownAttribute($pixel, self::PIXEL_ATTRIBUTES);

        if ($unknown !== null) {
            return ["The \"{$unknown}\" attribute is not allowed inside <noscript>."];
        }

        if ($pixel->hasAttribute('style') && preg_match('/^[a-z0-9\s:;\-.%]*$/i', $pixel->getAttribute('style')) !== 1) {
            return ['The style of a tracking pixel may only use plain properties such as display:none.'];
        }

        return self::inspectSource($pixel->getAttribute('src'));
    }

    /**
     * @return list<string>
     */
    private static function inspectSource(string $src): array
    {
        $parts = parse_url($src);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return ['The address must start with https://.'];
        }

        $host = strtolower($parts['host']);

        foreach (self::HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return [];
            }
        }

        return ["{$host} is not on the approved list of hosts. Ask your developer to add it."];
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function unknownAttribute(DOMElement $element, array $allowed): ?string
    {
        foreach ($element->attributes as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                return $attribute->name;
            }
        }

        return null;
    }
}
