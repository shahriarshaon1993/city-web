<?php

namespace App\Support\Media;

use enshrined\svgSanitize\Sanitizer;
use Illuminate\Validation\ValidationException;

/**
 * NFR-SEC-12 / CMS-MED-003: an uploaded SVG is rewritten by `enshrined/svg-sanitize` before it is
 * stored — scripts, event handlers, `javascript:` links and remote references are removed.
 */
final class SvgSanitizer
{
    /**
     * @return array{svg: string, width: int, height: int}
     *
     * @throws ValidationException when the file is not a usable SVG
     */
    public function clean(string $path): array
    {
        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->minify(true);
        $sanitizer->removeXMLTag(true);

        $clean = $sanitizer->sanitize((string) file_get_contents($path));

        if (! is_string($clean) || preg_match('/<svg[\s>]/i', $clean) !== 1) {
            throw ValidationException::withMessages(['file' => 'This SVG is damaged or contains nothing the library can keep. Export it again from your design tool.']);
        }

        return ['svg' => $clean, ...$this->dimensions($clean)];
    }

    /**
     * Intrinsic size from the viewBox, else the width/height attributes; 0 × 0 when neither is given.
     *
     * @return array{width: int, height: int}
     */
    private function dimensions(string $svg): array
    {
        if (preg_match('/<svg\b[^>]*>/i', $svg, $tag) !== 1) {
            return ['width' => 0, 'height' => 0];
        }

        if (preg_match('/viewBox\s*=\s*["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)\s*["\']/i', $tag[0], $box) === 1) {
            return ['width' => (int) round((float) $box[1]), 'height' => (int) round((float) $box[2])];
        }

        $attribute = fn (string $name): int => preg_match('/\b'.$name.'\s*=\s*["\']\s*([\d.]+)/i', $tag[0], $value) === 1 ? (int) round((float) $value[1]) : 0;

        return ['width' => $attribute('width'), 'height' => $attribute('height')];
    }
}
