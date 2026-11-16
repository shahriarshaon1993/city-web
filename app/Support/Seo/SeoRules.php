<?php

namespace App\Support\Seo;

use App\Models\SeoMeta;
use Illuminate\Validation\Rule;

/**
 * §8.5 / DM-SEO: the validation of the SEO panel (CMP-ADM-SEO) that every publishable entity's
 * Form Request merges in under the `seo` key. Lengths follow the columns; the 60/160 character
 * advice of SEO-ONP-001/002 is a warning in the panel, not a rule.
 */
final class SeoRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $prefix = 'seo'): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.meta_title" => ['nullable', 'string', 'max:70'],
            "{$prefix}.meta_description" => ['nullable', 'string', 'max:170'],
            "{$prefix}.canonical_url" => ['nullable', 'string', 'url:https', 'max:500'],
            "{$prefix}.robots" => ['nullable', 'string', Rule::in(SeoMeta::ROBOTS)],
            "{$prefix}.og_title" => ['nullable', 'string', 'max:70'],
            "{$prefix}.og_description" => ['nullable', 'string', 'max:200'],
            "{$prefix}.og_image_asset_id" => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            "{$prefix}.twitter_card" => ['nullable', 'string', Rule::in(['summary', 'summary_large_image'])],
            "{$prefix}.focus_keyword" => ['nullable', 'string', 'max:80'],
            "{$prefix}.schema_enabled" => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(string $prefix = 'seo'): array
    {
        return [
            "{$prefix}.meta_title" => 'meta title',
            "{$prefix}.meta_description" => 'meta description',
            "{$prefix}.canonical_url" => 'canonical address',
            "{$prefix}.robots" => 'search engine setting',
            "{$prefix}.og_title" => 'share title',
            "{$prefix}.og_description" => 'share description',
            "{$prefix}.og_image_asset_id" => 'share image',
            "{$prefix}.focus_keyword" => 'focus keyword',
        ];
    }
}
