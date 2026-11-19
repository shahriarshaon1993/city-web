<?php

namespace App\Rules;

use App\Models\MediaAsset;
use App\Support\Media\MediaKind;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An image from the media library for a single-image field (VAL-01/03): it must exist, be an image
 * (an SVG only where `$allowSvg`), and measure at least the pixels the spec asks for. An SVG has no
 * pixel size, so the minimum does not apply to it.
 */
final class LibraryImage implements ValidationRule
{
    public function __construct(
        private readonly int $minWidth = 0,
        private readonly int $minHeight = 0,
        private readonly bool $allowSvg = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $asset = is_numeric($value) ? MediaAsset::query()->find((int) $value) : null;

        if ($asset === null || ! in_array($asset->kind, [MediaKind::Image, MediaKind::Svg], true)) {
            $fail('Choose an image from the library.');

            return;
        }

        if ($asset->kind === MediaKind::Svg) {
            if (! $this->allowSvg) {
                $fail('Choose a photo (JPEG, PNG or WebP), not an SVG.');
            }

            return;
        }

        $width = (int) $asset->width;
        $height = (int) $asset->height;

        if ($width < $this->minWidth || $height < $this->minHeight) {
            $fail($this->minHeight > 0
                ? "The :attribute must be at least {$this->minWidth} × {$this->minHeight} px (this one is {$width} × {$height})."
                : "The :attribute must be at least {$this->minWidth} px wide (this one is {$width} px).");
        }
    }
}
