<?php

namespace App\Actions\Seo;

use App\Models\FacetPage;
use App\Models\Location;
use App\Models\Project;
use App\Models\SeoMeta;

/**
 * DM-SEO: stores the SEO panel of one entity. The row exists only once an editor fills a field
 * (until then the §9.3 templates apply), so a panel left at its defaults deletes it.
 */
final class SaveSeoMeta
{
    private const array TEXT = ['meta_title', 'meta_description', 'canonical_url', 'og_title', 'og_description', 'focus_keyword'];

    /**
     * @param  Project|Location|FacetPage  $entity  a model using HasSeo
     * @param  array<string, mixed>|null  $seo  validated `seo` input (SeoRules)
     */
    public function handle(Project|Location|FacetPage $entity, ?array $seo): void
    {
        $seo ??= [];
        $values = [];

        foreach (self::TEXT as $field) {
            $text = trim((string) ($seo[$field] ?? ''));
            $values[$field] = $text === '' ? null : $text;
        }

        $values['robots'] = (string) ($seo['robots'] ?? 'index,follow') ?: 'index,follow';
        $values['twitter_card'] = (string) ($seo['twitter_card'] ?? 'summary_large_image') ?: 'summary_large_image';
        $values['schema_enabled'] = (bool) ($seo['schema_enabled'] ?? true);
        $values['og_image_asset_id'] = ($seo['og_image_asset_id'] ?? null) ?: null;

        $isDefault = collect($values)->except(['robots', 'twitter_card', 'schema_enabled'])->filter(fn ($value) => $value !== null)->isEmpty()
            && $values['robots'] === 'index,follow'
            && $values['twitter_card'] === 'summary_large_image'
            && $values['schema_enabled'] === true;

        /** @var SeoMeta|null $row */
        $row = $entity->seoMeta()->first();

        if ($isDefault) {
            $row?->delete();

            return;
        }

        if ($row === null) {
            $entity->seoMeta()->create($values);

            return;
        }

        $row->fill($values)->save();
    }
}
