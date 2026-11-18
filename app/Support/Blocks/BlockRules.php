<?php

namespace App\Support\Blocks;

use App\Models\MediaAsset;
use App\Support\Html\BasicHtml;
use App\Support\Media\MediaKind;
use App\Support\Menus\LinkType;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Rule fragments the 28 schemas share, so every block validates links, images and rich text the same
 * way (VAL-01 defaults: required = `*`, otherwise nullable; string `max`).
 */
final class BlockRules
{
    /**
     * @return list<mixed>
     */
    public static function text(int $max, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
    }

    /**
     * @param  list<string>  $values
     * @return list<mixed>
     */
    public static function choice(array $values, bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', Rule::in($values)];
    }

    /**
     * A library image (or SVG) by asset id; `$minWidth` is the spec's "≥ N px" for photos.
     *
     * @return list<mixed>
     */
    public static function image(bool $required = false, ?int $minWidth = null): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'integer',
            Rule::exists('media_assets', 'id')->whereNull('deleted_at')->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value]),
        ];

        if ($minWidth !== null) {
            $rules[] = self::minWidth($minWidth);
        }

        return $rules;
    }

    /**
     * @return list<mixed>
     */
    public static function asset(MediaKind $kind, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'integer',
            Rule::exists('media_assets', 'id')->whereNull('deleted_at')->where('kind', $kind->value),
        ];
    }

    /** "Image ≥ 1920 px": a photo narrower than that is refused; an SVG has no pixel width. */
    public static function minWidth(int $pixels): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($pixels): void {
            $asset = is_numeric($value) ? MediaAsset::query()->find((int) $value) : null;

            if ($asset !== null && $asset->kind === MediaKind::Image && $asset->width !== null && $asset->width < $pixels) {
                $fail("The :attribute must be at least {$pixels} px wide.");
            }
        };
    }

    /**
     * Rich text (sanitised on save); `$maxWords` is the spec's word limit.
     *
     * @return list<mixed>
     */
    public static function html(bool $required = false, ?int $maxWords = null): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:20000'];

        if ($maxWords !== null) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) use ($maxWords): void {
                if (is_string($value) && BasicHtml::wordCount($value) > $maxWords) {
                    $fail("The :attribute must be {$maxWords} words or fewer.");
                }
            };
        }

        return $rules;
    }

    /**
     * A stored link (`label`, `link_type`, `linkable_id`, `url`, `anchor`, `new_tab`), the same
     * shape a menu item has (CMS-NAV-002). Optional links validate only when present.
     *
     * @return array<string, mixed>
     */
    public static function link(string $key, bool $required = false): array
    {
        $entities = implode(',', array_map(fn (LinkType $type): string => $type->value, array_filter(LinkType::cases(), fn (LinkType $type): bool => $type->isEntity())));

        return [
            $key => [$required ? 'required' : 'nullable', 'array'],
            "{$key}.label" => ["required_with:{$key}", 'string', 'max:24'],
            "{$key}.link_type" => ["required_with:{$key}", 'string', Rule::in(LinkType::values())],
            "{$key}.linkable_id" => ["required_if:{$key}.link_type,{$entities}", 'nullable', 'integer'],
            "{$key}.url" => ["required_if:{$key}.link_type,url", 'nullable', 'string', 'max:500', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && $value !== '' && preg_match('/^(https?:\/\/|mailto:|tel:|\/|#)/i', $value) !== 1) {
                    $fail('The :attribute must start with https://, /, # (a section of the page), mailto: or tel:.');
                }
            }],
            "{$key}.anchor" => ["required_if:{$key}.link_type,anchor", 'nullable', 'string', 'max:41', 'regex:/^#?[a-z][a-z0-9-]{1,40}$/'],
            "{$key}.new_tab" => ['nullable', 'boolean'],
        ];
    }

    /**
     * The `attributes` entries that go with {@see self::link()}.
     *
     * @return array<string, string>
     */
    public static function linkAttributes(string $key, string $name): array
    {
        return [
            "{$key}.label" => "{$name} label",
            "{$key}.link_type" => "{$name} target",
            "{$key}.linkable_id" => "{$name} target",
            "{$key}.url" => "{$name} address",
            "{$key}.anchor" => "{$name} section",
        ];
    }
}
