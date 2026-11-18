<?php

namespace App\Support\Pages;

use App\Models\Page;
use App\Models\PageBlock;
use App\Rules\UniqueSlug;
use App\Support\Blocks\BlockType;
use App\Support\Seo\SeoRules;
use Illuminate\Validation\Rule;

/**
 * The format rules of a page's editor payload (VAL-01, VAL-05): the page, the block envelopes and the
 * SEO panel. A block's `data` is only required to be an array here: the schema rules of
 * CMS-PB-003 decide whether the block is valid, and an invalid block is saved as a draft-only block
 * and blocks publishing instead of rejecting the whole save. Used by the page Form Request (16.2).
 */
final class PageRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(?Page $current = null): array
    {
        $locked = $current?->is_system === true;

        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => $locked ? ['nullable'] : [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn([...ReservedSlugs::all(), Page::HOME, 'leadership']),
                new UniqueSlug(Page::class, $current?->id, 'page'),
            ],
            'template' => $locked ? ['nullable'] : ['required', 'string', Rule::in([Page::BUILDER, Page::LEGAL])],
            'last_reviewed_at' => ['nullable', 'date'],
            'blocks' => ['present', 'array', 'max:'.Page::BLOCKS_MAX],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string', Rule::in(BlockType::values())],
            'blocks.*.data' => ['present', 'array'],
            'blocks.*.is_visible' => ['required', 'boolean'],
            'blocks.*.anchor_id' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9-]{1,40}$/', 'distinct'],
            'blocks.*.background' => ['required', 'string', Rule::in(PageBlock::BACKGROUNDS)],
            'blocks.*.spacing' => ['required', 'string', Rule::in(PageBlock::SPACINGS)],
            'blocks.*.animate' => ['required', 'boolean'],
            ...SeoRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'slug' => 'URL', 'last_reviewed_at' => 'last updated date', 'blocks' => 'blocks', 'blocks.*.anchor_id' => 'section address',
            ...SeoRules::attributes(),
        ];
    }
}
